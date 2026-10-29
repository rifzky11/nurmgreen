@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    {{-- Banner promo utama --}}
    <a href="{{ url('/') }}" class="banner">
        <div class="banner-overlay">
            <h1>Malas ke kebun? Belanja tanaman dari rumah.</h1>
            <span class="btn">Cek Sekarang</span>
        </div>
    </a>

    <section class="section">
        <div class="section-head">
            <h2>Kategori Populer</h2>
            <a href="{{ url('/') }}">Lihat Semua</a>
        </div>
        <div class="cat-grid">
            <a class="cat-item" href="{{ url('/') }}">
                <span class="cat-icon">🌿</span>
                Tanaman Hias
            </a>
            <a class="cat-item" href="{{ url('/') }}">
                <span class="cat-icon">🪴</span>
                Tanaman Indoor
            </a>
            <a class="cat-item" href="{{ url('/') }}">
                <span class="cat-icon">🌱</span>
                Bibit &amp; Benih
            </a>
            <a class="cat-item" href="{{ url('/') }}">
                <span class="cat-icon">🧴</span>
                Pupuk
            </a>
            <a class="cat-item" href="{{ url('/') }}">
                <span class="cat-icon">🛡️</span>
                Obat Tanaman
            </a>
            <a class="cat-item" href="{{ url('/') }}">
                <span class="cat-icon">🪣</span>
                Pot &amp; Media
            </a>
        </div>
    </section>

    {{-- Kartu produk dummy (data asli menyusul setelah katalog) --}}
    <section class="section">
        <div class="section-head">
            <h2>Untuk Kamu</h2>
            <a href="{{ url('/') }}">Lihat Semua</a>
        </div>
        <div class="product-grid">
            <article class="card">
                <img src="{{ asset('images/p1.svg') }}" alt="Monstera Deliciosa">
                <div class="card-body">
                    <p class="card-title">Monstera Deliciosa pot 50cm tanaman hias indoor</p>
                    <p class="card-price">Rp85.000</p>
                    <p class="old-price">Rp110.000 <span class="badge-disc">23%</span></p>
                    <p class="card-meta">★ 4.9 · 120 terjual</p>
                </div>
            </article>
            <article class="card">
                <img src="{{ asset('images/p2.svg') }}" alt="Kaktus mini">
                <div class="card-body">
                    <p class="card-title">Kaktus mini pot terracotta succulent campur</p>
                    <p class="card-price">Rp18.500</p>
                    <p class="old-price">Rp25.000 <span class="badge-disc">26%</span></p>
                    <p class="card-meta">★ 4.8 · 340 terjual</p>
                </div>
            </article>
            <article class="card">
                <img src="{{ asset('images/p3.svg') }}" alt="Pupuk kompos">
                <div class="card-body">
                    <p class="card-title">Pupuk kompos organik 5kg media tanam subur</p>
                    <p class="card-price">Rp32.000</p>
                    <p class="card-meta">★ 4.7 · 89 terjual</p>
                </div>
            </article>
            <article class="card">
                <img src="{{ asset('images/p4.svg') }}" alt="Bibit pakcoy">
                <div class="card-body">
                    <p class="card-title">Bibit pakcoy unggul siap tanam 50 batang</p>
                    <p class="card-price">Rp12.000</p>
                    <p class="old-price">Rp15.000 <span class="badge-disc">20%</span></p>
                    <p class="card-meta">★ 4.6 · 210 terjual</p>
                </div>
            </article>
            <article class="card">
                <img src="{{ asset('images/p5.svg') }}" alt="Fungisida">
                <div class="card-body">
                    <p class="card-title">Fungisida nabati 250ml obat jamur daun</p>
                    <p class="card-price">Rp27.900</p>
                    <p class="card-meta">★ 4.8 · 64 terjual</p>
                </div>
            </article>
            <article class="card">
                <img src="{{ asset('images/p6.svg') }}" alt="Lidah mertua">
                <div class="card-body">
                    <p class="card-title">Lidah mertua Sansevieria pot 30cm pembersih udara</p>
                    <p class="card-price">Rp45.000</p>
                    <p class="card-meta">★ 4.5 · 41 terjual</p>
                </div>
            </article>
        </div>
    </section>
@endsection
