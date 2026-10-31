<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Terjadi Kesalahan') | NurmGreen</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="error-body">
    {{-- Halaman error sengaja tidak memakai layouts.app agar tetap tampil
         walau session/database bermasalah. --}}
    <main class="error-page">
        <a href="{{ url('/') }}" class="error-logo" aria-label="NurmGreen">
            <svg width="28" height="28" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                <path d="M16 4c8 4 12 10 12 18-6 0-12-2-16-6C8 12 6 8 6 4c6 0 8 0 10 0z" fill="#03AC0E"/>
                <path d="M16 10v16" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
            </svg>
            nurmgreen
        </a>

        <section class="error-card">
            <span class="error-code">@yield('code')</span>
            <h1>@yield('heading')</h1>
            <p class="error-message">@yield('message')</p>
            <div class="error-actions">@yield('actions')</div>
        </section>

        <p class="error-footer">
            &copy; {{ date('Y') }} NurmGreen. Toko tanaman, pupuk, dan obat tanaman.
        </p>
    </main>
</body>
</html>
