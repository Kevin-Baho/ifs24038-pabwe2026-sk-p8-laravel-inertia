# BlogVerse Pro (ifs24038-pabwe2026-sk-p8-laravel-inertia)

Studi kasus PABWE 2026 Praktikum 8: platform blog tiga tingkatan dengan **Laravel 13, Inertia.js, React, PostgreSQL, PhpSpreadsheet, dan OpenAI**. Arsitektur Clean Architecture dan Feature-First.

## Menjalankan
```bash
composer install
cp .env.example .env            # lalu isi DB_PASSWORD dan OPENAI_KEY
php artisan key:generate
# buat database PostgreSQL: CREATE DATABASE pabwe_p8_blog;
php artisan migrate --seed
php artisan storage:link        # agar cover dan foto profil tampil
npm install
npm run dev                     # atau: npm run build
php artisan serve
```
Akun demo (seeder, ganti sebelum deploy): `admin` / `password` dan `member` / `password`.

## Tiga tingkatan
| Tingkat | URL | Keterangan |
|---|---|---|
| 1 Publik | `/`, `/articles`, `/articles/{slug}` | Sticky top navbar (Masuk/Daftar), footer editorial, baca Markdown (TOAST UI Viewer), kartu ajakan komunitas |
| 2 Member | `/dashboard/posts` | CRUD artikel sendiri, statistik, list view + menu kebab, modal full screen, AI Generator, Ekspor/Impor Excel |
| 3 Admin | `/admin/users`, `/admin/posts`, `/admin/users/{id}/posts` | Kelola pengguna, monitoring artikel (read-only), ekspor Excel sistem dan per pengguna |

Form login memakai `id="login-username"`, `id="login-password"`, `id="login-submit"`.

## Struktur
```
app/Features/
  Admin/   Controllers, Services
  Ai/      Contracts, DTO, Services/OpenAiPostGeneratorService
  Auth/    Actions, Controllers
  Post/    Contracts, Controllers (AdminPost, Post, PublicPost), Enums/PostCategory,
           Models/Post, Policies, Repositories, Requests, Services (PostService, PostExcelService), Support
  Profile/ Controllers, Services
resources/js/  Layouts/AppLayout.jsx, Pages/{Admin,Auth,Post,Profile,Public}, Components
```

## Catatan
- Konfigurasi AI di `.env`: `OPENAI_ENDPOINT`, `OPENAI_KEY`, `OPENAI_MODEL`. Input angka AI dibatasi 1-5 draf; hasil disimpan sebagai draf.
- Excel: kolom dikenali dari header, jadi file hasil ekspor dapat diimpor kembali. Semua sel ditulis sebagai teks (mencegah injeksi formula).
- Slug dibuat otomatis, unik, dan tidak berubah saat judul diedit.
- Admin tidak dapat mengubah atau menghapus artikel pengguna (PostPolicy).
- Tes: `composer test` (SQLite in-memory, butuh ekstensi `pdo_sqlite`).
