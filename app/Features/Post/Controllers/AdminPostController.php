<?php

namespace App\Features\Post\Controllers;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Support\PostTransformer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Tingkat 3: monitoring seluruh artikel (READ-ONLY, tidak ada ubah/hapus). */
class AdminPostController
{
    public function index(Request $request, AdminUserService $admin): Response
    {
        $filters = $this->filters($request);

        return Inertia::render('Admin/Posts', [
            'posts' => $admin->getAllPosts($filters, 10)->through(fn (Post $p) => PostTransformer::adminRow($p)),
            'filters' => $filters,
            'stats' => $admin->getSystemStats()['posts'],
            'categories' => PostCategory::options(),
        ]);
    }

    public function show(Post $post): Response
    {
        $post->loadMissing('user:id,name,username,profile_photo_path');

        return Inertia::render('Post/Show', [
            'post' => PostTransformer::detail($post),
            'publicUrl' => $post->is_published ? route('articles.show', $post->slug) : null,
            'readOnly' => true,
            'author' => $post->user ? [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'username' => $post->user->username,
            ] : null,
        ]);
    }

    /** Ekspor seluruh artikel sistem ke Excel. */
    public function export(AdminUserService $admin, PostExcelService $excel): StreamedResponse
    {
        return $excel->exportToResponse(
            $admin->getAllPostsForExport(),
            'blogverse-semua-artikel-'.now()->format('Ymd-His').'.xlsx',
            withAuthor: true,
        );
    }

    /** @return array{search: string, status: string, category: string} */
    private function filters(Request $request): array
    {
        return [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'category' => (string) $request->query('category', ''),
        ];
    }
}
