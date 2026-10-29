<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Beranda toko
Route::get('/', function () {
    return view('home');
})->name('home');

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
