<?php

namespace App\Features\Post\Contracts;

use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PostRepositoryInterface
{
    /**
     * Artikel milik satu user (member). Filter: search, status (published|draft), category.
     *
     * @param array{search?: ?string, status?: ?string, category?: ?string} $filters
     */
    public function paginateForUser(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Semua artikel seluruh user (monitoring admin), opsional dibatasi satu user.
     *
     * @param array{search?: ?string, status?: ?string, category?: ?string} $filters
     */
    public function paginateAll(array $filters = [], int $perPage = 10, ?int $userId = null): LengthAwarePaginator;

    /** @return Collection<int, Post> */
    public function allForUser(int $userId): Collection;

    /** @return Collection<int, Post> */
    public function allWithAuthor(?int $userId = null): Collection;

    /**
     * Artikel terbit untuk publik.
     *
     * @param array{search?: ?string, category?: ?string} $filters
     */
    public function paginatePublished(array $filters = [], int $perPage = 9): LengthAwarePaginator;

    /** @return Collection<int, Post> */
    public function latestPublished(int $limit = 6, ?int $exceptId = null): Collection;

    public function findPublishedBySlug(string $slug): ?Post;

    public function find(int $id): ?Post;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Post;

    /** @param array<string, mixed> $attributes */
    public function update(Post $post, array $attributes): bool;

    public function delete(Post $post): bool;

    /** @return array{total: int, published: int, draft: int} */
    public function statsForUser(int $userId): array;

    /** @return array{total: int, published: int, draft: int} */
    public function statsGlobal(?int $userId = null): array;

    /** @return array<string, int> kategori => jumlah artikel terbit */
    public function publishedCountByCategory(): array;
}
