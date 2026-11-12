<?php

use App\Features\Admin\Controllers\AdminUserController;
use App\Features\Auth\Controllers\AuthController;
use App\Features\Post\Controllers\AdminPostController;
use App\Features\Post\Controllers\PostController;
use App\Features\Post\Controllers\PublicPostController;
use App\Features\Profile\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check (Vercel container / uptime monitoring)
|--------------------------------------------------------------------------
*/
Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');

/*
|--------------------------------------------------------------------------
| Tingkat 1 — Publik / Guest (tanpa sidebar)
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicPostController::class, 'home'])->name('home');
Route::get('/articles', [PublicPostController::class, 'index'])->name('articles.index');
Route::get('/articles/{slug}', [PublicPostController::class, 'show'])->name('articles.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'createLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'storeLogin'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'createRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'storeRegister'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    // Profil (member & admin)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');

    /*
    |----------------------------------------------------------------------
    | Tingkat 2 — Member / Penulis (/dashboard/posts)
    |----------------------------------------------------------------------
    */
    Route::prefix('dashboard')->name('dashboard.')->middleware('role:member')->group(function () {
        Route::redirect('/', '/dashboard/posts');

        // Rute statis harus berada sebelum rute berparameter {post}.
        Route::get('/posts/export', [PostController::class, 'exportExcel'])->name('posts.export');
        Route::post('/posts/import', [PostController::class, 'importExcel'])->name('posts.import');
        Route::post('/posts/ai-generate', [PostController::class, 'generateAi'])
            ->middleware('throttle:ai-generate')->name('posts.ai.generate');
        Route::post('/posts/ai-save', [PostController::class, 'saveAi'])->name('posts.ai.save');

        Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}', [PostController::class, 'show'])->whereNumber('post')->name('posts.show');
        Route::put('/posts/{post}', [PostController::class, 'update'])->whereNumber('post')->name('posts.update');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->whereNumber('post')->name('posts.destroy');
        Route::patch('/posts/{post}/toggle-publish', [PostController::class, 'togglePublish'])
            ->whereNumber('post')->name('posts.toggle');
    });

    /*
    |----------------------------------------------------------------------
    | Tingkat 3 — Admin (/admin/...)
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::get('/users/{user}/posts', [AdminUserController::class, 'posts'])->name('users.posts');
        Route::get('/users/{user}/posts/export', [AdminUserController::class, 'exportUserPosts'])->name('users.posts.export');

        Route::get('/posts', [AdminPostController::class, 'index'])->name('posts');
        Route::get('/posts/export', [AdminPostController::class, 'export'])->name('posts.export');
        Route::get('/posts/{post}', [AdminPostController::class, 'show'])->whereNumber('post')->name('posts.show');
    });
});
