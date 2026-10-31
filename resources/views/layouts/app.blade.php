<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NurmGreen') | Toko Tanaman, Pupuk & Obat Tanaman</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
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
                    </a>
                    <span class="divider"></span>
                    @auth
                        {{-- Menu berbeda sesuai role --}}
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">Dashboard</a>
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">Kategori</a>
                            <a href="{{ route('admin.products.index') }}" class="btn btn-outline">Produk</a>
                        @else
                            <a href="{{ route('orders.index') }}" class="btn btn-outline">Pesanan</a>
                        @endif

                        {{-- Sudah login: tampilkan nama & tombol keluar --}}
                        <span class="user-chip">{{ auth()->user()->name }}</span>
                        <form action="{{ route('logout') }}" method="POST" class="logout-form">
                            @csrf
                            <button type="submit" class="btn btn-outline">Keluar</button>
                        </form>
                    @else
                        {{-- Belum login: arahkan ke halaman auth --}}
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
</body>
</html>
