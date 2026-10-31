@extends('layouts.app')

@section('title', 'Detail Pesanan Admin')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Detail Pesanan</h2>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline">Kembali</a>
        </div>

        @if ($errors->any())
            <div class="flash flash-err">{{ $errors->first() }}</div>
        @endif

        <div class="panel">
            <div class="order-detail-head">
                <div>
                    <h2 class="order-detail-title">{{ $order->order_code }}</h2>
                    <p class="order-meta">Dibuat {{ $order->created_at->format('d/m/Y H:i') }}</p>
                    @if ($order->shipped_at)
                        <p class="order-meta">Dikirim {{ $order->shipped_at->format('d/m/Y H:i') }}</p>
                    @endif
                    @if ($order->completed_at)
                        <p class="order-meta">Diterima {{ $order->completed_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
                <span class="status-badge status-{{ $order->statusColor() }}">
                    {{ $order->statusLabel() }}
                </span>
            </div>

            <div class="admin-order-info">
                <div class="order-shipping">
                    <h3>Customer</h3>
                    <p><strong>{{ $order->user->name }}</strong></p>
                    <p>{{ $order->user->email }}</p>
                    @if ($order->user->phone)
                        <p>{{ $order->user->phone }}</p>
                    @endif
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
            </div>

            {{-- Bukti pembayaran dari customer (file disk privat, dibuka via route) --}}
            <div class="payment-proof-panel">
                <h3>Bukti Pembayaran</h3>

                @if ($order->hasPaymentProof())
                    <div class="payment-proof-preview">
                        <a href="{{ route('orders.payment-proof', $order) }}"
                           target="_blank" rel="noopener">
                            <img src="{{ route('orders.payment-proof', $order) }}"
                                 alt="Bukti pembayaran {{ $order->order_code }}" class="proof-thumb">
                        </a>
                        <div>
                            <p>Diunggah {{ $order->payment_proof_uploaded_at?->format('d/m/Y H:i') }}</p>
                            <a href="{{ route('orders.payment-proof', $order) }}"
                               target="_blank" rel="noopener" class="btn btn-outline">
                                Lihat Ukuran Penuh
                            </a>
                        </div>
                    </div>
                @else
                    <p class="muted">Belum ada bukti pembayaran.</p>
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

            {{-- Form ubah status: pilihan hanya transisi yang sah dari status saat ini --}}
            <div class="status-panel">
                <h3>Ubah Status</h3>

                @php($nextStatuses = $order->nextStatuses())

                @if (empty($nextStatuses))
                    <p class="muted">
                        Status "{{ $order->statusLabel() }}" bersifat final, tidak ada perubahan status lagi.
                    </p>
                @else
                    <p class="muted">
                        Status saat ini: <strong>{{ $order->statusLabel() }}</strong>.
                        Pilihan di bawah hanya transisi yang diizinkan.
                    </p>

                    <form action="{{ route('admin.orders.update-status', $order) }}" method="POST"
                          class="status-form">
                        @csrf
                        @method('PATCH')

                        <select name="status" class="form-control" aria-label="Status baru" required>
                            <option value="">Pilih status baru</option>
                            @foreach ($nextStatuses as $status)
                                <option value="{{ $status }}">{{ $statusLabels[$status] }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-solid">Simpan Status</button>
                    </form>

                    @if (in_array('cancelled', $nextStatuses, true))
                        <p class="form-hint">
                            Membatalkan pesanan akan mengembalikan stok semua produk ke inventori.
                        </p>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
