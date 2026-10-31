@extends('layouts.app')

@section('title', 'Ubah Kategori')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Ubah Kategori</h2>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">Kembali</a>
        </div>

        <div class="panel form-panel">
            @include('admin.categories._form')
        </div>
    </div>
@endsection
