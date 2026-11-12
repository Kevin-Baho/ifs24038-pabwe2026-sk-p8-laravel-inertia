<?php

namespace App\Features\Auth\Actions;

use App\Models\User;

class RegisterUserAction
{
    /** @param array{name: string, username: string, email: string, password: string, role?: string} $data */
    public function execute(array $data): User
    {
        // Password di-hash otomatis oleh cast `hashed` pada model User.
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'] ?? 'member',
        ]);
    }
}
