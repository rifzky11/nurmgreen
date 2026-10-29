@extends('layouts.app')

@section('title', 'Daftar Akun')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Daftar Akun</h1>
            <p class="auth-sub">Buat akun NurmGreen untuk mulai belanja.</p>

            {{-- action ke route POST /register, token CSRF wajib disertakan --}}
            <form action="{{ route('register') }}" method="POST" novalidate>
                @csrf

                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="{{ old('name') }}" maxlength="100" required autofocus>
                    @error('name')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="{{ old('email') }}" maxlength="255" required>
                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           minlength="8" required>
                    <span class="form-hint">Minimal 8 karakter.</span>
                    @error('password')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control" minlength="8" required>
                </div>

                {{-- Tombol submit biasa; tidak perlu name agar tidak ikut tervalidasi --}}
                <button type="submit" class="btn btn-solid auth-submit">Daftar</button>
            </form>

            <p class="auth-alt">
                Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
            </p>
        </div>
    </div>
@endsection
