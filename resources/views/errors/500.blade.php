@extends('errors.layout')

@section('title', 'Terjadi Kesalahan')
@section('code', '500')
@section('heading', 'Terjadi Kesalahan')
@section('message', 'Maaf, ada gangguan pada server. Silakan coba beberapa saat lagi.')

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-solid">Kembali ke Beranda</a>
@endsection
