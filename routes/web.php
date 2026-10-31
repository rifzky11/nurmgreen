<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
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

        // Kelola pesanan: daftar + filter, detail, dan ubah status.
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order:order_code}', [AdminOrderController::class, 'show'])
            ->name('orders.show');
        Route::patch('/orders/{order:order_code}/status', [AdminOrderController::class, 'updateStatus'])
            ->name('orders.update-status');
    });

/*
|--------------------------------------------------------------------------
| Area customer
|--------------------------------------------------------------------------
| auth           : harus login
| role:customer  : admin tidak boleh masuk area belanja
*/
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/keranjang', [CartController::class, 'index'])->name('cart.index');

    // {product:slug}: produk yang ditambahkan (satu baris per produk per user)
    Route::post('/keranjang/{product:slug}', [CartController::class, 'store'])
        ->name('cart.store');

    // {cartItem}: baris keranjang; kepemilikan dicek di controller (403)
    Route::patch('/keranjang/{cartItem}', [CartController::class, 'update'])
        ->name('cart.update');

    Route::delete('/keranjang/{cartItem}', [CartController::class, 'destroy'])
        ->name('cart.destroy');

    // Checkout: form pengiriman, proses pesanan (transaksi DB), halaman sukses.
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/sukses/{orderCode}', [CheckoutController::class, 'success'])
        ->name('checkout.success');

    // Riwayat pesanan customer: daftar, detail, dan pembatalan (pending saja).
    // {order:order_code} memakai kode pesanan di URL, bukan id.
    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order:order_code}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/pesanan/{order:order_code}/batal', [OrderController::class, 'cancel'])
        ->name('orders.cancel');
});
