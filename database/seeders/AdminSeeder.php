<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seeder akun admin awal untuk produksi.
     * Membaca identitas akun admin dari variabel environment (ADMIN_USERNAME, ADMIN_EMAIL, ADMIN_PASSWORD).
     */
    public function run(): void
    {
        $email = getenv('ADMIN_EMAIL') ?: env('ADMIN_EMAIL') ?: ($_ENV['ADMIN_EMAIL'] ?? null);
        $username = getenv('ADMIN_USERNAME') ?: env('ADMIN_USERNAME') ?: ($_ENV['ADMIN_USERNAME'] ?? null);
        $password = getenv('ADMIN_PASSWORD') ?: env('ADMIN_PASSWORD') ?: ($_ENV['ADMIN_PASSWORD'] ?? null);
        $name = getenv('ADMIN_NAME') ?: env('ADMIN_NAME') ?: ($_ENV['ADMIN_NAME'] ?? 'Administrator');

        if (empty($email) || empty($username) || empty($password)) {
            $this->command?->warn('ADMIN_EMAIL, ADMIN_USERNAME, atau ADMIN_PASSWORD belum diset di environment. Melewati AdminSeeder.');

            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => $username,
                'password' => Hash::make($password),
                'role' => 'admin',
            ]
        );

        $this->command?->info("Akun admin '{$user->username}' ({$user->email}) berhasil disiapkan.");
    }
}
