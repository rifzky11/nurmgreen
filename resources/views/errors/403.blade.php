@extends('layouts.app')

@section('title', 'Akses Ditolak')

@section('content')
    {{-- Halaman ini tampil otomatis saat controller/middleware memanggil abort(403) --}}
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>403 — Akses Ditolak</h1>
            <p class="auth-sub">
                Maaf, akun kamu tidak memiliki izin untuk membuka halaman ini.
            </p>

            @auth
                <p class="muted">
                    Kamu masuk sebagai <strong>{{ auth()->user()->role }}</strong>.
                    Silakan kembali ke halaman yang sesuai dengan role kamu.
                </p>

                <p>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-solid">Ke Dashboard Admin</a>
                    @else
                        <a href="{{ route('home') }}" class="btn btn-solid">Kembali ke Beranda</a>
                    @endif
                </p>
            @else
                <p><a href="{{ route('login') }}" class="btn btn-solid">Masuk</a></p>
            @endauth
        </div>
    </div>
@endsection
