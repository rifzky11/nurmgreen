@extends('errors.layout')

@section('title', 'Akses Ditolak')
@section('code', '403')
@section('heading', 'Akses Ditolak')
@section('message', 'Maaf, akun kamu tidak memiliki izin untuk membuka halaman ini.')

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-solid">Kembali ke Beranda</a>
    <a href="{{ url('/login') }}" class="btn btn-outline">Masuk dengan Akun Lain</a>
@endsection
