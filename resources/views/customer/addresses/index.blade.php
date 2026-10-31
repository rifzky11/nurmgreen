@extends('layouts.app')

@section('title', 'Alamat Saya')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Alamat Saya</h2>
            <span class="muted">{{ $addresses->count() }} alamat tersimpan</span>
        </div>

        @if ($addresses->isEmpty())
            <div class="panel empty-state">
                <p>Belum ada alamat tersimpan. Tambahkan alamat pertama kamu di bawah
                    agar checkout berikutnya lebih cepat.</p>
            </div>
        @else
            <div class="address-list">
                @foreach ($addresses as $address)
                    <div class="panel address-card">
                        <div class="address-card-head">
                            <span class="address-label">{{ $address->label }}</span>
                            @if ($address->is_default)
                                <span class="nav-role">Utama</span>
                            @endif
                        </div>

                        <div><strong>{{ $address->recipient_name }}</strong></div>
                        <div class="muted">{{ $address->phone }}</div>
                        <p class="address-text">{{ $address->address }}</p>

                        <div class="address-actions">
                            <a href="{{ route('addresses.edit', $address) }}" class="btn btn-outline">Ubah</a>

                            @if (! $address->is_default)
                                <form action="{{ route('addresses.default', $address) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline">Jadikan Utama</button>
                                </form>
                            @endif

                            <form action="{{ route('addresses.destroy', $address) }}" method="POST"
                                  onsubmit="return confirm('Hapus alamat ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Hapus</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="panel address-form-panel">
            <h3 class="checkout-title">Tambah Alamat</h3>

            @include('customer.addresses._form', [
                'action' => route('addresses.store'),
                'method' => 'POST',
                'address' => null,
                'submitLabel' => 'Simpan Alamat',
            ])
        </div>
    </div>
@endsection
