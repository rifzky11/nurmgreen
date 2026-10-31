@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    {{-- Banner promo utama, mengarah ke katalog --}}
    <a href="{{ route('catalog.index') }}" class="banner">
        <div class="banner-overlay">
            <h1>Malas ke kebun? Belanja tanaman dari rumah.</h1>
        </div>
    </a>

    @php
        // Ikon kategori per slug, dengan fallback untuk kategori baru
        $catIcons = [
            'tanaman-hias' => '🌿',
            'tanaman-indoor' => '🪴',
            'bibit-benih' => '🌱',
            'pupuk-media' => '🧴',
            'obat-tanaman' => '🛡️',
            'pot-media' => '🪣',
        ];
    @endphp

    <section class="section">
        <div class="section-head">
            <h2>Kategori Populer</h2>
            <a href="{{ route('catalog.index') }}">Lihat Semua</a>
        </div>
        <div class="cat-grid">
            @forelse ($categories as $category)
                <a class="cat-item" href="{{ route('catalog.index', ['category' => $category->slug]) }}">
                    <span class="cat-icon">{{ $catIcons[$category->slug] ?? '🌱' }}</span>
                    {{ $category->name }}
                </a>
            @empty
                <p class="muted">Belum ada kategori.</p>
            @endforelse
        </div>
    </section>

    {{-- Produk terbaru dari database, hanya yang aktif --}}
    <section class="section">
        <div class="section-head">
            <h2>Produk Terbaru</h2>
            <a href="{{ route('catalog.index') }}">Lihat Semua</a>
        </div>

        @if ($products->isNotEmpty())
            <div class="product-grid catalog-grid">
                @foreach ($products as $product)
                    @include('catalog._card', ['product' => $product])
                @endforeach
            </div>
        @else
            <div class="panel empty-state">
                <p>Belum ada produk yang tayang. Silakan cek kembali nanti.</p>
            </div>
        @endif
    </section>
@endsection
