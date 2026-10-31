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
                        {{-- Tambah ke keranjang: quantity bertambah jika produk sudah ada --}}
                        <form action="{{ route('cart.store', $product) }}" method="POST"
                              class="cart-qty-form js-add-to-cart"
                              data-login-url="{{ route('login') }}">
                            @csrf
                            <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}"
                                   class="form-control qty-input" aria-label="Jumlah">
                            <button type="submit" class="btn btn-solid">+ Keranjang</button>
                        </form>
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

@push('scripts')
    <script>
        (() => {
            const toastContainer = document.getElementById('toast-container');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            const showToast = (message, type = 'success') => {
                if (!toastContainer) return;

                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;
                toast.textContent = message; // textContent, bukan innerHTML

                toastContainer.appendChild(toast);
                setTimeout(() => {
                    toast.classList.add('toast-hide');
                    setTimeout(() => toast.remove(), 250);
                }, 3000);
            };

            const updateCartBadge = (count) => {
                const badge = document.getElementById('cartCountBadge');
                if (!badge) return;

                badge.hidden = count === 0;
                badge.textContent = count > 99 ? '99+' : String(count);
            };

            document.querySelectorAll('.js-add-to-cart').forEach((form) => {
                const button = form.querySelector('button[type="submit"]');
                const loginUrl = form.dataset.loginUrl;

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (button?.disabled) return;
                    if (button) button.disabled = true;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                        });

                        // Sesi habis: arahkan ke login setelah toast tampil.
                        if (response.status === 401) {
                            showToast('Silakan masuk dulu untuk menambahkan produk ke keranjang.', 'error');
                            setTimeout(() => { if (loginUrl) window.location.href = loginUrl; }, 1200);
                            return;
                        }

                        // Token CSRF kedaluwarsa.
                        if (response.status === 419) {
                            showToast('Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.', 'error');
                            return;
                        }

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            showToast(data.message || 'Gagal menambahkan produk.', 'error');
                            return;
                        }

                        updateCartBadge(Number(data.cart_count || 0));
                        showToast(data.message || 'Produk ditambahkan ke keranjang.', 'success');
                    } catch (error) {
                        showToast('Terjadi kesalahan jaringan. Coba lagi.', 'error');
                    } finally {
                        if (button) button.disabled = false;
                    }
                });
            });
        })();
    </script>
@endpush
