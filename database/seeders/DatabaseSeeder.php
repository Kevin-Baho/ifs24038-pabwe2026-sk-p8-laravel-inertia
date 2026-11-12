<?php

namespace Database\Seeders;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun demo untuk pengembangan lokal. GANTI password sebelum deploy ke server publik.
     */
    public function run(): void
    {
        // Di lingkungan produksi, JANGAN pernah menjalankan seeder akun demo (admin/password).
        if (app()->environment('production')) {
            $this->call(AdminSeeder::class);

            return;
        }

        User::firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Administrator',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $member = User::firstOrCreate(['email' => 'member@example.com'], [
            'name' => 'Member Demo',
            'username' => 'member',
            'password' => 'password',
            'role' => 'member',
        ]);

        $samples = [
            ['Mengenal Arsitektur Feature-First di Laravel', 'Teknologi', true,
                "## Mengapa Feature-First?\n\nAlih-alih mengelompokkan berkas berdasarkan jenis (controller, model, service), kita mengelompokkannya berdasarkan **fitur**.\n\n- Perubahan satu fitur cukup menyentuh satu folder\n- Batas tanggung jawab lebih jelas\n- Mudah diuji secara terpisah\n\n> Struktur yang baik membuat tim bergerak lebih cepat."],
            ['Belajar Efektif di Era Informasi Berlebih', 'Pendidikan', true,
                "## Fokus dulu, luaskan kemudian\n\nBelajar tidak harus cepat; yang penting konsisten. Mulailah dengan satu sumber yang andal, catat dengan kata-kata sendiri, lalu uji pemahaman lewat latihan."],
            ['Rutinitas Pagi yang Realistis', 'Gaya Hidup', true,
                "Rutinitas yang sulit dijalani akan ditinggalkan. Pilih dua kebiasaan kecil dan jalani selama dua minggu sebelum menambah yang lain."],
            ['Membangun Bisnis Kecil dari Nol', 'Bisnis', true,
                "## Validasi sebelum investasi\n\nTemui calon pelanggan, tawarkan versi paling sederhana dari produk Anda, lalu dengarkan."],
            ['Mengapa Kita Perlu Lebih Banyak Membaca', 'Opini', false,
                "Draf opini tentang literasi yang belum selesai ditulis."],
        ];

        foreach ($samples as [$title, $category, $published, $content]) {
            Post::firstOrCreate(['user_id' => $member->id, 'title' => $title], [
                'category' => $category,
                'excerpt' => null,
                'content' => $content,
                'is_published' => $published,
                'published_at' => $published ? now()->subDays(random_int(0, 10)) : null,
            ]);
        }

        $service = app(\App\Features\Post\Services\PostService::class);
        Post::whereNull('excerpt')->each(fn (Post $p) => $p->update(['excerpt' => $service->makeExcerpt($p->content)]));
    }
}
