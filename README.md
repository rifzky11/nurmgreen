# 🌿 NurmGreen

Aplikasi web e-commerce UMKM lokal untuk penjualan **tanaman hias, tanaman buah, pupuk, obat tanaman, media tanam, dan alat berkebun**. 

- **Nama mahasiswa:** `Muhammad Rifzky Nurman`
- **NIM:** `25110300004`
- **Live URL:** `#`
- **Repository:** `https://github.com/rifzky11/nurmgreen`

---

## 📋 Daftar Isi

1. [Fitur](#-fitur)
2. [Tech Stack](#-tech-stack)
3. [Hak Akses Pengguna](#-hak-akses-pengguna)
4. [Struktur Database](#-struktur-database)
5. [Struktur Direktori MVC](#-struktur-direktori-mvc)
6. [Instalasi Lokal](#-instalasi-lokal)
7. [Akun Login Demo](#-akun-login-demo)
8. [Implementasi Keamanan](#-implementasi-keamanan)
9. [Deployment](#-deployment-railway)
10. [Troubleshooting](#-troubleshooting)

---

## ✨ Fitur

### Pengunjung (Guest)
- Melihat beranda, katalog produk, dan detail produk
- Pencarian nama, filter kategori, urutan harga, pagination
- Registrasi dan login

### Customer
- Keranjang belanja (tambah, ubah jumlah, hapus)
- Checkout dengan transfer manual (proses dalam DB transaction)
- Riwayat dan detail pesanan, pembatalan pesanan berstatus *pending*
- Edit profil dan ubah password

### Admin
- Dashboard ringkasan (total produk, kategori, pesanan, pendapatan, stok menipis)
- CRUD kategori
- CRUD produk dengan upload gambar
- Kelola pesanan dan ubah status (pending → paid → shipped → completed / cancelled)

---

## 🛠 Tech Stack

| Komponen | Teknologi |
|---|---|
| Framework | Laravel (PHP) |
| Database | MySQL (relasional) |
| ORM | Eloquent |
| View | Blade Template |
| Styling | `[Tailwind CSS / Bootstrap]` via CDN |
| Versi Control | Git |

> Autentikasi dibuat **manual** (tanpa Breeze/Jetstream/starter kit).

---

## 🔐 Hak Akses Pengguna

| Fitur | Guest | Customer | Admin |
|---|:---:|:---:|:---:|
| Lihat katalog & detail produk | ✅ | ✅ | ✅ |
| Registrasi & login | ✅ | - | - |
| Keranjang & checkout | ❌ | ✅ | ❌ |
| Riwayat pesanan sendiri | ❌ | ✅ | ❌ |
| Edit profil | ❌ | ✅ | ✅ |
| CRUD kategori & produk | ❌ | ❌ | ✅ |
| Kelola semua pesanan | ❌ | ❌ | ✅ |
| Dashboard admin | ❌ | ❌ | ✅ |

---

## 🗄 Struktur Database

Tabel utama: `users`, `categories`, `products`, `cart_items`, `orders`, `order_items`.

```
users 1──N orders 1──N order_items N──1 products N──1 categories
users 1──N cart_items N──1 products
```

| Tabel | Keterangan |
|---|---|
| `users` | Data pengguna, kolom `role` (admin / customer) |
| `categories` | Kategori produk |
| `products` | Produk, terhubung ke kategori (`restrictOnDelete`) |
| `cart_items` | Isi keranjang, unique `(user_id, product_id)` |
| `orders` | Header pesanan, status, data pengiriman |
| `order_items` | Detail pesanan dengan snapshot nama dan harga produk |

Diagram ERD lengkap ada pada dokumen presentasi (PDF).

---

## 📁 Struktur Direktori MVC

```
nurmgreen/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Controller (AuthController, ProductController, dst.)
│   │   ├── Middleware/         # EnsureUserHasRole (role-based access)
│   │   └── Requests/           # Form Request (validasi server-side)
│   ├── Models/                 # Model Eloquent (User, Product, Order, dst.)
│   └── Policies/               # OrderPolicy (otorisasi kepemilikan data)
├── database/
│   ├── migrations/             # Skema tabel
│   ├── seeders/                # Data dummy
│   └── nurmgreen.sql           # Ekspor database
├── resources/views/            # View Blade
│   ├── layouts/
│   ├── auth/
│   ├── admin/
│   ├── shop/
│   └── errors/                 # 403, 404, 419, 500
├── routes/web.php              # Definisi routing
├── .env.example
└── README.md
```

---

## 💻 Instalasi Lokal

### Prasyarat
- PHP `[versi sesuai Laravel Anda, mis. >= 8.2]`
- Composer
- MySQL (XAMPP / Laragon / MySQL Server)
- Git

### Langkah-langkah

```bash
# 1. Clone repository
git clone [LINK_REPOSITORY]
cd nurmgreen

# 2. Install dependency
composer install

# 3. Salin file environment
cp .env.example .env          # Windows (CMD): copy .env.example .env

# 4. Buat database kosong di MySQL
#    Contoh: CREATE DATABASE nurmgreen;

# 5. Edit .env, sesuaikan koneksi database
#    DB_DATABASE=nurmgreen
#    DB_USERNAME=root
#    DB_PASSWORD=

# 6. Generate application key
php artisan key:generate

# 7. Jalankan migrasi dan seeder
php artisan migrate --seed

# 8. Buat symbolic link untuk gambar produk
php artisan storage:link

# 9. Jalankan server
php artisan serve
```

Buka **http://127.0.0.1:8000** di browser.

### Alternatif: Import file SQL
Jika tidak ingin menjalankan migrasi, import `database/nurmgreen.sql` melalui phpMyAdmin atau MySQL Workbench ke database `nurmgreen`, lalu lanjutkan langkah 6, 8, dan 9.

---

## 👤 Akun Login Demo

| Role | Email | Password |
|---|---|---|
| Admin | `admin@nurmgreen.app` | `admin1234` |
| Customer 1 | `marl@nurmgreen.app` | `marl1234` |
| Customer 2 | `ripski@nurmgreen.app` | `ripski1234` |

> Akun admin tidak bisa dibuat lewat registrasi. Registrasi publik otomatis berperan sebagai `customer`.

---

## 🛡 Implementasi Keamanan

| Ancaman | Penanganan |
|---|---|
| **CSRF** | Direktif `@csrf` pada seluruh form, middleware `VerifyCsrfToken` bawaan Laravel |
| **XSS** | Output Blade `{{ }}` yang otomatis di-escape, tanpa `{!! !!}` untuk input user |
| **SQL Injection** | Eloquent ORM / Query Builder dengan parameter binding |
| **Mass Assignment** | `$fillable` eksplisit, `role` tidak bisa diisi dari request |
| **Password** | Di-hash dengan `Hash::make` (bcrypt) |
| **Session Fixation** | `session()->regenerate()` saat login, `invalidate()` + `regenerateToken()` saat logout |
| **Brute Force** | Rate limiting pada endpoint login |
| **Akses Tidak Sah** | Middleware role (`admin` / `customer`) dan `OrderPolicy` untuk kepemilikan data |
| **Upload File** | Validasi tipe (jpg, png, webp) dan ukuran maksimal 2 MB |
| **Validasi Input** | Form Request di sisi server untuk seluruh input |

---

## 🚀 Deployment (Railway)

1. Push proyek ke GitHub.
2. Di [Railway](https://railway.app), buat **New Project → Deploy from GitHub repo**.
3. Tambahkan service **MySQL** pada project yang sama.
4. Atur **Variables** pada service aplikasi:

```env
APP_NAME=NurmGreen
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...            # hasil: php artisan key:generate --show
APP_URL=https://[domain-railway-anda]

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database       # atau file
```

5. Atur **Start Command** (sesuaikan dengan konfigurasi Railway Anda):

```bash
php artisan migrate --force && php artisan storage:link && php artisan serve --host=0.0.0.0 --port=$PORT
```

6. Jalankan seeder sekali untuk data demo (lewat Railway shell): `php artisan db:seed --force`
7. Buka **Settings → Networking → Generate Domain**, lalu isi `APP_URL` dengan domain tersebut.

> **Catatan:** storage disk di Railway bersifat sementara. Gambar yang diupload dapat hilang saat redeploy. Gunakan Railway Volume atau gunakan gambar dari seeder untuk keperluan demo.

---

## 🔧 Troubleshooting

| Masalah | Solusi |
|---|---|
| `No application encryption key has been specified` | Jalankan `php artisan key:generate` |
| `SQLSTATE[HY000] [1049] Unknown database` | Buat database terlebih dahulu dan cek `DB_DATABASE` di `.env` |
| `SQLSTATE[HY000] [2002] Connection refused` | Pastikan MySQL menyala, cek `DB_HOST` dan `DB_PORT` |
| Gambar produk tidak tampil | Jalankan `php artisan storage:link`, cek `APP_URL` |
| `419 Page Expired` | Pastikan form memakai `@csrf`, bersihkan cache: `php artisan optimize:clear` |
| `Permission denied` pada `storage` | `chmod -R 775 storage bootstrap/cache` |
| Error 500 di production | Cek log di dashboard Railway / `storage/logs/laravel.log`, pastikan `APP_KEY` dan variabel DB terisi |
| Perubahan `.env` tidak terbaca | Jalankan `php artisan config:clear` |
| `Class not found` setelah menambah file | Jalankan `composer dump-autoload` |

---

## 📄 Lisensi & Catatan Akademik

Proyek ini dibuat secara individu untuk keperluan UAS *Web Application Development*.