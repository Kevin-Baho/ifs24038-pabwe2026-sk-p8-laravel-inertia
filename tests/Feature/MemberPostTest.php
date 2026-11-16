<?php

namespace Tests\Feature;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);
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

    // ── NEW TESTS FOR MISSING COVERAGE ──────────────────────────────────────

    /** Covers PostController::show() – owner sees own published post */
    public function test_owner_can_view_own_post_detail(): void
    {
        $me = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $me->id]);

        $this->actingAs($me)
            ->get("/dashboard/posts/{$post->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Post/Show')
                ->where('post.slug', $post->slug)
                ->where('publicUrl', route('articles.show', $post->slug))
            );
    }

    /** Covers PostController::show() – draft post, publicUrl = null */
    public function test_owner_can_view_draft_post_detail_with_null_public_url(): void
    {
        $me = User::factory()->create();
        $post = Post::factory()->draft()->create(['user_id' => $me->id]);

        $this->actingAs($me)
            ->get("/dashboard/posts/{$post->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Post/Show')
                ->where('publicUrl', null)
            );
    }

    /** Covers PostController::index() – ?edit={id} when post belongs to user (Gate allows) */
    public function test_index_with_edit_query_param_returns_editing_post(): void
    {
        $me = User::factory()->create();
        $post = Post::factory()->draft()->create(['user_id' => $me->id]);

        $this->actingAs($me)
            ->get("/dashboard/posts?edit={$post->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Post/Index')
                ->where('editing.id', $post->id)
            );
    }

    /** Covers PostController::index() – ?edit={id} when candidate belongs to other user */
    public function test_index_with_edit_query_param_of_other_user_post_returns_null_editing(): void
    {
        $me = User::factory()->create();
        $other = Post::factory()->draft()->create(); // belongs to another user

        $this->actingAs($me)
            ->get("/dashboard/posts?edit={$other->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Post/Index')
                ->where('editing', null)
            );
    }

    /** Covers PostController::update() – new cover upload replaces old one */
    public function test_update_post_with_new_cover_replaces_old_cover(): void
    {
        $disk = config('filesystems.media_disk', 'public');
        Storage::fake($disk);
        $me = User::factory()->create();

        $this->actingAs($me)->post('/dashboard/posts', $this->payload([
            'cover' => UploadedFile::fake()->image('old.jpg'),
        ]));
        $post = Post::firstOrFail();
        $oldPath = $post->cover_image_path;
        $this->assertNotNull($oldPath);

        $this->actingAs($me)->put("/dashboard/posts/{$post->id}", $this->payload([
            'title' => 'Judul Diperbarui',
            'cover' => UploadedFile::fake()->image('new.jpg'),
        ]))->assertSessionHasNoErrors();

        $post->refresh();
        Storage::disk($disk)->assertMissing($oldPath);
        Storage::disk($disk)->assertExists($post->cover_image_path);
        $this->assertNotSame($oldPath, $post->cover_image_path);
    }

    /** Covers PostController::update() – remove_cover flag clears cover */
    public function test_update_post_with_remove_cover_flag(): void
    {
        $disk = config('filesystems.media_disk', 'public');
        Storage::fake($disk);
        $me = User::factory()->create();

        $this->actingAs($me)->post('/dashboard/posts', $this->payload([
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ]));
        $post = Post::firstOrFail();
        $this->assertNotNull($post->cover_image_path);

        $this->actingAs($me)->put("/dashboard/posts/{$post->id}", $this->payload([
            'remove_cover' => true,
        ]))->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertNull($post->cover_image_path);
    }

    /** Covers PostController::importExcel() – successful import using export round-trip */
    public function test_import_excel_creates_posts(): void
    {
        $me = User::factory()->create();
        Post::factory()->published()->create([
            'user_id' => $me->id,
            'title' => 'Artikel Untuk Impor',
            'category' => 'Teknologi',
        ]);

        $excel = app(PostExcelService::class);
        $tmpPath = tempnam(sys_get_temp_dir(), 'import_test') . '.xlsx';
        (new Xlsx($excel->buildSpreadsheet($me->posts()->get())))->save($tmpPath);

        $response = $this->actingAs($me)->post('/dashboard/posts/import', [
            'importFile' => new UploadedFile(
                $tmpPath,
                'import.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ),
        ]);

        @unlink($tmpPath);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertSame(2, Post::where('user_id', $me->id)->count());
    }

    /** Covers PostController::importExcel() – skipped rows counted in message */
    public function test_import_excel_reports_skipped_rows(): void
    {
        $me = User::factory()->create();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Judul');
        $sheet->setCellValue('C1', 'Kategori');
        $sheet->setCellValue('D1', 'Ringkasan');
        $sheet->setCellValue('E1', 'Konten');
        $sheet->setCellValue('F1', 'Status');
        // Valid row
        $sheet->setCellValue('B2', 'Artikel Valid');
        $sheet->setCellValue('C2', 'Teknologi');
        $sheet->setCellValue('E2', 'Konten valid');
        $sheet->setCellValue('F2', 'Published');
        // Blank title row (skipped)
        $sheet->setCellValue('B3', '');
        $sheet->setCellValue('E3', 'Konten tanpa judul');

        $tmpPath = tempnam(sys_get_temp_dir(), 'import_skip') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmpPath);

        $response = $this->actingAs($me)->post('/dashboard/posts/import', [
            'importFile' => new UploadedFile(
                $tmpPath,
                'import.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ),
        ]);

        @unlink($tmpPath);

        $response->assertSessionHas('success');
        $this->assertStringContainsString('1 baris dilewati', session('success'));
    }

    /** Covers PostController::importExcel() – validation fails on wrong mime */
    public function test_import_excel_validates_file_type(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)->post('/dashboard/posts/import', [
            'importFile' => UploadedFile::fake()->create('bad.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('importFile');
    }

    /** Covers PostController::generateAi() – RuntimeException branch */
    public function test_generate_ai_runtime_exception_flashes_error(): void
    {
        $this->app->bind(AiClientInterface::class, fn () => new class implements AiClientInterface {
            public function generatePosts(string $topic, int $count = 1, ?string $category = null): array
            {
                throw new \RuntimeException('Rate limit exceeded');
            }
        });

        $this->actingAs(User::factory()->create())
            ->post('/dashboard/posts/ai-generate', ['topic' => 'Topik Test', 'count' => 1])
            ->assertSessionHas('error', 'Gagal menghasilkan draf dengan AI: Rate limit exceeded');
    }

    /** Covers PostController::generateAi() – generic Throwable branch */
    public function test_generate_ai_generic_throwable_flashes_service_unavailable(): void
    {
        $this->app->bind(AiClientInterface::class, fn () => new class implements AiClientInterface {
            public function generatePosts(string $topic, int $count = 1, ?string $category = null): array
            {
                throw new \Exception('Connection timeout');
            }
        });

        $this->actingAs(User::factory()->create())
            ->post('/dashboard/posts/ai-generate', ['topic' => 'Topik Test', 'count' => 1])
            ->assertSessionHas('error', 'Layanan AI tidak dapat dihubungi. Coba lagi beberapa saat.');
    }
}
