@extends('layouts.app')

@section('title', 'Detail Pesanan')

@section('content')
    <p class="breadcrumb">
        <a href="{{ route('orders.index') }}">Pesanan Saya</a> &rsaquo; {{ $order->order_code }}
    </p>

    <div class="section">
        <div class="panel">
            <div class="order-detail-head">
                <div>
                    <h2 class="order-detail-title">{{ $order->order_code }}</h2>
                    <p class="order-meta">Dibuat {{ $order->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <span class="status-badge status-{{ $order->status }}">
                    {{ $order->statusLabel() }}
                </span>
            </div>

            <div class="order-shipping">
                <h3>Data Pengiriman</h3>
                <p><strong>{{ $order->shipping_name }}</strong></p>
                <p>{{ $order->shipping_phone }}</p>
                <p>{{ $order->shipping_address }}</p>
                @if ($order->notes)
                    <p class="muted">Catatan: {{ $order->notes }}</p>
                @endif
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="width:120px">Harga</th>
                        <th style="width:80px">Jumlah</th>
                        <th style="width:130px">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->orderItems as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="cart-summary">
                <div class="cart-summary-line cart-summary-total">
                    <span>Total</span>
                    <span>Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Pesanan yang belum dibayar tetap menampilkan instruksi transfer. --}}
            @if ($order->status === 'pending')
                @include('customer.partials.transfer-instructions', ['order' => $order])
            @endif

            <div class="order-actions">
                <a href="{{ route('orders.index') }}" class="btn btn-outline">Kembali</a>

                {{-- Tombol batal hanya muncul untuk pesanan pending milik sendiri. --}}
                @if ($order->canBeCancelled())
                    <form action="{{ route('orders.cancel', $order) }}" method="POST"
                          onsubmit="return confirm('Batalkan pesanan ini? Stok produk akan dikembalikan.');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-danger">Batalkan Pesanan</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
