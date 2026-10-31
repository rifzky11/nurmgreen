@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Tambah Produk</h2>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline">Kembali</a>
        </div>

        <div class="panel form-panel">
            @include('admin.products._form')
        </div>
    </div>
@endsection
