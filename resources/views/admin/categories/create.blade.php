@extends('layouts.app')

@section('title', 'Tambah Kategori')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Tambah Kategori</h2>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">Kembali</a>
        </div>

        <div class="panel form-panel">
            @include('admin.categories._form')
        </div>
    </div>
@endsection
