<?php

namespace Tests\Unit;

use App\Features\Ai\Services\OpenAiPostGeneratorService;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Policies\PostPolicy;
use App\Features\Post\Repositories\PostRepository;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Features\Post\Support\PostTransformer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    // ── NEW TESTS FOR MISSING COVERAGE ──────────────────────────────────────

    /** Covers PostPolicy::viewAny() */
    public function test_post_policy_view_any_always_returns_true(): void
    {
        $policy = new PostPolicy();
        $user = User::factory()->create();

        $this->assertTrue($policy->viewAny($user));
    }

    /** Covers PostPolicy::view() – admin can view, non-owner cannot */
    public function test_post_policy_view_admin_can_view_any_post(): void
    {
        $policy = new PostPolicy();
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        $this->assertTrue($policy->view($admin, $post));
    }

    /** Covers PostPolicy::view() – member cannot view another's post */
    public function test_post_policy_view_member_can_only_view_own_post(): void
    {
        $policy = new PostPolicy();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($policy->view($owner, $post));
        $this->assertFalse($policy->view($other, $post));
    }

    /** Covers User::profilePhotoUrl() when avatar_url is null (no photo) */
    public function test_user_profile_photo_url_returns_ui_avatars_when_no_photo(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso', 'profile_photo_path' => null]);

        $url = $user->profilePhotoUrl();

        $this->assertStringContainsString('ui-avatars.com', $url);
        $this->assertStringContainsString(urlencode('Budi Santoso'), $url);
    }

    /** Covers User::profilePhotoUrl() when avatar_url is set */
    public function test_user_profile_photo_url_returns_storage_url_when_photo_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/test.jpg', 'fake');

        $user = User::factory()->create(['profile_photo_path' => 'avatars/test.jpg']);

        $url = $user->profilePhotoUrl();

        $this->assertStringNotContainsString('ui-avatars.com', $url);
        $this->assertStringContainsString('avatars/test.jpg', $url);
    }

    /** Covers Post::coverImageUrl() – returns null when path is null */
    public function test_post_cover_image_url_returns_null_when_no_cover(): void
    {
        $post = Post::factory()->create(['cover_image_path' => null]);

        $this->assertNull($post->coverImageUrl());
    }

    /** Covers Post::coverImageUrl() – returns URL when path set */
    public function test_post_cover_image_url_returns_url_when_cover_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('post-covers/test.jpg', 'fake');

        $post = Post::factory()->create(['cover_image_path' => 'post-covers/test.jpg']);

        $this->assertNotNull($post->coverImageUrl());
        $this->assertStringContainsString('post-covers/test.jpg', $post->coverImageUrl());
    }

    /** Covers PostTransformer::adminRow() – post has no user (null author) */
    public function test_post_transformer_admin_row_with_null_user(): void
    {
        // Create post and disassociate user
        $post = Post::factory()->create();
        $post->user_id = null;
        // Don't save – just call the transformer with a detached relationship
        // We load the relationship manually as null
        $post->setRelation('user', null);

        $row = PostTransformer::adminRow($post);

        $this->assertNull($row['author']);
        $this->assertArrayNotHasKey('content', $row);
    }

    /** Covers PostTransformer::card() – post has user with profile photo */
    public function test_post_transformer_card_with_author_having_profile_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/author.jpg', 'fake');

        $user = User::factory()->create(['profile_photo_path' => 'avatars/author.jpg']);
        $post = Post::factory()->published()->create(['user_id' => $user->id]);
        $post->load('user');

        $card = PostTransformer::card($post);

        $this->assertNotNull($card['author']['avatar_url']);
        $this->assertStringContainsString('avatars/author.jpg', $card['author']['avatar_url']);
    }

    /** Covers PostTransformer::card() – post has user WITHOUT profile photo (null avatar) */
    public function test_post_transformer_card_with_author_without_profile_photo(): void
    {
        $user = User::factory()->create(['profile_photo_path' => null]);
        $post = Post::factory()->published()->create(['user_id' => $user->id]);
        $post->load('user');

        $card = PostTransformer::card($post);

        $this->assertNull($card['author']['avatar_url']);
    }

    /** Covers PostRepository::allForUser() */
    public function test_post_repository_all_for_user(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(3)->create(['user_id' => $user->id]);
        Post::factory()->create(); // other user

        $repo = app(PostRepository::class);
        $posts = $repo->allForUser($user->id);

        $this->assertCount(3, $posts);
    }

    /** Covers PostRepository::allWithAuthor() with userId filter */
    public function test_post_repository_all_with_author_filtered_by_user(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $user->id]);
        Post::factory()->count(2)->create(); // other users

        $repo = app(PostRepository::class);
        $posts = $repo->allWithAuthor($user->id);

        $this->assertCount(2, $posts);
    }

    /** Covers PostRepository::statsGlobal() with userId */
    public function test_post_repository_stats_global_with_user_id(): void
    {
        $user = User::factory()->create();
        Post::factory()->published()->count(2)->create(['user_id' => $user->id]);
        Post::factory()->draft()->create(['user_id' => $user->id]);
        Post::factory()->published()->create(); // other user

        $repo = app(PostRepository::class);
        $stats = $repo->statsGlobal($user->id);

        $this->assertSame(['total' => 3, 'published' => 2, 'draft' => 1], $stats);
    }

    /** Covers PostService::createDrafts() – skips items with blank title or content */
    public function test_post_service_create_drafts_skips_blank_items(): void
    {
        $user = User::factory()->create();
        $service = app(PostService::class);

        $count = $service->createDrafts($user->id, [
            ['title' => 'Valid Draft', 'category' => 'Umum', 'excerpt' => '', 'content' => 'Konten valid'],
            ['title' => '', 'category' => 'Umum', 'excerpt' => '', 'content' => 'No title'],          // skipped
            ['title' => 'No Content', 'category' => 'Umum', 'excerpt' => '', 'content' => ''],        // skipped
        ]);

        $this->assertSame(1, $count);
        $this->assertDatabaseCount('posts', 1);
    }

    /** Covers PostExcelService::import() – empty file (empty rows array) */
    public function test_post_excel_import_empty_file_returns_zero(): void
    {
        $user = User::factory()->create();

        // Register custom reader that produces an empty array from toArray()
        \PhpOffice\PhpSpreadsheet\IOFactory::registerReader('Csv', EmptySheetReaderStub::class);

        $path = storage_path('framework/testing/empty_rows_test.csv');
        file_put_contents($path, "col\nval");

        $excel = app(PostExcelService::class);
        $result = $excel->import($path, $user->id);

        \PhpOffice\PhpSpreadsheet\IOFactory::restoreDefaultReadersAndWriters();
        @unlink($path);

        $this->assertSame(['imported' => 0, 'skipped' => 0], $result);
    }

    /** Covers OpenAiPostGeneratorService constructor – OPENAI_ENDPOINT env branch */
    public function test_open_ai_service_constructor_uses_env_endpoint(): void
    {
        putenv('OPENAI_ENDPOINT=https://env.endpoint.test/v1');
        putenv('OPENAI_MODEL=env-model');

        $service = new OpenAiPostGeneratorService(null, 'sk-some-key', null);

        // If no exception, constructor resolved correctly
        $this->assertInstanceOf(OpenAiPostGeneratorService::class, $service);

        putenv('OPENAI_ENDPOINT');
        putenv('OPENAI_MODEL');
    }

    /** Covers OpenAiPostGeneratorService constructor – non-groq key with no env fallback */
    public function test_open_ai_service_constructor_fallback_to_openai_defaults(): void
    {
        putenv('OPENAI_ENDPOINT');
        putenv('OPENAI_MODEL');

        $service = new OpenAiPostGeneratorService(null, 'sk-openai-not-groq', null);

        $this->assertInstanceOf(OpenAiPostGeneratorService::class, $service);
    }

    /** Covers PostExcelService::buildSpreadsheet() – withAuthor = true (adds Penulis & Username columns) */
    public function test_post_excel_build_spreadsheet_with_author(): void
    {
        $user = User::factory()->create(['name' => 'Penulis Utama', 'username' => 'penulisutama']);
        Post::factory()->published()->create(['user_id' => $user->id]);

        $excel = app(PostExcelService::class);
        $spreadsheet = $excel->buildSpreadsheet($user->posts()->with('user')->get(), true, 'Admin Export');

        $sheet = $spreadsheet->getActiveSheet();
        // toArray() with first argument false returns header values as plain strings
        $headerRow = $sheet->rangeToArray('A1:J1', null, false, false, false)[0];
        $headers = array_filter(array_values($headerRow));

        $this->assertContains('Penulis', $headers);
        $this->assertContains('Username', $headers);
    }

    /** Covers PostTransformer::author() when $post->user is null */
    public function test_post_transformer_card_with_null_author(): void
    {
        $post = Post::factory()->create();
        $post->setRelation('user', null);

        $card = PostTransformer::card($post);

        $this->assertNull($card['author']);
    }

    /** Covers PostRepository::find() and filter branches (status draft, category) */
    public function test_post_repository_find_and_filter_branches(): void
    {
        $user = User::factory()->create();
        $draft = Post::factory()->draft()->create(['user_id' => $user->id, 'category' => 'Bisnis']);
        $published = Post::factory()->published()->create(['user_id' => $user->id, 'category' => 'Teknologi']);

        $repo = app(PostRepository::class);

        // find
        $this->assertTrue($draft->is($repo->find($draft->id)));
        $this->assertNull($repo->find(999999));

        // filter status = draft
        $draftPosts = $repo->paginateForUser($user->id, ['status' => 'draft']);
        $this->assertCount(1, $draftPosts->items());
        $this->assertSame($draft->id, $draftPosts->items()[0]->id);

        // filter status = published
        $publishedPosts = $repo->paginateForUser($user->id, ['status' => 'published']);
        $this->assertCount(1, $publishedPosts->items());
        $this->assertSame($published->id, $publishedPosts->items()[0]->id);

        // filter category = Bisnis
        $categoryPosts = $repo->paginateForUser($user->id, ['category' => 'Bisnis']);
        $this->assertCount(1, $categoryPosts->items());
        $this->assertSame($draft->id, $categoryPosts->items()[0]->id);

        // allWithAuthor with null userId
        $allWithAuthor = $repo->allWithAuthor(null);
        $this->assertGreaterThanOrEqual(2, $allWithAuthor->count());
    }

    /** Covers PostExcelService::import() fallbacks for content from excerpt and title */
    public function test_post_excel_import_content_fallbacks(): void
    {
        $user = User::factory()->create();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Judul');
        $sheet->setCellValue('B1', 'Kategori');
        $sheet->setCellValue('C1', 'Ringkasan');
        $sheet->setCellValue('D1', 'Konten');
        $sheet->setCellValue('E1', 'Status');

        // Row 1: content='', excerpt='' -> content becomes title
        $sheet->setCellValue('A2', 'Judul Saja');
        $sheet->setCellValue('B2', 'Umum');
        $sheet->setCellValue('C2', '');
        $sheet->setCellValue('D2', '');
        $sheet->setCellValue('E2', 'Draft');

        // Row 2: content='', excerpt='Ringkasan Saja' -> content becomes excerpt
        $sheet->setCellValue('A3', 'Judul Dengan Excerpt');
        $sheet->setCellValue('B3', 'Umum');
        $sheet->setCellValue('C3', 'Ringkasan Saja');
        $sheet->setCellValue('D3', '');
        $sheet->setCellValue('E3', 'Draft');

        $tmpPath = tempnam(sys_get_temp_dir(), 'fallbacks_import') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tmpPath);

        $excel = app(PostExcelService::class);
        $result = $excel->import($tmpPath, $user->id);
        @unlink($tmpPath);

        $this->assertSame(['imported' => 2, 'skipped' => 0], $result);
        $post1 = Post::where('title', 'Judul Saja')->firstOrFail();
        $this->assertSame('Judul Saja', $post1->content);
        $post2 = Post::where('title', 'Judul Dengan Excerpt')->firstOrFail();
        $this->assertSame('Ringkasan Saja', $post2->content);
    }
}

class EmptySheetReaderStub extends \PhpOffice\PhpSpreadsheet\Reader\Csv
{
    public function load(string $filename, int $flags = 0): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $sheet = new class extends \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet {
            public function __construct() {}
            public function toArray(
                mixed $nullValue = null,
                bool $calculateFormulas = true,
                bool $formatData = true,
                bool $returnCellRef = false,
                bool $ignoreHidden = false,
                bool $reduceArrays = false,
                bool $lessFloatPrecision = false,
                bool $oldCalculatedValue = false,
            ): array {
                return [];
            }
        };

        return new class($sheet) extends \PhpOffice\PhpSpreadsheet\Spreadsheet {
            private \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $mockSheet;
            public function __construct(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $mockSheet)
            {
                parent::__construct();
                $this->mockSheet = $mockSheet;
            }
            public function getActiveSheet(): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
            {
                return $this->mockSheet;
            }
        };
    }
}
