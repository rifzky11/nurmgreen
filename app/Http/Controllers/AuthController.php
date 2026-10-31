<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** Batas percobaan login per menit (per kombinasi email + IP). */
    private const MAX_ATTEMPTS = 5;

    /** Lama blokir dalam detik setelah batas terlampaui. */
    private const DECAY_SECONDS = 60;

    /** Tampilkan form register. */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Simpan akun baru, lalu langsung login-kan user.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        // Hanya data yang lolos validasi yang dipakai (role tidak pernah diambil dari input user)
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']), // simpan email huruf kecil agar konsisten saat login
            'password' => Hash::make($data['password']), // password disimpan sebagai hash bcrypt
        ]);

        // role TIDAK mass-assignable; dipaksa di server lewat forceFill.
        $user->forceFill(['role' => 'customer'])->save();

        // Login otomatis setelah daftar
        Auth::login($user);

        // Ganti ID session agar tidak bisa dibajak (session fixation)
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Akun berhasil dibuat. Selamat datang, '.$user->name.'!');
    }

    /** Tampilkan form login. */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Proses login dengan pembatasan percobaan.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Kunci rate limit: email (huruf kecil) + IP pengirim
        $key = 'login|'.Str::lower($data['email']).'|'.$request->ip();

        // Jika sudah 5 kali gagal dalam 1 menit, tolak tanpa mengecek password
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        // Auth::attempt mencocokkan email + password dengan hash di database
        if (! Auth::attempt(['email' => Str::lower($data['email']), 'password' => $data['password']])) {
            // Catat percobaan gagal untuk rate limiting
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        // Login berhasil: reset hitungan gagal & ganti ID session
        RateLimiter::clear($key);
        $request->session()->regenerate();

        // Redirect sesuai role: admin ke dashboard, customer ke beranda
        $tujuan = auth()->user()->isAdmin()
            ? route('admin.dashboard')
            : route('home');

        return redirect()->to($tujuan)->with('success', 'Berhasil masuk. Halo, '.auth()->user()->name.'!');
    }

    /**
     * Logout: hapus status login dan bersihkan session + token CSRF.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout(); // hapus user dari session

        $request->session()->invalidate(); // buang seluruh isi session lama
        $request->session()->regenerateToken(); // buat token CSRF baru

        return redirect()->route('home')->with('success', 'Anda telah keluar.');
    }
}
