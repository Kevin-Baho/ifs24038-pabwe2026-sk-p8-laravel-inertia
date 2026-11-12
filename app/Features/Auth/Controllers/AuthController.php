<?php

namespace App\Features\Auth\Controllers;

use App\Features\Auth\Actions\LoginUserAction;
use App\Features\Auth\Actions\RegisterUserAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController
{
    public function createLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function storeLogin(Request $request, LoginUserAction $action): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'username.required' => 'Username atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (! $action->execute($credentials['username'], $credentials['password'], (bool) ($credentials['remember'] ?? false))) {
            throw ValidationException::withMessages([
                'username' => 'Username/email atau password yang Anda masukkan tidak sesuai.',
            ]);
        }

        return Auth::user()->isAdmin()
            ? redirect()->route('admin.users')
            : redirect()->intended(route('dashboard.posts.index'));
    }

    public function createRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function storeRegister(Request $request, RegisterUserAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::login($action->execute([...$validated, 'role' => 'member']));
        $request->session()->regenerate();

        return redirect()->route('dashboard.posts.index')->with('success', 'Selamat datang di BlogVerse Pro!');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
