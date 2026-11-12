<?php

namespace Tests\Unit;

use App\Features\Ai\Services\OpenAiPostGeneratorService;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PostDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_enum_values_and_loose_mapping(): void
    {
        $this->assertSame(['Teknologi', 'Pendidikan', 'Gaya Hidup', 'Bisnis', 'Opini', 'Umum'], PostCategory::values());
        $this->assertSame(PostCategory::GAYA_HIDUP, PostCategory::fromLoose('  gaya hidup '));
        $this->assertSame(PostCategory::UMUM, PostCategory::fromLoose('tidak dikenal'));
    }

    public function test_slug_generator_is_unique_and_handles_empty_titles(): void
    {
        $user = User::factory()->create();
        Post::factory()->create(['user_id' => $user->id, 'slug' => 'halo-dunia']);

        $this->assertSame('halo-dunia-2', Post::generateUniqueSlug('Halo Dunia!'));
        $this->assertSame('artikel', Post::generateUniqueSlug('???'));
    }

    public function test_excerpt_strips_markdown(): void
    {
        $text = app(PostService::class)->makeExcerpt("## Judul\n\nIni **tebal** dan [tautan](https://x.test).\n\n```php\necho 1;\n```");

        $this->assertSame('Judul Ini tebal dan tautan.', $text);
    }

    public function test_post_casts_category_to_enum(): void
    {
        $post = Post::factory()->create(['category' => 'Bisnis']);

        $this->assertSame(PostCategory::BISNIS, $post->fresh()->category);
        $this->assertTrue($post->user->is(User::find($post->user_id)));
    }

    public function test_ai_parser_accepts_fenced_json_and_rejects_garbage(): void
    {
        $ai = new OpenAiPostGeneratorService('https://example.test/v1', 'key', 'm');
        $json = "```json\n{\"posts\":[{\"title\":\"Judul\",\"category\":\"opini\",\"excerpt\":\"E\",\"content\":\"Isi\"}]}\n```";

        $items = $ai->parseContent($json);
        $this->assertCount(1, $items);
        $this->assertSame('Opini', $items[0]->category);

        $this->expectException(RuntimeException::class);
        $ai->parseContent('bukan json');
    }

    public function test_excel_round_trip_import(): void
    {
        $user = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Artikel Ekspor', 'category' => 'Pendidikan']);

        $excel = app(PostExcelService::class);
        $path = tempnam(sys_get_temp_dir(), 'bv').'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($excel->buildSpreadsheet($user->posts()->get())))->save($path);

        $result = $excel->import($path, $user->id);
        @unlink($path);

        $this->assertSame(['imported' => 1, 'skipped' => 0], $result);
        $copy = Post::where('user_id', $user->id)->where('id', '!=', Post::first()->id)->firstOrFail();
        $this->assertSame('Artikel Ekspor', $copy->title);
        $this->assertSame(PostCategory::PENDIDIKAN, $copy->category);
        $this->assertTrue($copy->is_published);
        $this->assertNotSame(Post::first()->slug, $copy->slug);
    }
}
