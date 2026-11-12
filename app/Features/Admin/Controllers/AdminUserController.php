<?php

namespace App\Features\Admin\Controllers;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Support\PostTransformer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Tingkat 3: kelola pengguna dan pantau artikel per pengguna. */
class AdminUserController
{
    public function index(Request $request, AdminUserService $admin): Response
    {
        $search = (string) $request->query('search', '');

        return Inertia::render('Admin/Users', [
            'users' => $admin->getUsersList($search ?: null, 10),
            'filters' => ['search' => $search],
            'stats' => $admin->getSystemStats(),
        ]);
    }

    public function updateRole(Request $request, User $user, AdminUserService $admin): RedirectResponse
    {
        $validated = $request->validate(['role' => ['required', Rule::in(['admin', 'member'])]]);

        try {
            $admin->changeRole(Auth::user(), $user, $validated['role']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Peran {$user->name} diubah menjadi {$validated['role']}.");
    }

    public function destroy(User $user, AdminUserService $admin): RedirectResponse
    {
        try {
            $admin->deleteUser(Auth::user(), $user);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.users')->with('success', "Pengguna {$user->name} berhasil dihapus.");
    }

    public function posts(Request $request, User $user, AdminUserService $admin): Response
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'category' => (string) $request->query('category', ''),
        ];

        return Inertia::render('Admin/UserPosts', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'posts' => $admin->getUserPosts($user->id, $filters, 10)
                ->through(fn (Post $p) => PostTransformer::adminRow($p)),
            'filters' => $filters,
            'stats' => $admin->getUserPostStats($user->id),
            'categories' => PostCategory::options(),
        ]);
    }

    public function exportUserPosts(User $user, AdminUserService $admin, PostExcelService $excel): StreamedResponse
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $user->username);

        return $excel->exportToResponse(
            $admin->getUserPostsForExport($user->id),
            "blogverse-artikel-{$safe}-".now()->format('Ymd-His').'.xlsx',
            withAuthor: true,
        );
    }
}
