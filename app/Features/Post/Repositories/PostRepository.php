<?php

namespace App\Features\Post\Repositories;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PostRepository implements PostRepositoryInterface
{
    public function paginateForUser(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Post::query()->where('user_id', $userId);

        return $this->applyFilters($query, $filters)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateAll(array $filters = [], int $perPage = 10, ?int $userId = null): LengthAwarePaginator
    {
        $query = Post::query()
            ->with('user:id,name,username')
            ->when($userId, fn (Builder $q) => $q->where('user_id', $userId));

        return $this->applyFilters($query, $filters)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allForUser(int $userId): Collection
    {
        return Post::query()->where('user_id', $userId)->latest('id')->get();
    }

    public function allWithAuthor(?int $userId = null): Collection
    {
        return Post::query()
            ->with('user:id,name,username')
            ->when($userId, fn (Builder $q) => $q->where('user_id', $userId))
            ->latest('id')
            ->get();
    }

    public function paginatePublished(array $filters = [], int $perPage = 9): LengthAwarePaginator
    {
        $query = Post::query()->published()->with('user:id,name,username,profile_photo_path');

        return $this->applyFilters($query, [
            'search' => $filters['search'] ?? null,
            'category' => $filters['category'] ?? null,
        ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function latestPublished(int $limit = 6, ?int $exceptId = null): Collection
    {
        return Post::query()
            ->published()
            ->with('user:id,name,username,profile_photo_path')
            ->when($exceptId, fn (Builder $q) => $q->where('id', '!=', $exceptId))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function findPublishedBySlug(string $slug): ?Post
    {
        return Post::query()
            ->published()
            ->with('user:id,name,username,profile_photo_path')
            ->where('slug', $slug)
            ->first();
    }

    public function find(int $id): ?Post
    {
        return Post::find($id);
    }

    public function create(array $attributes): Post
    {
        return Post::create($attributes);
    }

    public function update(Post $post, array $attributes): bool
    {
        return $post->update($attributes);
    }

    public function delete(Post $post): bool
    {
        return (bool) $post->delete();
    }

    public function statsForUser(int $userId): array
    {
        return $this->stats(Post::query()->where('user_id', $userId));
    }

    public function statsGlobal(?int $userId = null): array
    {
        return $this->stats(Post::query()->when($userId, fn (Builder $q) => $q->where('user_id', $userId)));
    }

    public function publishedCountByCategory(): array
    {
        return Post::query()
            ->published()
            ->selectRaw('category, COUNT(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * Dua hitungan sederhana agar kompatibel PostgreSQL dan SQLite.
     *
     * @return array{total: int, published: int, draft: int}
     */
    private function stats(Builder $query): array
    {
        $total = (clone $query)->count();
        $published = (clone $query)->where('is_published', true)->count();

        return ['total' => $total, 'published' => $published, 'draft' => $total - $published];
    }

    /** @param array{search?: ?string, status?: ?string, category?: ?string} $filters */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $category = $filters['category'] ?? null;

        if ($search !== '') {
            $needle = '%'.mb_strtolower(addcslashes($search, '%_\\')).'%';
            $query->where(function (Builder $q) use ($needle) {
                $q->whereRaw('LOWER(title) LIKE ?', [$needle])
                  ->orWhereRaw("LOWER(COALESCE(excerpt, '')) LIKE ?", [$needle]);
            });
        }

        if ($status === 'published') {
            $query->where('is_published', true);
        } elseif ($status === 'draft') {
            $query->where('is_published', false);
        }

        if (! empty($category)) {
            $query->where('category', $category);
        }

        return $query;
    }
}
