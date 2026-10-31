@extends('layouts.app')

@section('title', 'Detail Produk')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Detail Produk</h2>
            <div class="row-actions">
                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-solid">Edit</a>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline">Kembali</a>
            </div>
        </div>

        <div class="panel product-detail">
            <div class="product-detail-image">
                @if ($product->image)
                    <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}">
                @else
                    <span class="muted">Tanpa gambar</span>
                @endif
            </div>

            <div>
                <h3>{{ $product->name }}</h3>
                <p class="muted">Slug: <code>{{ $product->slug }}</code></p>

                <p>
                    Kategori: <strong>{{ $product->category->name }}</strong><br>
                    Harga: <strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong><br>
                    Stok: <strong>{{ $product->stock }}</strong><br>
                    Status:
                    @if ($product->is_active)
                        <span class="badge badge-on">Aktif</span>
                    @else
                        <span class="badge badge-off">Nonaktif</span>
                    @endif
                </p>

                <p>{{ $product->description }}</p>
            </div>
        </div>
    </div>
@endsection
