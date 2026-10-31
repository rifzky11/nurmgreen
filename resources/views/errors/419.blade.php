@extends('errors.layout')

@section('title', 'Sesi Telah Berakhir')
@section('code', '419')
@section('heading', 'Sesi Telah Berakhir')
@section('message', 'Token keamanan (CSRF) sudah kedaluwarsa. Silakan kembali dan coba lagi.')

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-solid">Kembali ke Beranda</a>
    <a href="{{ url('/login') }}" class="btn btn-outline">Masuk Kembali</a>
@endsection
