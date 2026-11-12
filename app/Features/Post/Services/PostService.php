<?php

namespace App\Features\Post\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(protected PostRepositoryInterface $posts) {}

    // ---------------------------------------------------------------- Member

    /** @param array{search?: ?string, status?: ?string, category?: ?string} $filters */
    public function getPostsForUser(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->posts->paginateForUser($userId, $filters, $perPage);
    }

    /** @return array{total: int, published: int, draft: int} */
    public function getStatsForUser(int $userId): array
    {
        return $this->posts->statsForUser($userId);
    }

    /** @param array<string, mixed> $data */
    public function create(int $userId, array $data, ?UploadedFile $cover = null): Post
    {
        $isPublished = (bool) ($data['is_published'] ?? false);

        return $this->posts->create([
            'user_id' => $userId,
            'title' => $data['title'],
            'category' => $this->normalizeCategory($data['category'] ?? null),
            'excerpt' => $this->resolveExcerpt($data['excerpt'] ?? null, (string) $data['content']),
            'content' => $data['content'],
            'cover_image_path' => $cover?->store('post-covers', config('filesystems.media_disk', 'public')),
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
        ]);
    }

    /**
     * Slug sengaja tidak berubah saat judul diedit agar tautan lama tetap valid.
     *
     * @param array<string, mixed> $data
     */
    public function update(Post $post, array $data, ?UploadedFile $cover = null, bool $removeCover = false): bool
    {
        $isPublished = (bool) ($data['is_published'] ?? $post->is_published);

        $attributes = [
            'title' => $data['title'],
            'category' => $this->normalizeCategory($data['category'] ?? $post->category->value),
            'excerpt' => $this->resolveExcerpt($data['excerpt'] ?? null, (string) $data['content']),
            'content' => $data['content'],
            ...$this->publishState($isPublished, $post->published_at),
        ];

        if ($cover) {
            $this->deleteCoverFile($post->cover_image_path);
            $attributes['cover_image_path'] = $cover->store('post-covers', config('filesystems.media_disk', 'public'));
        } elseif ($removeCover) {
            $this->deleteCoverFile($post->cover_image_path);
            $attributes['cover_image_path'] = null;
        }

        return $this->posts->update($post, $attributes);
    }

    public function delete(Post $post): bool
    {
        $this->deleteCoverFile($post->cover_image_path);

        return $this->posts->delete($post);
    }

    /** Toggle cepat Draf <-> Terbit. */
    public function togglePublish(Post $post): bool
    {
        return $this->posts->update($post, $this->publishState(! $post->is_published, $post->published_at));
    }

    /**
     * Simpan draf hasil AI (selalu berstatus draf).
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function createDrafts(int $userId, array $items): int
    {
        $count = 0;
        foreach ($items as $item) {
            if (blank($item['title'] ?? null) || blank($item['content'] ?? null)) {
                continue;
            }
            $this->create($userId, [
                'title' => $item['title'],
                'category' => $item['category'] ?? null,
                'excerpt' => $item['excerpt'] ?? null,
                'content' => $item['content'],
                'is_published' => false,
            ]);
            $count++;
        }

        return $count;
    }

    // ---------------------------------------------------------------- Publik

    /** @param array{search?: ?string, category?: ?string} $filters */
    public function getPublished(array $filters = [], int $perPage = 9): LengthAwarePaginator
    {
        return $this->posts->paginatePublished($filters, $perPage);
    }

    /** @return Collection<int, Post> */
    public function getLatestPublished(int $limit = 6, ?int $exceptId = null): Collection
    {
        return $this->posts->latestPublished($limit, $exceptId);
    }

    public function getPublishedBySlugOrFail(string $slug): Post
    {
        return $this->posts->findPublishedBySlug($slug) ?? abort(404, 'Artikel tidak ditemukan.');
    }

    /** @return array<string, int> */
    public function getPublishedCountByCategory(): array
    {
        return $this->posts->publishedCountByCategory();
    }

    // --------------------------------------------------------------- Helpers

    public function makeExcerpt(string $markdown, int $limit = 180): string
    {
        $text = preg_replace('/```.*?```/s', ' ', $markdown) ?? '';
        $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/', ' ', $text) ?? '';
        $text = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $text) ?? '';
        $text = preg_replace('/^[#>\-\*\+\s]+/m', '', $text) ?? '';
        $text = preg_replace('/[*_`~|]+/', '', strip_tags($text)) ?? '';
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return Str::limit($text, $limit);
    }

    public function deleteCoverFile(?string $path): void
    {
        if ($path) {
            Storage::disk(config('filesystems.media_disk', 'public'))->delete($path);
        }
    }

    private function resolveExcerpt(?string $excerpt, string $content): string
    {
        $excerpt = trim((string) $excerpt);

        return $excerpt !== '' ? $excerpt : $this->makeExcerpt($content);
    }

    private function normalizeCategory(?string $category): string
    {
        return PostCategory::fromLoose($category)->value;
    }

    /** @return array{is_published: bool, published_at: ?Carbon} */
    private function publishState(bool $isPublished, ?Carbon $currentPublishedAt): array
    {
        return [
            'is_published' => $isPublished,
            'published_at' => $isPublished ? ($currentPublishedAt ?? now()) : null,
        ];
    }
}
