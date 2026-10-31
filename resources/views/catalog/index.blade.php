@extends('layouts.app')

@section('title', 'Katalog')

@section('content')
    <section class="section">
        <div class="section-head">
            <h2>{{ $category ? 'Kategori: '.$category->name : 'Katalog Produk' }}</h2>
            <span class="muted">{{ $products->total() }} produk</span>
        </div>

        {{-- Filter memakai GET supaya kondisi katalog tersimpan di URL
             (?q=...&category=...&sort=...) dan bisa dibagikan --}}
        <form action="{{ route('catalog.index') }}" method="GET" class="filter-bar">
            <input type="search" name="q" value="{{ $q }}" class="form-control"
                   placeholder="Cari nama produk..." aria-label="Cari nama produk">

            <select name="category" class="form-control" aria-label="Filter kategori">
                <option value="">Semua kategori</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->slug }}"
                            @selected($category && $category->id === $c->id)>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>

            <select name="sort" class="form-control" aria-label="Urutkan produk">
                <option value="">Paling Baru</option>
                <option value="price_asc" @selected($sort === 'price_asc')>Harga Terendah</option>
                <option value="price_desc" @selected($sort === 'price_desc')>Harga Tertinggi</option>
            </select>

            <button type="submit" class="btn btn-solid">Terapkan</button>
            <a href="{{ route('catalog.index') }}" class="btn btn-outline">Reset</a>
        </form>

        @if ($products->isNotEmpty())
            <div class="product-grid catalog-grid">
                @foreach ($products as $product)
                    @include('catalog._card', ['product' => $product])
                @endforeach
            </div>
        @else
            <div class="panel empty-state">
                <p>Tidak ada produk yang cocok dengan pencarian atau filter.</p>
                <a href="{{ route('catalog.index') }}" class="btn btn-outline">Lihat semua produk</a>
            </div>
        @endif

        <div class="pagination-wrap">
            {{ $products->links('pagination.simple-green') }}
        </div>
    </section>
@endsection
