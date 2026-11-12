<?php

namespace Tests\Feature;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberPostTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Judul Percobaan',
            'category' => 'Teknologi',
            'excerpt' => '',
            'content' => "## Halo\n\nIsi artikel **Markdown**.",
            'is_published' => false,
        ], $override);
    }

    public function test_index_lists_only_own_posts_with_stats(): void
    {
        $me = User::factory()->create();
        Post::factory()->published()->count(2)->create(['user_id' => $me->id]);
        Post::factory()->draft()->create(['user_id' => $me->id]);
        Post::factory()->published()->create(); // milik orang lain

        $this->actingAs($me)->get('/dashboard/posts')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Post/Index')
            ->has('posts.data', 3)
            ->where('stats', ['total' => 3, 'published' => 2, 'draft' => 1])
            ->has('categories', 6));
    }

    public function test_member_can_create_post_with_slug_and_auto_excerpt(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)->post('/dashboard/posts', $this->payload())->assertSessionHasNoErrors();

        $post = Post::firstOrFail();
        $this->assertSame($me->id, $post->user_id);
        $this->assertSame('judul-percobaan', $post->slug);
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
        $this->assertStringContainsString('Isi artikel Markdown', $post->excerpt);
    }

    public function test_duplicate_titles_get_unique_slugs(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)->post('/dashboard/posts', $this->payload());
        $this->actingAs($me)->post('/dashboard/posts', $this->payload());

        $this->assertSame(['judul-percobaan', 'judul-percobaan-2'], Post::orderBy('id')->pluck('slug')->all());
    }

    public function test_validation_rejects_invalid_category_and_empty_content(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/dashboard/posts', $this->payload(['category' => 'Olahraga', 'content' => '']))
            ->assertSessionHasErrors(['category', 'content']);
    }

    public function test_cover_upload_is_stored_and_removed_on_delete(): void
    {
        $disk = config('filesystems.media_disk', 'public');
        Storage::fake($disk);
        $me = User::factory()->create();

        $this->actingAs($me)->post('/dashboard/posts', $this->payload(['cover' => UploadedFile::fake()->image('cover.jpg', 800, 450)]));

        $post = Post::firstOrFail();
        Storage::disk($disk)->assertExists($post->cover_image_path);

        $this->actingAs($me)->delete("/dashboard/posts/{$post->id}")->assertRedirect(route('dashboard.posts.index'));
        Storage::disk($disk)->assertMissing($post->cover_image_path);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_owner_can_update_but_slug_stays_stable(): void
    {
        $me = User::factory()->create();
        $post = Post::factory()->draft()->create(['user_id' => $me->id, 'slug' => 'slug-lama']);

        $this->actingAs($me)->put("/dashboard/posts/{$post->id}", $this->payload(['title' => 'Judul Baru', 'is_published' => true]))
            ->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame('Judul Baru', $post->title);
        $this->assertSame('slug-lama', $post->slug);
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
    }

    public function test_toggle_publish_flips_status_and_published_at(): void
    {
        $me = User::factory()->create();
        $post = Post::factory()->draft()->create(['user_id' => $me->id]);

        $this->actingAs($me)->patch("/dashboard/posts/{$post->id}/toggle-publish");
        $this->assertTrue($post->fresh()->is_published);
        $this->assertNotNull($post->fresh()->published_at);

        $this->actingAs($me)->patch("/dashboard/posts/{$post->id}/toggle-publish");
        $this->assertFalse($post->fresh()->is_published);
        $this->assertNull($post->fresh()->published_at);
    }

    public function test_member_cannot_touch_someone_elses_post(): void
    {
        $me = User::factory()->create();
        $other = Post::factory()->published()->create();

        $this->actingAs($me)->get("/dashboard/posts/{$other->id}")->assertForbidden();
        $this->actingAs($me)->put("/dashboard/posts/{$other->id}", $this->payload())->assertForbidden();
        $this->actingAs($me)->delete("/dashboard/posts/{$other->id}")->assertForbidden();
        $this->actingAs($me)->patch("/dashboard/posts/{$other->id}/toggle-publish")->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $other->id]);
    }

    public function test_admin_cannot_use_member_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard/posts')->assertForbidden();
    }

    public function test_excel_export_downloads_xlsx(): void
    {
        $me = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $me->id]);

        $response = $this->actingAs($me)->get('/dashboard/posts/export');

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }

    public function test_ai_generate_flashes_items_and_save_creates_drafts(): void
    {
        $this->app->bind(AiClientInterface::class, fn () => new class implements AiClientInterface {
            public function generatePosts(string $topic, int $count = 1, ?string $category = null): array
            {
                return array_map(
                    fn ($i) => new GeneratedPostItem("Draf AI {$i}", 'Opini', 'Ringkas', "Isi draf {$i}"),
                    range(1, $count),
                );
            }
        });
        $me = User::factory()->create();

        $response = $this->actingAs($me)->post('/dashboard/posts/ai-generate', ['topic' => 'Literasi digital', 'count' => 2]);
        $response->assertSessionHas('aiGeneratedItems', fn ($items) => count($items) === 2 && $items[0]['title'] === 'Draf AI 1');

        $this->actingAs($me)->post('/dashboard/posts/ai-save', ['items' => [
            ['title' => 'Draf AI 1', 'category' => 'Opini', 'excerpt' => 'Ringkas', 'content' => 'Isi draf 1'],
        ]]);
        $this->assertDatabaseHas('posts', ['user_id' => $me->id, 'title' => 'Draf AI 1', 'is_published' => false]);
    }

    public function test_ai_count_must_be_within_limit(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/dashboard/posts/ai-generate', ['topic' => 'Apa saja', 'count' => 99])
            ->assertSessionHasErrors('count');
    }
}
