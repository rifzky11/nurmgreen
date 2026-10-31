@extends('errors.layout')

@section('title', 'Halaman Tidak Ditemukan')
@section('code', '404')
@section('heading', 'Halaman Tidak Ditemukan')
@section('message', 'Halaman atau produk yang kamu cari tidak tersedia. Mungkin tautannya salah atau sudah dihapus.')

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-solid">Kembali ke Beranda</a>
    <a href="{{ url('/katalog') }}" class="btn btn-outline">Lihat Katalog</a>
@endsection
