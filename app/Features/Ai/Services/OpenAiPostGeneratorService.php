<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Enums\PostCategory;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiPostGeneratorService implements AiClientInterface
{
    public const MAX_COUNT = 5;

    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;

    public function __construct(
        ?string $baseUrl = null,
        ?string $apiKey = null,
        ?string $model = null,
    ) {
        $this->baseUrl = $baseUrl ?: (string) (config('openai.endpoint') ?: 'https://api.groq.com/openai/v1');
        $this->apiKey = $apiKey ?: (string) (env('OPENAI_KEY') ?? config('openai.key', ''));
        $this->model = $model ?: (string) (env('OPENAI_MODEL') ?: 'openai/gpt-oss-20b');
    }

    public function generatePosts(string $topic, int $count = 1, ?string $category = null): array
    {
        $topic = trim($topic);
        if ($topic === '') {
            throw new RuntimeException('Topik tidak boleh kosong.');
        }

        if ($this->apiKey === '') {
            throw new RuntimeException('OPENAI_KEY belum dikonfigurasi di file .env.');
        }

        $safeCount = min(self::MAX_COUNT, max(1, $count));
        $categories = implode(', ', PostCategory::values());
        $fixedCategory = $category ? PostCategory::fromLoose($category)->value : null;
        $categoryRule = $fixedCategory
            ? "Semua artikel WAJIB berkategori \"{$fixedCategory}\"."
            : "Pilih satu kategori yang paling cocok dari pilihan berikut: {$categories}.";

        $systemPrompt = "Bertindaklah sebagai Senior Content Writer profesional yang menulis artikel blog berbahasa Indonesia yang mendalam, informatif, dan menarik.\n"
            . "Pengguna meminta draf artikel dengan detail berikut:\n"
            . "- Topik Utama: '{$topic}'\n"
            . "- Jumlah Artikel: {$safeCount}\n"
            . "- Aturan Kategori: {$categoryRule}\n\n"
            . "INSTRUKSI PENULISAN:\n"
            . "1. Judul harus spesifik membahas topik di atas.\n"
            . "2. Bagian 'excerpt' berupa ringkasan 1-2 kalimat.\n"
            . "3. Bagian 'content' WAJIB dalam format Markdown yang kaya (minimal 250-400 kata), mencakup subjudul (##) dan paragraf yang mendalam.\n\n"
            . "PENTING: Balas HANYA dengan format JSON valid TANPA teks pengantar atau penutup di luar JSON dengan struktur berikut:\n"
            . "{\n"
            . "  \"posts\": [\n"
            . "    {\n"
            . "      \"title\": \"Judul spesifik dan menarik\",\n"
            . "      \"category\": \"salah satu kategori yang valid\",\n"
            . "      \"excerpt\": \"Ringkasan 1-2 kalimat\",\n"
            . "      \"content\": \"Isi artikel lengkap dalam format Markdown\"\n"
            . "    }\n"
            . "  ]\n"
            . "}";

        $timeout = (int) env('AI_TIMEOUT', 50);
        $endpoint = rtrim($this->baseUrl, '/').'/chat/completions';

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout($timeout)
            ->post($endpoint, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $topic],
                ],
                'temperature' => 0.7,
            ]);

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? "status {$response->status()}";
            throw new RuntimeException("Groq API Error: {$message}");
        }

        $rawContent = $response->json('choices.0.message.content');

        if (! $rawContent) {
            throw new RuntimeException('AI tidak mengembalikan respons teks.');
        }

        return $this->parseContent((string) $rawContent, $fixedCategory);
    }

    /**
     * @return array<int, GeneratedPostItem>
     */
    public function parseContent(string $raw, ?string $fixedCategory = null): array
    {
        $clean = trim($raw);

        if (str_starts_with($clean, '```')) {
            $clean = trim((string) preg_replace(['/^```(?:json)?\s*/i', '/\s*```$/'], '', $clean));
        }

        $decoded = json_decode($clean, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('AI mengembalikan format respons JSON yang tidak valid.');
        }

        $items = isset($decoded['posts']) && is_array($decoded['posts'])
            ? $decoded['posts']
            : (array_is_list($decoded) ? $decoded : []);

        $results = [];
        foreach ($items as $item) {
            if (! is_array($item) || blank($item['title'] ?? null) || blank($item['content'] ?? null)) {
                continue;
            }

            $excerpt = trim((string) ($item['excerpt'] ?? ''));

            $results[] = new GeneratedPostItem(
                title: mb_substr(trim((string) $item['title']), 0, 255),
                category: $fixedCategory ?? PostCategory::fromLoose($item['category'] ?? null)->value,
                excerpt: $excerpt !== '' ? mb_substr($excerpt, 0, 500) : null,
                content: trim((string) $item['content']),
            );
        }

        if ($results === []) {
            throw new RuntimeException('Tidak ada draf artikel valid yang dihasilkan AI.');
        }

        return $results;
    }
}