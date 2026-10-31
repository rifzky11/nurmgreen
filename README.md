# 🌿 NurmGreen

Aplikasi web e-commerce untuk UMKM lokal yang menjual **tanaman hias, bibit, pupuk, obat tanaman, media tanam, dan alat berkebun**. Dibangun dengan Laravel, Blade, MySQL, CSS tulis tangan, dan JavaScript vanilla.

- **Nama mahasiswa:** Muhammad Rifzky Nurman
- **NIM:** 25110300004
- **Live URL:** `https://nurmgreen.infinityfree.me`
- **Repository:** https://github.com/rifzky11/nurmgreen

---

## 📋 Daftar Isi

1. [Fitur](#-fitur)
2. [Tech Stack](#-tech-stack)
3. [Hak Akses Pengguna](#-hak-akses-pengguna)
4. [Struktur Database](#-struktur-database)
5. [Struktur Direktori](#-struktur-direktori)
6. [Instalasi Lokal](#-instalasi-lokal)
7. [Akun Login Demo](#-akun-login-demo)
8. [Deployment ke InfinityFree (Shared Hosting)](#-deployment-ke-infinityfree-shared-hosting)
9. [Ekspor Database ke nurmgreen.sql](#-ekspor-database-ke-nurmgreensql)
10. [Testing & Code Style](#-testing--code-style)
11. [Implementasi Keamanan](#-implementasi-keamanan)
12. [Troubleshooting](#-troubleshooting)
13. [Lisensi & Catatan Akademik](#-lisensi--catatan-akademik)

---

## ✨ Fitur

### Pengunjung (Guest)
- Beranda dengan kategori populer dan produk terbaru.
- Katalog produk: pencarian nama, filter kategori, urutan harga, dan pagination.
- Halaman detail produk dengan status stok, produk serupa, dan tombol beli.
- Registrasi dan login dengan validasi serta rate limiting.

### Customer
- **Keranjang belanja**
  - Tambah produk dari halaman detail melalui AJAX: toast notifikasi dan badge navbar berubah tanpa reload.
  - Centang produk tertentu untuk checkout; jumlah jenis produk, total barang, subtotal, dan total mengikuti pilihan secara langsung.
  - Jumlah otomatis tersimpan saat diubah (tanpa tombol "Ubah"), dengan validasi stok dan pesan error inline.
- **Buku alamat**
  - CRUD alamat penerima (label, nama, nomor HP, alamat lengkap).
  - Alamat utama otomatis dipilih saat checkout; alamat pertama otomatis menjadi utama.
- **Checkout**
  - Checkout hanya produk yang dipilih, memakai alamat tersimpan atau isi manual.
  - Validasi ulang stok dan pembuatan order dalam satu transaksi database.
  - Kode pesanan unik `NG-YYYYMMDD-XXXX`, snapshot nama & harga produk, serta instruksi transfer bank.
- **Pembayaran**
  - Unggah/ganti bukti transfer (JPG/JPEG/PNG/WEBP, maks. 2 MB) selama pesanan berstatus *pending*.
  - Bukti disimpan di disk privat; hanya pemilik dan admin yang dapat melihatnya.
  - Status tidak berubah otomatis; admin yang memverifikasi.
- **Pesanan**
  - Riwayat pesanan dengan pagination, detail item, dan timeline status.
  - Batalkan pesanan *pending* (stok dikembalikan dalam transaksi).
  - Konfirmasi "Pesanan Sudah Diterima" saat status *shipped* (menjadi *completed*).
- **Profil**
  - Ubah nama, nomor HP, dan alamat.
  - Ubah password dengan verifikasi password saat ini.

### Admin
- **Dashboard**
  - Kartu ringkasan: total produk, kategori, pesanan, omzet (paid/shipped/completed), pesanan menunggu pembayaran, dan pesanan dengan bukti menunggu verifikasi.
  - 5 pesanan terbaru dan daftar produk dengan stok menipis (≤ 5).
  - Semua angka memakai agregasi SQL (`COUNT`/`SUM`) dan eager loading.
- **Kategori & produk**
  - CRUD kategori dan produk.
  - Upload gambar produk dengan validasi tipe dan ukuran.
- **Pesanan**
  - Pencarian kode pesanan, filter status, filter "sudah upload bukti", dan pagination.
  - Detail pesanan: customer, pengiriman, item, total, bukti pembayaran, serta waktu dibuat/dikirim/diterima.
  - Ubah status: `pending → paid → shipped` atau `cancelled` (stok kembali); status `completed` hanya dapat ditetapkan buyer.
- **Profil** admin juga dapat diubah melalui halaman profil.

### Umum
- Halaman error kustom bertema NurmGreen: **403, 404, 419, 500**.
- Notifikasi toast, dialog konfirmasi aman, dan tampilan responsif.
- Bahasa antarmuka: Indonesia.

---

## 🛠 Tech Stack

| Komponen | Teknologi |
|---|---|
| Bahasa | PHP `^8.3` |
| Framework | Laravel `^13.17` |
| Database | MySQL 8 / MariaDB (relasional) |
| ORM | Eloquent + Query Builder |
| View | Blade Template |
| Styling | CSS tulis tangan (`public/css/app.css`), tanpa framework CSS/CDN |
| JavaScript | Vanilla JS (fetch/AJAX, toast, keranjang dinamis) |
| Autentikasi | Session guard Laravel (bcrypt) |
| Testing | PHPUnit 12 |
| Code style | Laravel Pint |
| Version control | Git |

> Aset frontend disajikan langsung dari folder `public/`. Aplikasi **tidak membutuhkan `npm build`** untuk berjalan; `package.json` hanya disediakan untuk kebutuhan opsional Vite.

---

## 🔐 Hak Akses Pengguna

| Fitur | Guest | Customer | Admin |
|---|:---:|:---:|:---:|
| Lihat beranda, katalog, detail produk | ✅ | ✅ | ✅ |
| Registrasi & login | ✅ | – | – |
| Keranjang & checkout | ❌ | ✅ | ❌ |
| Buku alamat | ❌ | ✅ | ❌ |
| Upload bukti pembayaran | ❌ | ✅ (pemilik, pending) | ❌ |
| Lihat bukti pembayaran | ❌ | ✅ (pemilik) | ✅ |
| Riwayat & detail pesanan sendiri | ❌ | ✅ | ❌ |
| Batalkan pesanan pending | ❌ | ✅ | ❌ |
| Konfirmasi pesanan diterima | ❌ | ✅ (pemilik, shipped) | ❌ |
| Dashboard admin | ❌ | ❌ | ✅ |
| CRUD kategori & produk | ❌ | ❌ | ✅ |
| Kelola & ubah status pesanan | ❌ | ❌ | ✅ |
| Edit profil & ubah password | ❌ | ✅ | ✅ |

---

## 🗄 Struktur Database

```
users 1──N addresses
users 1──N cart_items N──1 products
users 1──N orders 1──N order_items N──1 products
products N──1 categories
```

| Tabel | Kolom penting | Keterangan |
|---|---|---|
| `users` | `name`, `email`, `password`, `role`, `phone`, `address` | Role: `admin` / `customer` |
| `addresses` | `user_id`, `label`, `recipient_name`, `phone`, `address`, `is_default` | Buku alamat customer |
| `categories` | `name`, `slug`, `description` | Kategori produk |
| `products` | `category_id`, `name`, `slug`, `description`, `price`, `stock`, `image`, `is_active` | Produk; `category_id` restrict delete |
| `cart_items` | `user_id`, `product_id`, `quantity` | Unique `(user_id, product_id)` |
| `orders` | `user_id`, `order_code`, `total_price`, `status`, `shipping_name`, `shipping_phone`, `shipping_address`, `notes`, `payment_proof`, `payment_proof_uploaded_at`, `shipped_at`, `completed_at` | Status: `pending`, `paid`, `shipped`, `completed`, `cancelled` |
| `order_items` | `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal` | Snapshot nama & harga saat checkout |

Catatan:
- `orders.order_code` unik dengan format `NG-YYYYMMDD-XXXX`.
- `payment_proof` menyimpan path relatif file di disk privat `storage/app/private/payment-proofs`.
- `order_items` menyimpan snapshot sehingga riwayat tidak berubah saat produk diedit.
- Relasi `orders.user_id` memakai `restrictOnDelete`; data pesanan tidak boleh kehilangan pemilik.

---

## 📁 Struktur Direktori

```
nurmgreen/
├── app/
│   ├── Exceptions/              # CheckoutException, InsufficientStockException
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/           # Dashboard, Product, Category, Order
│   │   │   ├── AddressController.php
│   │   │   ├── AuthController.php
│   │   │   ├── CartController.php
│   │   │   ├── CatalogController.php
│   │   │   ├── CheckoutController.php
│   │   │   ├── OrderController.php
│   │   │   └── ProfileController.php
│   │   ├── Middleware/          # EnsureUserHasRole
│   │   └── Requests/            # Validasi form (Auth, Product, Checkout, Address, dll.)
│   ├── Models/                  # User, Address, Category, Product, CartItem, Order, OrderItem
│   └── Policies/                # OrderPolicy (kepemilikan & aksi pesanan)
├── database/
│   ├── factories/               # UserFactory
│   ├── migrations/              # Skema tabel
│   ├── seeders/                 # DatabaseSeeder (akun demo + katalog)
│   └── nurmgreen.sql            # Hasil ekspor database (dibuat manual, tidak wajib di-commit)
├── public/
│   ├── css/app.css              # Seluruh styling aplikasi
│   └── storage/                 # Symlink ke storage/app/public
├── resources/views/
│   ├── admin/                   # Dashboard, kategori, produk, pesanan
│   ├── auth/                    # Login & register
│   ├── catalog/                 # Katalog & detail produk
│   ├── customer/
│   │   ├── addresses/           # Buku alamat
│   │   ├── orders/              # Riwayat & detail pesanan
│   │   ├── partials/            # Instruksi transfer
│   │   ├── cart.blade.php
│   │   ├── checkout.blade.php
│   │   └── checkout-success.blade.php
│   ├── errors/                  # 403, 404, 419, 500 + layout
│   ├── profile/                 # Edit profil & password
│   └── layouts/app.blade.php    # Layout utama + navbar + toast
├── routes/web.php
├── .env.example
└── README.md
```

---

## 💻 Instalasi Lokal

### Prasyarat
- PHP **8.3+** dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json`.
- Composer 2.
- MySQL 8 / MariaDB (XAMPP, Laragon, atau server MySQL).
- Git.
- Node.js **tidak wajib** (CSS & JS disajikan statis dari `public/`).

### Langkah-langkah

```bash
# 1. Clone repository
git clone https://github.com/rifzky11/nurmgreen.git
cd nurmgreen

# 2. Install dependency PHP
composer install

# 3. Salin file environment
cp .env.example .env
# Windows CMD:
# copy .env.example .env

# 4. Buat database kosong di MySQL
#    CREATE DATABASE nurmgreen CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 5. Sesuaikan .env
#    APP_DEBUG=true              (untuk development lokal)
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=nurmgreen
#    DB_USERNAME=root
#    DB_PASSWORD=

# 6. Generate application key
php artisan key:generate

# 7. Jalankan migrasi + seeder data demo
php artisan migrate --seed

# 8. Buat symlink storage untuk gambar produk
php artisan storage:link

# 9. Jalankan server lokal
php artisan serve
```

Buka **http://127.0.0.1:8000**.

Jika muncul error permission di Linux/macOS:

```bash
chmod -R ug+rwx storage bootstrap/cache
```

### Alternatif: import SQL
Jika tersedia file `database/nurmgreen.sql`, buat database kosong lalu impor:

```bash
mysql -u root -p nurmgreen < database/nurmgreen.sql
php artisan key:generate
php artisan storage:link
php artisan serve
```

> File SQL berisi data pengguna. Jangan commit dump production yang berisi data pribadi.

---

## 👤 Akun Login Demo

Dibuat oleh `DatabaseSeeder` (`php artisan migrate --seed`):

| Role | Email | Password |
|---|---|---|
| Admin | `admin@nurmgreen.app` | `admin1234` |
| Customer 1 | `marl@nurmgreen.app` | `marl1234` |
| Customer 2 | `ripski@nurmgreen.app` | `ripski1234` |

> Registrasi publik selalu membuat akun `customer`. Akun admin tidak dapat dibuat dari form registrasi.

---

## 🚀 Deployment ke InfinityFree (Shared Hosting)

Aplikasi live di-hosting di **InfinityFree** (shared hosting gratis dengan PHP + MySQL remote) pada `https://nurmgreen.infinityfree.me`. Karena shared hosting **tidak menyediakan akses SSH maupun Composer**, seluruh persiapan dilakukan di komputer lokal lalu file diunggah ke server.

### 1. Persiapan di komputer lokal
- Push project ke GitHub; pastikan branch yang di-deploy berisi kode terbaru.
- Buat akun [InfinityFree](https://www.infinityfree.com) dan subdomain `nurmgreen.infinityfree.me`.
- Install dependency produksi **secara lokal** (Composer tidak tersedia di server):

```bash
composer install --no-dev --optimize-autoloader
```

Hasilnya folder `vendor/` siap pakai untuk production dan ikut diunggah.

### 2. Upload file ke htdocs
- Unggah seluruh isi project ke folder `htdocs/` melalui FTP (mis. FileZilla) atau File Manager di panel InfinityFree.
- Karena `vendor/` berisi ribuan file kecil, cara tercepat: kompres menjadi `.zip` di lokal, unggah satu file zip, lalu ekstrak lewat File Manager.
- Jangan ikutkan `.git/`, `node_modules/`, dan `tests/`.
- File `.env` dibuat manual di server (tidak ikut repository).

### 3. Root .htaccess — rewrite ke public/
Document root InfinityFree mengarah ke `htdocs/`, bukan ke `public/`. Tambahkan `.htaccess` di root project yang meneruskan seluruh request ke folder `public/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^$ public/ [L]
    RewriteRule (.*) public/$1 [L]
</IfModule>
```

`public/.htaccess` bawaan Laravel tetap dipertahankan dan menangani front controller `index.php`.

### 4. Konfigurasi .env production
Buat `.env` di `htdocs/` dengan nilai berikut (host, nama database, user, dan password diambil dari menu **MySQL Databases** di panel InfinityFree):

```env
APP_NAME=NurmGreen
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:HASIL_KEY_GENERATE
APP_URL=https://nurmgreen.infinityfree.me

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=sqlXXX.infinityfree.com
DB_PORT=3306
DB_DATABASE=if0_XXXXXXX_nurmgreen
DB_USERNAME=if0_XXXXXXX
DB_PASSWORD=PASSWORD_HOSTING

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

Catatan:
- Generate `APP_KEY` **sekali** dari lokal: `php artisan key:generate --show`, lalu salin hasilnya. Jangan regenerate karena akan membatalkan session/encoded data lama.
- `SESSION_SECURE_COOKIE=true` karena production memakai HTTPS (SSL gratis InfinityFree).
- Karena tidak ada SSH/artisan, config tidak pernah di-cache — setiap perubahan `.env` langsung terbaca setelah di-upload ulang.
- Pastikan versi PHP di panel (**Select PHP Version**) minimal 8.3.

### 5. Import database via phpMyAdmin
1. Panel InfinityFree → **MySQL Databases** → buat database, catat host (`sqlXXX.infinityfree.com`), nama database, username, dan password.
2. Buka phpMyAdmin dari panel, pilih database tersebut.
3. Tab **Import** → unggah `database/nurmgreen.sql` (hasil ekspor lokal) → **Go**.
4. Tabel `sessions` ikut terimpor, sehingga `SESSION_DRIVER=database` langsung berfungsi tanpa migrasi tambahan.

### 6. Mengapa Schema::defaultStringLength(191)
MySQL/MariaDB versi lama membatasi panjang index key 767 byte untuk charset `utf8mb4` (4 byte per karakter). Kolom string yang di-index/unik (mis. `users.email`) dibatasi 191 karakter karena 191 × 4 = 764 byte — masih di bawah batas tersebut. Aturan ini dipanggil sekali di `app/Providers/AppServiceProvider.php` melalui `Schema::defaultStringLength(191)` sehingga migrasi berjalan aman di server hosting.

### 7. Symlink storage — workaround tanpa SSH
`php artisan storage:link` tidak dapat dijalankan karena tidak ada SSH, dan symlink umumnya tidak diizinkan di shared hosting. Workaround-nya: **ganti symlink `public/storage` menjadi folder asli** yang berisi salinan isi `storage/app/public`:

```bash
# di lokal, sebelum upload
rm public/storage
cp -R storage/app/public public/storage
```

Folder `public/storage/products/` beserta gambarnya ikut diunggah sebagai direktori nyata, sehingga URL gambar produk `/storage/products/...` tetap berfungsi. Jika ada produk baru yang diunggah lewat admin, unggah manual file gambarnya ke `public/storage/products/` via File Manager, atau ulangi salinan lokal di atas.

### 8. Permission folder
Via File Manager InfinityFree, pastikan folder berikut writable (default 755; naikkan ke 775/775–777 bila muncul error permission):
- `storage/` beserta seluruh subfoldernya (`storage/logs`, `storage/framework`, `storage/app`).
- `bootstrap/cache/`.

Di shared hosting file dimiliki akun hosting sendiri, sehingga `chown` tidak diperlukan — cukup `chmod` bila terjadi error penulisan log/session/cache.

### 9. Checklist setelah deploy
- [ ] Subdomain HTTPS terbuka (`https://nurmgreen.infinityfree.me`).
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` benar.
- [ ] Login admin berhasil dan tidak muncul `419 Page Expired`.
- [ ] Gambar produk tampil (folder `public/storage/products` ter-upload).
- [ ] Upload bukti pembayaran berhasil dan dapat dilihat admin.
- [ ] `storage/logs/laravel.log` tidak berisi error (dicek via File Manager).

---

## 🗃 Ekspor Database ke nurmgreen.sql

### macOS / Linux

```bash
mysqldump -u root -p \
  --default-character-set=utf8mb4 \
  --routines --triggers \
  nurmgreen > database/nurmgreen.sql
```

### Windows (XAMPP)

```cmd
C:\xampp\mysql\bin\mysqldump.exe -u root -p nurmgreen > database\nurmgreen.sql
```

### Docker

```bash
docker exec -i nama_container_mysql \
  mysqldump -u root -pPASSWORD nurmgreen > database/nurmgreen.sql
```

### Impor kembali

```bash
mysql -u root -p nurmgreen < database/nurmgreen.sql
```

Catatan:
- Ganti `nurmgreen` dengan nama database Anda.
- Backup berisi data pengguna dan pesanan; jangan commit dump production ke repository publik.
- Setelah impor di mesin baru, tetap jalankan `php artisan key:generate` dan `php artisan storage:link`.

---

## 🧪 Testing & Code Style

```bash
# Menjalankan seluruh test
php artisan test

# Test tertentu
php artisan test --filter=CheckoutTest

# Format kode PHP (Laravel Pint)
./vendor/bin/pint

# Cek format tanpa mengubah file
./vendor/bin/pint --test
```

Test memakai SQLite in-memory (`phpunit.xml`), sehingga tidak mengganggu database MySQL lokal.

---

## 🛡 Implementasi Keamanan

| Ancaman | Penanganan |
|---|---|
| **CSRF** | `@csrf` di semua form non-GET; AJAX mengirim `X-CSRF-TOKEN` dari meta tag; middleware `VerifyCsrfToken` |
| **XSS** | Output Blade `{{ }}`; tidak ada `{!! !!}`; toast memakai `textContent`; konfirmasi hapus tidak menyisipkan nama ke JavaScript inline |
| **SQL Injection** | Eloquent/Query Builder dengan parameter binding; tidak ada raw SQL; wildcard LIKE di-escape |
| **Mass Assignment** | `$fillable` eksplisit; `role` tidak fillable dan hanya di-set server via `forceFill()` |
| **Password** | Hash bcrypt; ubah password wajib `current_password`; session diregenerasi |
| **Session Fixation** | `session()->regenerate()` saat login/ganti password; logout `invalidate()` + `regenerateToken()` |
| **Brute Force** | Rate limiting login (5 percobaan/menit per email + IP) |
| **Akses Tidak Sah** | Middleware `role:admin` / `role:customer`, `OrderPolicy`, dan pengecekan kepemilikan pada keranjang/alamat |
| **IDOR / Data Orang Lain** | Query pesanan/keranjang/alamat selalu di-scope `user_id`; route bukti bayar hanya pemilik/admin |
| **Upload File** | Validasi `image`, `mimes:jpg,jpeg,png,webp`, maks. 2 MB; nama file digenerate; bukti bayar di disk privat |
| **Konsistensi Stok** | Checkout, pembatalan, dan perubahan status memakai `DB::transaction` + `lockForUpdate` |
| **Error Exposure** | Halaman error kustom 403/404/419/500; `APP_DEBUG=false` di production |
| **Validasi Input** | Form Request di sisi server untuk seluruh input penting |

---

## 🔧 Troubleshooting

| Masalah | Penyebab & solusi |
|---|---|
| **Error 500** | Cek `storage/logs/laravel.log` (unduh via File Manager InfinityFree). Pastikan `APP_KEY` terisi, koneksi DB benar, folder `storage`/`bootstrap/cache` writable, dan `.env` ter-upload lengkap di `htdocs/`. |
| **Halaman putih / 500 saat `APP_DEBUG=false`** | Sementara set `APP_DEBUG=true` di `.env` hosting untuk melihat detail (hapus kembali setelah selesai), atau baca log. Jangan biarkan `APP_DEBUG=true` di production. |
| **Gambar produk tidak tampil** | Pastikan `APP_URL` benar dan folder `public/storage/products` ter-upload sebagai folder asli (workaround symlink, lihat bagian deployment). Di lokal cukup `php artisan storage:link --force`. |
| **`storage:link` gagal: link already exists** | Di lokal jalankan `php artisan storage:link --force` atau hapus `public/storage` terlebih dahulu. Di Windows, jalankan terminal sebagai Administrator. Di shared hosting tanpa SSH, gunakan workaround folder asli `public/storage`. |
| **Permission denied pada storage/bootstrap cache** | `chmod -R ug+rwx storage bootstrap/cache`; di shared hosting ubah permission via File Manager (755, naik ke 775/777 bila perlu). |
| **`No application encryption key has been specified`** | `php artisan key:generate`. Untuk production, isi `APP_KEY` dari `php artisan key:generate --show` sekali saja, lalu tulis langsung ke `.env` di server. |
| **`419 Page Expired`** | Token CSRF kedaluwarsa/session hilang. Pastikan form punya `@csrf`, `SESSION_DRIVER=database` dan tabel `sessions` ikut terimpor dari `nurmgreen.sql`. |
| **`SQLSTATE[HY000] [1049] Unknown database`** | Buat database `nurmgreen` dan cocokkan `DB_DATABASE`. |
| **`Connection refused` / `2002`** | MySQL belum berjalan atau `DB_HOST`/`DB_PORT` salah. Di InfinityFree gunakan host MySQL dari panel (`sqlXXX.infinityfree.com`), bukan `localhost`. |
| **Upload gagal / “file terlalu besar”** | Validasi aplikasi maks. 2 MB; cek juga `upload_max_filesize` dan `post_max_size` di `php.ini`. |
| **Perubahan `.env` tidak terbaca** | Di server tidak ada config cache (tidak ada SSH), sehingga perubahan `.env` di File Manager langsung berlaku; cukup refresh halaman. Di lokal jalankan `php artisan config:clear`. |
| **`Class not found` setelah menambah file** | `composer dump-autoload` di lokal, lalu unggah ulang `vendor/composer` ke hosting (Composer tidak tersedia di server). |
| **CSS tidak muncul** | Pastikan `public/css/app.css` ada dan tidak perlu `npm build`; cek URL aset dan cache browser. |
| **Import SQL gagal di phpMyAdmin** | Pastikan database kosong, ukuran file di bawah batas upload InfinityFree (kompres atau impor per bagian bila perlu), dan charset `utf8mb4` didukung. |
| **Halaman error / 500 hanya di hosting** | Cek versi PHP di panel (Select PHP Version) minimal 8.3, `vendor/` ter-upload lengkap, dan `.htaccess` root meneruskan ke `public/`. |
| **Bukti pembayaran gagal tersimpan** | Pastikan `storage/app/private` writable (chmod 775) dan sisa kuota disk hosting mencukupi. |

---

## 📄 Lisensi & Catatan Akademik

Proyek ini dibuat secara individu untuk keperluan UAS **Web Application Development**. Seluruh kode, tampilan, dan dokumentasi digunakan untuk pembelajaran; data dan akun demo hanya untuk keperluan presentasi.
