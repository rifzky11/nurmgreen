@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <p class="breadcrumb">
        <a href="{{ route('catalog.index') }}">Katalog</a> &rsaquo;
        <a href="{{ route('catalog.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a>
        &rsaquo; {{ $product->name }}
    </p>

    <div class="product-detail panel">
        <div class="product-detail-image">
            @if ($product->image)
                <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}">
            @else
                <div class="card-img-fallback detail-fallback" aria-hidden="true">🌿</div>
            @endif
        </div>

        <div>
            <h3>{{ $product->name }}</h3>
            <p class="muted">
                Kategori:
                <a href="{{ route('catalog.index', ['category' => $product->category->slug]) }}">
                    {{ $product->category->name }}
                </a>
            </p>

            <p class="detail-price">Rp {{ number_format($product->price, 0, ',', '.') }}</p>

            {{-- Status stok ditampilkan jelas sesuai kondisi stok --}}
            @if ($product->stock === 0)
                <div class="stock-note stock-oos">
                    <strong>Stok Habis.</strong> Produk ini belum bisa dibeli.
                </div>
            @elseif ($product->stock <= 5)
                <div class="stock-note stock-low">
                    Stok menipis: sisa <strong>{{ $product->stock }}</strong>.
                </div>
            @else
                <div class="stock-note stock-ok">
                    Tersedia &mdash; stok <strong>{{ $product->stock }}</strong>.
                </div>
            @endif

            <div class="detail-actions">
                @if ($product->stock > 0)
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-solid">Masuk untuk Membeli</a>
                    @elseif (auth()->user()->isAdmin())
                        <span class="muted">Pratinjau admin &mdash; keranjang hanya untuk customer.</span>
                    @else
                        <a href="{{ route('cart.index') }}" class="btn btn-solid">+ Keranjang</a>
                    @endguest
                @else
                    <span class="btn btn-disabled">+ Keranjang</span>
                @endif
            </div>

            <h4 class="detail-subhead">Deskripsi</h4>
            <p class="detail-desc">{{ $product->description }}</p>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="section">
            <div class="section-head">
                <h2>Produk Serupa</h2>
            </div>
            <div class="product-grid catalog-grid">
                @foreach ($related as $product)
                    @include('catalog._card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif
@endsection
