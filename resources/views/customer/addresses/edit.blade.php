@extends('layouts.app')

@section('title', 'Ubah Alamat')

@section('content')
    <p class="breadcrumb">
        <a href="{{ route('addresses.index') }}">Alamat Saya</a> &rsaquo; Ubah {{ $address->label }}
    </p>

    <div class="section">
        <div class="panel address-form-panel">
            <h3 class="checkout-title">Ubah Alamat</h3>

            @include('customer.addresses._form', [
                'action' => route('addresses.update', $address),
                'method' => 'PUT',
                'address' => $address,
                'submitLabel' => 'Simpan Perubahan',
            ])
        </div>
    </div>
@endsection
