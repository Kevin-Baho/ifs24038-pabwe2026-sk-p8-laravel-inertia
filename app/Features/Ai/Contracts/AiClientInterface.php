<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedPostItem;

interface AiClientInterface
{
    /**
     * Hasilkan beberapa draf artikel (Markdown) dari sebuah topik.
     *
     * @return array<int, GeneratedPostItem>
     */
    public function generatePosts(string $topic, int $count = 1, ?string $category = null): array;
}
