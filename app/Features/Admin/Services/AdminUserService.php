<?php

namespace App\Features\Admin\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostService;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AdminUserService
{
    public function __construct(
        protected PostRepositoryInterface $posts,
        protected PostService $postService,
    ) {}

    public function getUsersList(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = User::query()->withCount([
            'posts',
            'posts as published_posts_count' => fn ($q) => $q->where('is_published', true),
        ]);

        $search = trim((string) $search);
        if ($search !== '') {
            $needle = '%'.mb_strtolower(addcslashes($search, '%_\\')).'%';
            $query->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(name) LIKE ?', [$needle])
                  ->orWhereRaw('LOWER(username) LIKE ?', [$needle])
                  ->orWhereRaw('LOWER(email) LIKE ?', [$needle]);
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /** @return array{users: array{total: int, admins: int, members: int}, posts: array{total: int, published: int, draft: int}} */
    public function getSystemStats(): array
    {
        return [
            'users' => [
                'total' => User::count(),
                'admins' => User::where('role', 'admin')->count(),
                'members' => User::where('role', 'member')->count(),
            ],
            'posts' => $this->posts->statsGlobal(),
        ];
    }

    /** @param array{search?: ?string, status?: ?string, category?: ?string} $filters */
    public function getAllPosts(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->posts->paginateAll($filters, $perPage);
    }

    /** @param array{search?: ?string, status?: ?string, category?: ?string} $filters */
    public function getUserPosts(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->posts->paginateAll($filters, $perPage, $userId);
    }

    /** @return array{total: int, published: int, draft: int} */
    public function getUserPostStats(int $userId): array
    {
        return $this->posts->statsGlobal($userId);
    }

    /** @return Collection<int, Post> */
    public function getAllPostsForExport(): Collection
    {
        return $this->posts->allWithAuthor();
    }

    /** @return Collection<int, Post> */
    public function getUserPostsForExport(int $userId): Collection
    {
        return $this->posts->allWithAuthor($userId);
    }

    public function changeRole(User $actor, User $target, string $role): void
    {
        if ($actor->is($target)) {
            throw new RuntimeException('Anda tidak dapat mengubah peran akun Anda sendiri.');
        }

        $target->update(['role' => $role]);
    }

    /** Hapus pengguna beserta berkas cover artikel dan foto profilnya (baris artikel ikut terhapus via cascade). */
    public function deleteUser(User $actor, User $target): void
    {
        if ($actor->is($target)) {
            throw new RuntimeException('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $target->posts()->select(['id', 'cover_image_path'])->cursor()
            ->each(fn (Post $post) => $this->postService->deleteCoverFile($post->cover_image_path));

        if ($target->profile_photo_path) {
            Storage::disk(config('filesystems.media_disk', 'public'))->delete($target->profile_photo_path);
        }

        DB::transaction(fn () => $target->delete());
    }
}
