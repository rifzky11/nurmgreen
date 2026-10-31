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
                <span class="status-badge status-{{ $order->statusColor() }}">
                    {{ $order->statusLabel() }}
                </span>
            </div>

            {{-- Timeline status pesanan; cancelled tampil sebagai satu state. --}}
            @if ($order->status === 'cancelled')
                <div class="status-cancelled-state">
                    <span class="status-badge status-{{ $order->statusColor() }}">
                        {{ $order->statusLabel() }}
                    </span>
                    <p class="muted">Pesanan ini telah dibatalkan.</p>
                </div>
            @else
                @php
                    $labels = \App\Models\Order::statusLabels();
                    $steps = [
                        'pending' => $order->created_at,
                        'paid' => null,
                        'shipped' => $order->shipped_at,
                        'completed' => $order->completed_at,
                    ];
                    $keys = array_keys($steps);
                    $currentIndex = array_search($order->status, $keys, true);
                    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
                @endphp

                <ol class="status-stepper" aria-label="Status pesanan">
                    @foreach ($steps as $key => $timestamp)
                        <li @class([
                            'step',
                            'is-done' => $loop->index < $currentIndex,
                            'is-current' => $loop->index === $currentIndex,
                        ])>
                            <span class="step-label">{{ $labels[$key] }}</span>
                            @if ($timestamp)
                                <span class="step-time">{{ $timestamp->format('d/m/Y H:i') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif

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

            {{-- Bukti pembayaran: unggah hanya untuk pemilik & status pending --}}
            <div class="payment-proof-panel" id="upload-bukti">
                <h3>Bukti Pembayaran</h3>

                @if ($order->hasPaymentProof())
                    <div class="payment-proof-preview">
                        <a href="{{ route('orders.payment-proof', $order) }}" target="_blank" rel="noopener">
                            <img src="{{ route('orders.payment-proof', $order) }}"
                                 alt="Bukti pembayaran {{ $order->order_code }}" class="proof-thumb">
                        </a>
                        <div>
                            <p>Diunggah {{ $order->payment_proof_uploaded_at?->format('d/m/Y H:i') }}</p>

                            @if ($order->status === 'pending')
                                <span class="badge badge-low">Menunggu verifikasi admin</span>
                            @elseif ($order->status === 'cancelled')
                                <span class="badge badge-off">Pesanan dibatalkan</span>
                            @else
                                <span class="badge badge-on">Sudah diverifikasi</span>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="muted">Belum ada bukti pembayaran diunggah.</p>
                @endif

                @if ($order->status === 'pending')
                    <form action="{{ route('orders.upload-proof', $order) }}" method="POST"
                          enctype="multipart/form-data" class="proof-form">
                        @csrf

                        <div class="form-group">
                            <label for="payment_proof">
                                {{ $order->hasPaymentProof() ? 'Ganti Bukti Pembayaran' : 'Upload Bukti Pembayaran' }}
                            </label>
                            <input type="file" id="payment_proof" name="payment_proof" class="form-control"
                                   accept="image/jpeg,image/png,image/webp" required>
                            <span class="form-hint">Format JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.</span>
                            @error('payment_proof')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-solid">
                            {{ $order->hasPaymentProof() ? 'Unggah Ulang' : 'Unggah Bukti' }}
                        </button>
                    </form>
                @endif
            </div>

            <div class="order-actions">
                <a href="{{ route('orders.index') }}" class="btn btn-outline">Kembali</a>

                {{-- Konfirmasi diterima: hanya pemilik & status shipped (dicek Policy). --}}
                @if ($order->canBeReceived())
                    <form action="{{ route('orders.received', $order) }}" method="POST"
                          onsubmit="return confirm('Konfirmasi bahwa pesanan sudah kamu terima?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-solid">Pesanan Sudah Diterima</button>
                    </form>
                @endif

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
