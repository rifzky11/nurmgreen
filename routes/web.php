<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman publik (tanpa login)
|--------------------------------------------------------------------------
| Beranda, katalog, dan detail produk boleh dibuka siapa pun, termasuk
| pengunjung yang belum login.
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog.index');

// {product:slug}: route model binding lewat kolom slug, bukan id
Route::get('/produk/{product:slug}', [CatalogController::class, 'show'])
    ->name('catalog.show');

/*
|--------------------------------------------------------------------------
| Autentikasi (manual, tanpa starter kit)
|--------------------------------------------------------------------------
| Middleware guest: user yang sudah login tidak boleh membuka halaman ini.
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Area admin
|--------------------------------------------------------------------------
| auth          : harus login
| role:admin    : hanya role admin (middleware custom)
| prefix admin  : semua URL diawali /admin
| name admin.   : semua nama route diawali admin.
*/
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // index, create, store, edit, update, destroy (tanpa show)
        Route::resource('categories', CategoryController::class)->except('show');

        // Resource lengkap termasuk show (detail produk untuk admin)
        Route::resource('products', ProductController::class);
    });

/*
|--------------------------------------------------------------------------
| Area customer
|--------------------------------------------------------------------------
| auth           : harus login
| role:customer  : admin tidak boleh masuk area belanja
*/
Route::middleware(['auth', 'role:customer'])->group(function () {
    // Placeholder; isinya dibuat pada fitur keranjang/checkout/pesanan.
    Route::get('/keranjang', fn () => view('customer.coming-soon', ['title' => 'Keranjang']))
        ->name('cart.index');

    Route::get('/checkout', fn () => view('customer.coming-soon', ['title' => 'Checkout']))
        ->name('checkout.index');

    Route::get('/pesanan', fn () => view('customer.coming-soon', ['title' => 'Pesanan Saya']))
        ->name('orders.index');
});
