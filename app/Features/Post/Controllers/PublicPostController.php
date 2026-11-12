<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostService;
use App\Features\Post\Support\PostTransformer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Tingkat 1: halaman publik tanpa login (`/`, `/articles`, `/articles/{slug}`). */
class PublicPostController
{
    public function home(PostService $service): Response
    {
        $counts = $service->getPublishedCountByCategory();

        return Inertia::render('Public/Home', [
            'latest' => $service->getLatestPublished(7)->map(fn (Post $p) => PostTransformer::card($p))->values(),
            'categories' => collect(PostCategory::options())
                ->map(fn (array $c) => $c + ['count' => $counts[$c['value']] ?? 0])
                ->values(),
        ]);
    }

    public function index(Request $request, PostService $service): Response
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'category' => (string) $request->query('category', ''),
        ];

        return Inertia::render('Public/Index', [
            'posts' => $service->getPublished($filters, 9)->through(fn (Post $p) => PostTransformer::card($p)),
            'filters' => $filters,
            'categories' => PostCategory::options(),
        ]);
    }

    public function show(string $slug, PostService $service): Response
    {
        $post = $service->getPublishedBySlugOrFail($slug);

        return Inertia::render('Public/Show', [
            'post' => PostTransformer::detail($post),
            'related' => $service->getLatestPublished(3, $post->id)->map(fn (Post $p) => PostTransformer::card($p))->values(),
        ]);
    }
}
