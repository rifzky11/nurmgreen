@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Profil Saya</h2>
            <span class="muted">{{ $user->email }}</span>
        </div>

        <div class="checkout-grid">
            {{-- Data profil: hanya nama, nomor HP, dan alamat yang bisa diubah --}}
            <div class="panel">
                <h3 class="checkout-title">Data Profil</h3>

                <form action="{{ route('profile.update') }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="name">Nama Lengkap</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="{{ old('name', $user->name) }}" maxlength="100" required autofocus>
                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" class="form-control"
                               value="{{ $user->email }}" disabled>
                        <span class="form-hint">Email tidak dapat diubah.</span>
                    </div>

                    <div class="form-group">
                        <label for="phone">Nomor HP</label>
                        <input type="tel" id="phone" name="phone" class="form-control"
                               value="{{ old('phone', $user->phone) }}" maxlength="20"
                               placeholder="08xxxxxxxxxx">
                        @error('phone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="address">Alamat</label>
                        <textarea id="address" name="address" class="form-control" rows="3"
                                  maxlength="500">{{ old('address', $user->address) }}</textarea>
                        @error('address')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-solid">Simpan Profil</button>
                </form>
            </div>

            {{-- Ubah password: wajib memverifikasi password saat ini --}}
            <aside class="panel">
                <h3 class="checkout-title">Ubah Password</h3>

                <form action="{{ route('profile.password') }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="current_password">Password Saat Ini</label>
                        <input type="password" id="current_password" name="current_password"
                               class="form-control" autocomplete="current-password" required>
                        @error('current_password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Password Baru</label>
                        <input type="password" id="password" name="password" class="form-control"
                               autocomplete="new-password" minlength="8" required>
                        <span class="form-hint">Minimal 8 karakter.</span>
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Konfirmasi Password Baru</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-control" autocomplete="new-password" minlength="8" required>
                    </div>

                    <button type="submit" class="btn btn-solid">Ubah Password</button>
                </form>
            </aside>
        </div>
    </div>
@endsection
