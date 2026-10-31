<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NurmGreen') | Toko Tanaman, Pupuk & Obat Tanaman</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    {{-- Toast notifikasi global (add to cart) --}}
    <div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="true"></div>
    <header class="site-header">
        <div class="wrap">
            <div class="header-main">
                <a href="{{ url('/') }}" class="logo" aria-label="NurmGreen">
                    {{-- Ikon daun sederhana, warna hijau Tokopedia-like --}}
                    <svg width="28" height="28" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                        <path d="M16 4c8 4 12 10 12 18-6 0-12-2-16-6C8 12 6 8 6 4c6 0 8 0 10 0z" fill="#03AC0E"/>
                        <path d="M16 10v16" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    nurmgreen
                </a>

                <div class="header-search">
                    <form class="search-box" action="{{ route('catalog.index') }}" method="get">
                        <button type="submit" aria-label="Cari">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                <path d="M11.7 10.3a6 6 0 1 0-1.4 1.4l3.3 3.3 1.4-1.4-3.3-3.3zM6 10a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/>
                            </svg>
                        </button>
                        <input type="search" name="q" placeholder="Cari di NurmGreen" value="{{ request('q') }}" autocomplete="off">
                    </form>
                </div>

                <div class="header-actions">
                    @php
                        // Jumlah barang di keranjang (badge ikon keranjang);
                        // hanya untuk customer — admin tidak punya keranjang.
                        $cartCount = auth()->check() && ! auth()->user()->isAdmin()
                            ? (int) auth()->user()->cartItems()->sum('quantity')
                            : 0;
                    @endphp
                    <a href="{{ auth()->check()
                            ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('cart.index'))
                            : route('login') }}"
                       class="icon-cart" aria-label="Keranjang">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M6 6h15l-1.5 9h-12z"/>
                            <path d="M6 6 5 3H2"/>
                            <circle cx="9" cy="20" r="1.4" fill="currentColor" stroke="none"/>
                            <circle cx="18" cy="20" r="1.4" fill="currentColor" stroke="none"/>
                        </svg>
                        @if (auth()->check() && ! auth()->user()->isAdmin())
                            {{-- Selalu ada untuk customer agar bisa diperbarui via AJAX --}}
                            <span class="cart-count" id="cartCountBadge"
                                  @if ($cartCount === 0) hidden @endif>{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
                        @endif
                    </a>

                    @auth
                        {{-- Navigasi utama diringkas ke satu menu dropdown (burger) --}}
                        <div class="nav-dropdown">
                            <button type="button" class="nav-toggle" id="navToggle"
                                    aria-expanded="false" aria-controls="navMenu"
                                    aria-haspopup="true" aria-label="Buka menu navigasi">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <path d="M4 7h16M4 12h16M4 17h16"/>
                                </svg>
                            </button>

                            <nav class="nav-menu" id="navMenu" aria-label="Menu utama" hidden>
                                <div class="nav-menu-head">
                                    <div class="nav-user">
                                        <span class="nav-menu-name">{{ auth()->user()->name }}</span>
                                        <span class="nav-menu-email">{{ auth()->user()->email }}</span>
                                    </div>
                                    <span class="nav-role">
                                        {{ auth()->user()->isAdmin() ? 'Admin' : 'Customer' }}
                                    </span>
                                </div>

                                <div class="nav-menu-divider"></div>

                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}"
                                       @class(['nav-link', 'is-active' => request()->routeIs('admin.dashboard')])>
                                        Dashboard
                                    </a>
                                    <a href="{{ route('admin.orders.index') }}"
                                       @class(['nav-link', 'is-active' => request()->routeIs('admin.orders.*')])>
                                        Pesanan
                                    </a>
                                    <a href="{{ route('admin.products.index') }}"
                                       @class(['nav-link', 'is-active' => request()->routeIs('admin.products.*')])>
                                        Produk
                                    </a>
                                    <a href="{{ route('admin.categories.index') }}"
                                       @class(['nav-link', 'is-active' => request()->routeIs('admin.categories.*')])>
                                        Kategori
                                    </a>
                                    <a href="{{ route('home') }}" class="nav-link">Lihat Toko</a>
                                @else
                                    <a href="{{ route('home') }}" class="nav-link">Beranda</a>
                                    <a href="{{ route('catalog.index') }}"
                                       @class(['nav-link', 'is-active' => request()->routeIs('catalog.*')])>
                                        Katalog
                                    </a>
                                    <a href="{{ route('cart.index') }}"
                                       @class(['nav-link', 'is-active' => request()->routeIs('cart.*')])>
                                        Keranjang
                                    </a>
                                    <a href="{{ route('addresses.index') }}"
                                       @class(['nav-link', 'is-active' => request()->routeIs('addresses.*')])>
                                        Alamat Saya
                                    </a>
                                    <a href="{{ route('orders.index') }}"
                                       @class(['nav-link', 'is-active' => request()->routeIs('orders.*')])>
                                        Pesanan Saya
                                    </a>
                                @endif

                                <div class="nav-menu-divider"></div>

                                <form action="{{ route('logout') }}" method="POST" class="nav-logout">
                                    @csrf
                                    <button type="submit">Keluar</button>
                                </form>
                            </nav>
                        </div>
                    @else
                        {{-- Belum login: tombol utama tetap terlihat langsung --}}
                        <a href="{{ route('login') }}" class="btn btn-outline">Masuk</a>
                        <a href="{{ route('register') }}" class="btn btn-solid">Daftar</a>
                    @endauth
                </div>
            </div>

            <div class="header-loc">
                <p>
                    Dikirim ke <strong> Alamat Kamu</strong>
                </p>
            </div>
        </div>
    </header>

    <main class="site-main">
        <div class="wrap">
            @if (session('success'))
                <div class="flash flash-ok">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="flash flash-err">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="site-footer">
        <p class="footer-copy">&copy; {{ date('Y') }} NurmGreen. Toko tanaman, pupuk, dan obat tanaman.</p>
    </footer>

    {{-- Toggle menu burger: klik tombol, klik di luar, atau tombol Escape untuk menutup --}}
    <script>
        (() => {
            const toggle = document.getElementById('navToggle');
            const menu = document.getElementById('navMenu');
            if (!toggle || !menu) return;

            const closeMenu = () => {
                menu.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
            };

            toggle.addEventListener('click', (event) => {
                event.stopPropagation();
                const willOpen = menu.hidden;
                menu.hidden = !willOpen;
                toggle.setAttribute('aria-expanded', String(willOpen));
            });

            document.addEventListener('click', (event) => {
                if (!menu.hidden && !menu.contains(event.target) && !toggle.contains(event.target)) {
                    closeMenu();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeMenu();
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
