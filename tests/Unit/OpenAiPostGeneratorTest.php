<?php

namespace Tests\Unit;

use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Ai\Services\OpenAiPostGeneratorService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiPostGeneratorTest extends TestCase
{
    public function test_generated_post_item_to_array(): void
    {
        $dto = new GeneratedPostItem('Judul Test', 'Teknologi', 'Ringkasan test', 'Konten test');
        $arr = $dto->toArray();

        $this->assertSame([
            'title' => 'Judul Test',
            'category' => 'Teknologi',
            'excerpt' => 'Ringkasan test',
            'content' => 'Konten test',
        ], $arr);
    }

    public function test_constructor_resolves_different_configurations(): void
    {
        // 1. Explicit arguments
        $service1 = new OpenAiPostGeneratorService('https://custom.api/v1', 'custom-key', 'custom-model');
        $this->assertInstanceOf(OpenAiPostGeneratorService::class, $service1);

        // 2. Groq key detection
        $service2 = new OpenAiPostGeneratorService(null, 'gsk_test123', null);
        $this->assertInstanceOf(OpenAiPostGeneratorService::class, $service2);

        // 3. Fallback default
        $service3 = new OpenAiPostGeneratorService(null, 'sk-openai', null);
        $this->assertInstanceOf(OpenAiPostGeneratorService::class, $service3);
    }

    public function test_generate_posts_validates_empty_topic_and_missing_key(): void
    {
        $service = new OpenAiPostGeneratorService('https://api.openai.com/v1', 'valid-key', 'gpt-4');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Topik tidak boleh kosong.');
        $service->generatePosts('   ');
    }

    public function test_generate_posts_validates_missing_key(): void
    {
        $service = new OpenAiPostGeneratorService('https://api.openai.com/v1', '', 'gpt-4');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_KEY belum dikonfigurasi di file .env.');
        $service->generatePosts('Topik Valid');
    }

    public function test_generate_posts_handles_http_failure(): void
    {
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'Rate limit exceeded']], 429),
        ]);

        $service = new OpenAiPostGeneratorService('https://api.openai.com/v1', 'test-key', 'gpt-4');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AI API Error: Rate limit exceeded');
        $service->generatePosts('Topik Valid');
    }

    public function test_generate_posts_handles_empty_ai_response(): void
    {
        Http::fake([
            '*' => Http::response(['choices' => [['message' => ['content' => null]]]], 200),
        ]);

        $service = new OpenAiPostGeneratorService('https://api.openai.com/v1', 'test-key', 'gpt-4');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AI tidak mengembalikan respons teks.');
        $service->generatePosts('Topik Valid');
    }

    public function test_generate_posts_successfully_parses_markdown_json_block(): void
    {
        $jsonPayload = json_encode([
            'posts' => [
                [
                    'title' => 'Masa Depan AI',
                    'category' => 'Teknologi',
                    'excerpt' => 'Ringkasan artikel AI',
                    'content' => '## Pendahuluan\n\nIsi artikel mendalam.',
                ],
            ],
        ]);

        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "```json\n" . $jsonPayload . "\n```",
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAiPostGeneratorService('https://api.openai.com/v1', 'test-key', 'gpt-4');
        $items = $service->generatePosts('Masa Depan AI', 1, 'Teknologi');

        $this->assertCount(1, $items);
        $this->assertSame('Masa Depan AI', $items[0]->title);
        $this->assertSame('Teknologi', $items[0]->category);
    }

    public function test_parse_content_handles_raw_list_and_empty_items(): void
    {
        $service = new OpenAiPostGeneratorService('https://api.openai.com/v1', 'test-key', 'gpt-4');

        // Raw list format
        $jsonList = json_encode([
            [
                'title' => 'Judul List',
                'category' => 'Umum',
                'excerpt' => 'Excerpt list',
                'content' => 'Content list',
            ],
        ]);
        $items = $service->parseContent($jsonList);
        $this->assertCount(1, $items);
        $this->assertSame('Judul List', $items[0]->title);

        // Invalid JSON string
        $this->expectException(RuntimeException::class);
        $service->parseContent('bukan_json_valid');
    }

    public function test_parse_content_throws_when_no_valid_posts(): void
    {
        $service = new OpenAiPostGeneratorService('https://api.openai.com/v1', 'test-key', 'gpt-4');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak ada draf artikel valid yang dihasilkan AI.');
        $service->parseContent(json_encode(['posts' => []]));
    }
}
