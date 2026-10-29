@extends('layouts.app')

@section('title', $title)

@section('content')
    {{-- Halaman sementara untuk route customer yang fiturnya belum dibuat --}}
    <div class="section">
        <div class="section-head">
            <h2>{{ $title }}</h2>
        </div>

        <div class="panel">
            <p>Halaman <strong>{{ $title }}</strong> masih placeholder.</p>
            <p class="muted">Fitur ini akan dibuat pada tahap berikutnya. Route-nya sudah
                dilindungi middleware <code>auth</code> dan <code>role:customer</code>.</p>
            <p><a href="{{ route('home') }}" class="btn btn-solid">Kembali ke Beranda</a></p>
        </div>
    </div>
@endsection
