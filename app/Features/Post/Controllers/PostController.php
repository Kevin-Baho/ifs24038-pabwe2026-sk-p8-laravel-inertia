<?php

namespace App\Features\Post\Controllers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\OpenAiPostGeneratorService;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Requests\PostRequest;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Features\Post\Support\PostTransformer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Tingkat 2: manajemen artikel milik member sendiri (/dashboard/posts). */
class PostController
{
    public function index(Request $request, PostService $service): Response
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'category' => (string) $request->query('category', ''),
        ];

        $posts = $service->getPostsForUser((int) Auth::id(), $filters, 10)
            ->through(fn (Post $post) => PostTransformer::row($post));

        // `?edit={id}` (dari halaman Detail) membuka modal Ubah untuk artikel milik sendiri.
        $editing = null;
        if ($editId = (int) $request->query('edit')) {
            $candidate = Post::find($editId);
            if ($candidate && Gate::allows('update', $candidate)) {
                $editing = PostTransformer::row($candidate);
            }
        }

        return Inertia::render('Post/Index', [
            'editing' => $editing,
            'posts' => $posts,
            'filters' => $filters,
            'stats' => $service->getStatsForUser((int) Auth::id()),
            'categories' => PostCategory::options(),
            'aiMaxCount' => OpenAiPostGeneratorService::MAX_COUNT,
        ]);
    }

    public function store(PostRequest $request, PostService $service): RedirectResponse
    {
        Gate::authorize('create', Post::class);

        $service->create((int) Auth::id(), $request->validated(), $request->file('cover'));

        return back()->with('success', 'Artikel baru berhasil ditambahkan.');
    }

    public function show(Post $post): Response
    {
        Gate::authorize('view', $post);

        $post->loadMissing('user:id,name,username,profile_photo_path');

        return Inertia::render('Post/Show', [
            'post' => PostTransformer::detail($post),
            'publicUrl' => $post->is_published ? route('articles.show', $post->slug) : null,
        ]);
    }

    public function update(PostRequest $request, Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $service->update(
            $post,
            $request->validated(),
            $request->file('cover'),
            $request->boolean('remove_cover'),
        );

        return back()->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $service->delete($post);

        return redirect()->route('dashboard.posts.index')->with('success', 'Artikel berhasil dihapus.');
    }

    public function togglePublish(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $service->togglePublish($post);

        return back()->with('success', $post->fresh()->is_published
            ? 'Artikel dipublikasikan.'
            : 'Artikel dikembalikan ke draf.');
    }

    public function exportExcel(PostService $service, PostExcelService $excel): StreamedResponse
    {
        $posts = Auth::user()->posts()->latest('id')->get();

        return $excel->exportToResponse($posts, 'artikel-'.Auth::user()->username.'-'.now()->format('Ymd-His').'.xlsx');
    }

    public function importExcel(Request $request, PostExcelService $excel): RedirectResponse
    {
        $request->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:4096'],
        ], [
            'importFile.max' => 'Ukuran berkas impor maksimal 4 MB (batas payload Vercel 4.5 MB).',
        ]);

        $result = $excel->import($request->file('importFile')->getRealPath(), (int) Auth::id());

        $message = "Berhasil mengimpor {$result['imported']} artikel (sebagai draf atau sesuai kolom Status).";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} baris dilewati karena judul kosong.";
        }

        return back()->with('success', $message);
    }

    /** Hasilkan draf lewat AI; hasilnya dikirim kembali sebagai flash `aiGeneratedItems` untuk dipratinjau. */
    public function generateAi(Request $request, AiClientInterface $ai): RedirectResponse
    {
        $validated = $request->validate([
            'topic' => ['required', 'string', 'min:3', 'max:500'],
            'count' => ['required', 'integer', 'min:1', 'max:'.OpenAiPostGeneratorService::MAX_COUNT],
            'category' => ['nullable', Rule::in(PostCategory::values())],
        ], [
            'topic.required' => 'Topik artikel wajib diisi (minimal 3 karakter).',
            'topic.min' => 'Topik artikel minimal 3 karakter.',
            'count.max' => 'Maksimal '.OpenAiPostGeneratorService::MAX_COUNT.' draf per permintaan.',
        ]);

        try {
            $items = $ai->generatePosts($validated['topic'], (int) $validated['count'], $validated['category'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Gagal menghasilkan draf dengan AI: '.$e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Layanan AI tidak dapat dihubungi. Coba lagi beberapa saat.');
        }

        return back()->with('aiGeneratedItems', array_map(fn ($item) => $item->toArray(), $items));
    }

    public function saveAi(Request $request, PostService $service): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:'.OpenAiPostGeneratorService::MAX_COUNT],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string'],
            'items.*.excerpt' => ['nullable', 'string', 'max:500'],
            'items.*.content' => ['required', 'string'],
        ]);

        $count = $service->createDrafts((int) Auth::id(), $validated['items']);

        return back()->with('success', "{$count} draf artikel dari AI berhasil disimpan.");
    }
}
