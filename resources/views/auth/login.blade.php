@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Masuk</h1>
            <p class="auth-sub">Silakan masuk ke akun NurmGreen kamu.</p>

            {{-- action ke route POST /login, token CSRF wajib disertakan --}}
            <form action="{{ route('login') }}" method="POST" novalidate>
                @csrf

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="{{ old('email') }}" maxlength="255" required autofocus>
                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                    @error('password')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-solid auth-submit">Masuk</button>
            </form>

            <p class="auth-alt">
                Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>
            </p>
        </div>
    </div>
@endsection
