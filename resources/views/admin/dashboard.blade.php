@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Dashboard Admin</h2>
        </div>

        <div class="panel">
            <p>Halo, <strong>{{ auth()->user()->name }}</strong>. Kamu masuk sebagai <strong>admin</strong>.</p>
            <p class="muted">Halaman ini masih placeholder. Statistik toko dan menu pengelolaan
                (kategori, produk, pesanan) akan ditambahkan pada fitur berikutnya.</p>
        </div>
    </div>
@endsection
