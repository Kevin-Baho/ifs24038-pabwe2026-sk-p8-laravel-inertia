# DEPLOY.md — Panduan Deploy BlogVerse Pro ke Vercel

> Dokumen ini berisi langkah lengkap untuk men-deploy aplikasi BlogVerse Pro ke
> Vercel menggunakan FrankenPHP (Container Runtime), database TiDB Cloud Serverless
> (MySQL 8 compatible), dan object storage Cloudflare R2.

---

## Daftar Isi

1. [Prasyarat](#1-prasyarat)
2. [Environment Variables Vercel](#2-environment-variables-vercel)
3. [Persiapan Database Online (TiDB Cloud)](#3-persiapan-database-online-tidb-cloud)
4. [Persiapan Object Storage (Cloudflare R2)](#4-persiapan-object-storage-cloudflare-r2)
5. [Langkah Deploy ke Vercel](#5-langkah-deploy-ke-vercel)
6. [Menjalankan Migrasi & Seeder Admin](#6-menjalankan-migrasi--seeder-admin)
7. [Menyambungkan HeidiSQL ke Database Online](#7-menyambungkan-heidisql-ke-database-online)
8. [Catatan Runtime: FrankenPHP vs Fallback](#8-catatan-runtime-frankenphp-vs-fallback)
9. [Batas Payload & Timeout Vercel](#9-batas-payload--timeout-vercel)
10. [Checklist Pasca-Deploy](#10-checklist-pasca-deploy)
11. [Troubleshooting](#11-troubleshooting)

---

## 1. Prasyarat

| Alat | Versi minimum | Catatan |
|------|--------------|---------|
| PHP | 8.3+ | Lokal untuk jalankan migrasi |
| Composer | 2.x | Untuk install dependensi |
| Node.js | 20+ | Untuk build frontend |
| Vercel CLI | terbaru | `npm i -g vercel` |
| Akun Vercel | — | Daftar di vercel.com |
| Akun TiDB Cloud | — | tidbcloud.com (ada tier gratis) |
| Akun Cloudflare | — | cloudflare.com (R2 ada tier gratis) |

> **Penting:** Jangan pernah meng-commit file `.env` ke repository. Hanya
> `.env.example` yang boleh di-commit.

---

## 2. Environment Variables Vercel

Tambahkan semua variabel berikut di **Vercel Dashboard → Project → Settings → Environment Variables**.
Pilih environment: **Production** (dan Preview jika perlu).

### 2.1 Aplikasi

| Variable | Nilai Contoh | Keterangan |
|----------|-------------|-----------|
| `APP_NAME` | `BlogVerse Pro` | Nama aplikasi |
| `APP_ENV` | `production` | **Wajib** `production` |
| `APP_KEY` | `base64:...` | Generate baru: `php artisan key:generate --show` — **jangan pakai key lokal!** |
| `APP_URL` | `https://blogverse.vercel.app` | URL deployment Vercel (tanpa trailing slash) |
| `APP_DEBUG` | `false` | **Wajib** `false` di produksi |
| `APP_TIMEZONE` | `Asia/Jakarta` | Sesuaikan zona waktu |
| `APP_LOCALE` | `id` | Bahasa default |
| `BCRYPT_ROUNDS` | `12` | Kekuatan hash password |

### 2.2 Logging

| Variable | Nilai | Keterangan |
|----------|-------|-----------|
| `LOG_CHANNEL` | `stderr` | Log ke stderr agar terbaca di Vercel logs |
| `LOG_LEVEL` | `error` | Hanya log error di produksi |

### 2.3 Keamanan & Proxy

| Variable | Nilai | Keterangan |
|----------|-------|-----------|
| `FORCE_HTTPS` | `true` | Paksa URL menggunakan HTTPS |

### 2.4 Database (TiDB Cloud Serverless)

| Variable | Nilai Contoh | Keterangan |
|----------|-------------|-----------|
| `DB_CONNECTION` | `mysql` | Driver database |
| `DB_HOST` | `gateway01.ap-southeast-1.prod.aws.tidbcloud.com` | Host dari TiDB Cloud dashboard |
| `DB_PORT` | `4000` | Port default TiDB Cloud |
| `DB_DATABASE` | `blogverse` | Nama database yang dibuat |
| `DB_USERNAME` | `xxxxxxxx.root` | Username dari TiDB Cloud |
| `DB_PASSWORD` | `...` | Password TiDB Cloud |

> **SSL TiDB Cloud:** TiDB Cloud Serverless menggunakan sistem CA bundle bawaan
> server — tidak memerlukan file CA terpisah. Biarkan `MYSQL_ATTR_SSL_CA` **kosong**
> (tidak perlu diisi). Koneksi SSL tetap aktif secara otomatis.
>
> Jika penyedia database Anda membutuhkan CA khusus, ada dua opsi:
> - `MYSQL_ATTR_SSL_CA` → isi dengan **path absolut** ke file `.pem` di server
> - `MYSQL_ATTR_SSL_CA_CONTENT` → isi dengan **isi PEM** lengkap (teks). Aplikasi
>   akan otomatis menyimpannya ke file sementara di `sys_get_temp_dir()` saat boot.

| Variable | Nilai | Keterangan |
|----------|-------|-----------|
| `MYSQL_ATTR_SSL_CA` | _(kosong untuk TiDB Cloud)_ | Path file CA jika diperlukan |
| `MYSQL_ATTR_SSL_CA_CONTENT` | _(kosong untuk TiDB Cloud)_ | Isi PEM CA jika penyedia mengharuskan |
| `MYSQL_ATTR_SSL_VERIFY_SERVER_CERT` | `true` | Verifikasi sertifikat server |

### 2.5 Session & Cache

| Variable | Nilai | Keterangan |
|----------|-------|-----------|
| `SESSION_DRIVER` | `database` | Simpan session di tabel `sessions` |
| `SESSION_LIFETIME` | `120` | Durasi session (menit) |
| `SESSION_ENCRYPT` | `false` | Enkripsi session (opsional) |
| `CACHE_STORE` | `database` | Gunakan tabel `cache` |
| `QUEUE_CONNECTION` | `sync` | Proses job secara sinkron (Vercel stateless) |

### 2.6 Object Storage (Cloudflare R2)

| Variable | Nilai Contoh | Keterangan |
|----------|-------------|-----------|
| `FILESYSTEM_DISK` | `local` | Disk default Laravel (biarkan local) |
| `MEDIA_DISK` | `r2` | Disk untuk cover artikel & foto profil |
| `AWS_ACCESS_KEY_ID` | `...` | R2 Access Key ID |
| `AWS_SECRET_ACCESS_KEY` | `...` | R2 Secret Access Key |
| `AWS_DEFAULT_REGION` | `auto` | Region R2 (selalu `auto`) |
| `AWS_BUCKET` | `blogverse-media` | Nama bucket R2 |
| `AWS_URL` | `https://pub-xxx.r2.dev` | URL publik bucket (atau custom domain) |
| `AWS_ENDPOINT` | `https://<account_id>.r2.cloudflarestorage.com` | Endpoint R2 API |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `false` | Jangan gunakan path-style untuk R2 |

> **Alternatif AWS S3:** Jika menggunakan AWS S3 biasa, `AWS_DEFAULT_REGION` diisi
> dengan region S3 (mis. `ap-southeast-1`), `AWS_ENDPOINT` dikosongkan, dan
> `AWS_URL` diisi dengan URL bucket S3 publik.

### 2.7 OpenAI / AI Generator

| Variable | Nilai Contoh | Keterangan |
|----------|-------------|-----------|
| `OPENAI_KEY` | `sk-...` | API key OpenAI |
| `OPENAI_ENDPOINT` | `https://api.openai.com/v1` | Base URL API (bisa diubah untuk proxy) |
| `OPENAI_MODEL` | `gpt-4o-mini` | Model yang digunakan |
| `AI_TIMEOUT` | `50` | Timeout request AI (detik) — max 60 di Vercel Hobby |

### 2.8 Seeder Admin Awal

| Variable | Nilai Contoh | Keterangan |
|----------|-------------|-----------|
| `ADMIN_NAME` | `Administrator` | Nama lengkap admin |
| `ADMIN_USERNAME` | `admin` | Username login admin |
| `ADMIN_EMAIL` | `admin@blogverse.com` | Email admin |
| `ADMIN_PASSWORD` | `P@ssw0rd123!` | Password admin (gunakan password kuat!) |

---

## 3. Persiapan Database Online (TiDB Cloud)

### 3.1 Membuat Cluster TiDB Cloud Serverless (Gratis)

1. Daftar/login di https://tidbcloud.com
2. Klik **Create Cluster** → pilih **Serverless** (tier gratis)
3. Pilih region terdekat (mis. **AWS / Asia Pacific - Singapore**)
4. Beri nama cluster, klik **Create**
5. Tunggu cluster selesai dibuat (~1-2 menit)

### 3.2 Mendapatkan Kredensial Koneksi

1. Klik cluster Anda → **Connect**
2. Pilih **Connect With: General** dan **Operating System: Windows**
3. Salin nilai:
   - **Host** (mis. `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`)
   - **Port** (biasanya `4000`)
   - **Username** (mis. `xxxxxxxx.root`)
   - **Password** (klik "Generate Password" atau gunakan yang sudah ada)
4. Buat database baru:
   ```sql
   CREATE DATABASE blogverse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

---

## 4. Persiapan Object Storage (Cloudflare R2)

### 4.1 Membuat Bucket R2

1. Login ke Cloudflare Dashboard (https://dash.cloudflare.com)
2. Pilih **R2 Object Storage** → **Create bucket**
3. Nama bucket: `blogverse-media` (atau sesuai keinginan)
4. Klik **Create bucket**

### 4.2 Mengaktifkan Akses Publik

1. Buka bucket → tab **Settings**
2. Di bagian **Public access**, klik **Allow Access**
3. Catat **Public bucket URL** (format: `https://pub-xxx.r2.dev`)
4. Opsional: tambahkan **Custom Domain** untuk URL lebih bersih

### 4.3 Membuat API Token R2

1. Cloudflare Dashboard → **R2** → **Manage R2 API Tokens**
2. Klik **Create API token**
3. Izin: **Object Read & Write**
4. Scope: pilih bucket `blogverse-media` saja
5. Klik **Create API Token**
6. Catat:
   - **Access Key ID** → `AWS_ACCESS_KEY_ID`
   - **Secret Access Key** → `AWS_SECRET_ACCESS_KEY`
   - **Endpoint URL** → `AWS_ENDPOINT` (format: `https://<account_id>.r2.cloudflarestorage.com`)

---

## 5. Langkah Deploy ke Vercel

### 5.1 Generate APP_KEY Baru

```bash
# Jalankan di direktori proyek
php artisan key:generate --show
# Salin hasilnya (format: base64:...) untuk diisi di Vercel
```

> **JANGAN** salin `APP_KEY` dari file `.env` lokal ke Vercel.
> Selalu generate key baru untuk produksi.

### 5.2 Login & Link ke Vercel

```bash
# Install Vercel CLI (jika belum)
npm i -g vercel

# Login ke akun Vercel
vercel login

# Link proyek (jalankan di direktori proyek)
vercel link
# Ikuti prompt: pilih akun, buat proyek baru atau link ke yang sudah ada
```

### 5.3 Isi Environment Variables

Dua cara mengisi env vars:

**Cara A — Via Vercel Dashboard (Direkomendasikan):**
1. Buka https://vercel.com/dashboard
2. Pilih project → **Settings** → **Environment Variables**
3. Tambahkan satu per satu semua variabel dari Bagian 2

**Cara B — Via Vercel CLI:**
```bash
vercel env add APP_KEY production
# (masukkan nilai saat diminta)
```

### 5.4 Deploy ke Production

```bash
# Deploy ke production
vercel deploy --prod
```

Vercel akan:
1. Mendeteksi `vercel.json` dengan `"framework": "container"`
2. Build Docker image dari `Dockerfile.vercel`
3. Install dependensi PHP + Node.js
4. Build frontend dengan Vite
5. Cache `view` dan `event` (bukan `config`)
6. Deploy container ke edge

> **Catatan:** Proses build pertama bisa memakan waktu 5-10 menit karena
> men-download image Docker FrankenPHP.

---

## 6. Menjalankan Migrasi & Seeder Admin

Migrasi **tidak** dijalankan otomatis saat build atau boot. Jalankan secara manual
dari mesin lokal yang memiliki koneksi ke database online.

### 6.1 Buat File .env.production Sementara

```bash
# Salin .env lokal sebagai titik awal
copy .env .env.production
```

Edit `.env.production` — ubah nilai berikut:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://blogverse.vercel.app

DB_CONNECTION=mysql
DB_HOST=gateway01.ap-southeast-1.prod.aws.tidbcloud.com
DB_PORT=4000
DB_DATABASE=blogverse
DB_USERNAME=xxxxxxxx.root
DB_PASSWORD=your-tidb-password

MYSQL_ATTR_SSL_CA=
MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=true

MEDIA_DISK=r2
AWS_ACCESS_KEY_ID=your-r2-key-id
AWS_SECRET_ACCESS_KEY=your-r2-secret
AWS_DEFAULT_REGION=auto
AWS_BUCKET=blogverse-media
AWS_URL=https://pub-xxx.r2.dev
AWS_ENDPOINT=https://account_id.r2.cloudflarestorage.com
```

> **JANGAN commit `.env.production`!** Hapus setelah selesai.

### 6.2 Jalankan Migrasi

```bash
php artisan migrate --force --env=production
```

Pastikan semua tabel terbuat, termasuk:
- `users`, `posts`, `categories`, `tags`
- `sessions` — untuk `SESSION_DRIVER=database`
- `cache`, `cache_locks` — untuk `CACHE_STORE=database`
- `jobs`, `job_batches`, `failed_jobs`

### 6.3 Jalankan Seeder Admin Pertama

```bash
php artisan db:seed --class=AdminSeeder --force --env=production
```

Seeder akan membaca `ADMIN_NAME`, `ADMIN_USERNAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`
dari `.env.production` dan membuat akun admin pertama.

> Jika env tidak diisi, seeder akan menampilkan peringatan dan tidak membuat akun.

### 6.4 Hapus File Sementara

```bash
# Hapus .env.production setelah selesai
del .env.production
```

---

## 7. Menyambungkan HeidiSQL ke Database Online

Untuk memeriksa tabel dan data di TiDB Cloud menggunakan HeidiSQL:

1. Buka **HeidiSQL**
2. Klik **New** (Session Manager)
3. Isi konfigurasi:
   - **Network type:** MySQL (TCP/IP)
   - **Hostname / IP:** `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`
   - **User:** `xxxxxxxx.root`
   - **Password:** `your-tidb-password`
   - **Port:** `4000`
4. Tab **SSL**:
   - Centang **Use SSL**
   - **SSL CA:** kosongkan (TiDB Cloud menggunakan sistem CA)
   - Centang **Verify server certificate**
5. Klik **Open**

> **Tips:** TiDB Cloud mengharuskan SSL. Jika koneksi gagal, pastikan SSL diaktifkan
> dan port `4000` tidak diblokir firewall.

---

## 8. Catatan Runtime: FrankenPHP vs Fallback

### Primary: FrankenPHP Container (Direkomendasikan)

File konfigurasi: `vercel.json` (sudah dikonfigurasi `"framework": "container"`)

Membutuhkan:
- Akun Vercel dengan Container Runtime support
- `Dockerfile.vercel` di root proyek

Verifikasi: Jika deploy berhasil dan `/health` mengembalikan `{"status":"ok"}`,
FrankenPHP Container berjalan dengan benar.

### Fallback: vercel-php Serverless

Jika akun Vercel Hobby tidak mendukung Container Runtime:

```bash
# Backup vercel.json aktif
copy vercel.json vercel.container.json

# Gunakan konfigurasi fallback
copy vercel.serverless.json vercel.json
```

Lalu deploy ulang:
```bash
vercel deploy --prod
```

Perbedaan fallback:
- Menggunakan `api/index.php` sebagai entry point
- Runtime: `vercel-php@0.7.3` (community runtime)
- Tidak memerlukan Docker
- Batas timeout lebih ketat (max 60 detik)

---

## 9. Batas Payload & Timeout Vercel

| Item | Batas | Keterangan |
|------|-------|-----------|
| Body request max | ~4.5 MB | Hobby plan Vercel serverless |
| Upload cover artikel | **4 MB** | Sudah diatur di `PostRequest.php` |
| Import Excel | **4 MB** | Sudah diatur di `PostController.php` |
| Timeout function | **60 detik** | Hobby plan; `AI_TIMEOUT=50` untuk margin |
| Timeout AI Generator | **50 detik** | Diatur via env `AI_TIMEOUT` |

> **Jika butuh file lebih besar dari 4 MB:**
> Gunakan **direct upload ke R2 dari frontend** melalui pre-signed URL.
> Backend generate pre-signed URL, frontend upload langsung ke R2 tanpa melewati server.

---

## 10. Checklist Pasca-Deploy

Lakukan verifikasi berikut setelah deploy berhasil:

### Fungsionalitas Dasar
- [ ] `GET https://your-app.vercel.app/health` → `{"status":"ok"}`
- [ ] Homepage `/` tampil normal (artikel, navbar, footer)
- [ ] Halaman `/login` dapat diakses

### Autentikasi
- [ ] Login dengan akun admin yang dibuat AdminSeeder berhasil
- [ ] Dashboard admin dapat diakses setelah login
- [ ] Login member biasa berhasil (jika ada akun member)

### Fitur Artikel
- [ ] Daftar artikel tampil di halaman publik
- [ ] Detail artikel dapat dibuka
- [ ] Tambah artikel baru dengan cover image berhasil
- [ ] Cover image muncul dengan URL R2 (bukan `/storage/...`)
- [ ] Edit artikel berhasil
- [ ] Hapus artikel berhasil (file cover di R2 ikut terhapus)

### Profil
- [ ] Upload foto profil berhasil
- [ ] Foto profil muncul dengan URL R2 di navbar/sidebar
- [ ] Edit profil berhasil

### Fitur Lanjutan
- [ ] Export Excel berhasil (file bisa diunduh)
- [ ] Import Excel berhasil (dengan file < 4 MB)
- [ ] AI Generator berfungsi (jika `OPENAI_KEY` diisi)

### Keamanan
- [ ] `APP_DEBUG=false` (tidak ada stack trace di response error)
- [ ] HTTPS aktif (tidak ada mixed content warning)
- [ ] Session bekerja (login tidak hilang saat refresh)

---

## 11. Troubleshooting

### Error: "No application encryption key has been specified"
**Solusi:** Pastikan `APP_KEY` sudah diisi di Vercel env vars.
```bash
php artisan key:generate --show
# Salin hasilnya ke Vercel env
```

### Error: "SQLSTATE[HY000] [2002] Connection refused"
**Solusi:**
- Periksa nilai `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`
- Pastikan SSL diaktifkan untuk TiDB Cloud
- Coba koneksi dari lokal dengan `.env.production` untuk debugging

### Error: "Class not found" atau Autoload issues
**Solusi:**
```bash
composer dump-autoload --optimize
vercel deploy --prod --force
```

### File upload gagal (error 413 atau timeout)
**Solusi:**
- Pastikan ukuran file < 4 MB
- Periksa nilai `AWS_*` env vars sudah benar
- Verifikasi bucket R2 sudah bersifat publik

### Avatar/cover tidak muncul (URL tidak valid)
**Solusi:**
- Periksa `AWS_URL` sudah diisi dengan URL publik bucket R2
- Pastikan `MEDIA_DISK=r2` sudah diatur
- Verifikasi file terupload di Cloudflare R2 dashboard

### Session hilang setelah refresh
**Solusi:**
- Pastikan tabel `sessions` sudah ada (jalankan migrasi)
- Periksa `SESSION_DRIVER=database`
- Periksa `SESSION_DOMAIN` sudah sesuai domain produksi

### Docker tidak terinstal (tidak bisa uji lokal)
**Solusi:**
- Install Docker Desktop (https://www.docker.com/products/docker-desktop/) untuk uji lokal
- Atau gunakan `vercel deploy --preview` untuk uji di environment Vercel langsung
- Atau uji di lokal tanpa Docker: `php artisan serve` dengan env produksi

---

*Dokumen ini dibuat untuk BlogVerse Pro — Laravel 13 + Inertia.js + React 18.*
*Diperbarui: November 2026*
