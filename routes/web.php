<?php

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
| Middleware auth : hanya untuk user yang sudah login (logout).
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
