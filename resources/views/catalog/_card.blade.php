{{-- Kartu produk katalog; dipakai di beranda, katalog, dan produk serupa --}}
<a href="{{ route('catalog.show', $product) }}"
   class="card card-link {{ $product->stock === 0 ? 'is-oos' : '' }}">
    <div class="card-media">
        @if ($product->image)
            <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <span class="card-img-fallback" aria-hidden="true">🌿</span>
        @endif

        {{-- Status stok di atas gambar supaya terlihat jelas --}}
        @if ($product->stock === 0)
            <span class="badge badge-oos">Stok Habis</span>
        @elseif ($product->stock <= 5)
            <span class="badge badge-low">Sisa {{ $product->stock }}</span>
        @endif
    </div>
    <div class="card-body">
        <p class="card-title">{{ $product->name }}</p>
        <p class="card-price">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
        <p class="card-meta">{{ $product->category->name }}</p>
    </div>
</a>
