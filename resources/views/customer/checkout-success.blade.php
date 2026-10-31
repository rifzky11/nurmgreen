@extends('layouts.app')

@section('title', 'Pesanan Berhasil')

@section('content')
    <div class="section">
        <div class="panel checkout-success">
            <span class="success-check" aria-hidden="true">&check;</span>
            <h2>Pesanan Berhasil Dibuat</h2>
            <p class="muted">
                Simpan kode pesanan berikut. Gunakan kode ini saat konfirmasi pembayaran.
            </p>

            <p class="order-code">{{ $order->order_code }}</p>

            <table class="table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="width:110px">Harga</th>
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
                    <span>Total Pembayaran</span>
                    <span>Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Instruksi pembayaran manual via transfer bank --}}
            <div class="transfer-box">
                <h3>Instruksi Pembayaran</h3>
                <p>Silakan transfer sesuai total pembayaran ke rekening berikut:</p>
                <ul class="transfer-list">
                    <li>Bank <strong>BCA</strong></li>
                    <li>Nomor rekening <strong>1234567890</strong></li>
                    <li>Atas nama <strong>NurmGreen</strong></li>
                </ul>
                <ol class="transfer-steps">
                    <li>Transfer sesuai nominal <strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong>.</li>
                    <li>Cantumkan kode pesanan <strong>{{ $order->order_code }}</strong> pada berita/keterangan transfer.</li>
                    <li>Simpan bukti transfer. Pesanan diproses setelah pembayaran diverifikasi admin (maks. 1&times;24 jam kerja).</li>
                </ol>
            </div>

            <div class="cart-footer">
                <a href="{{ route('catalog.index') }}" class="btn btn-solid">Lanjut Belanja</a>
            </div>
        </div>
    </div>
@endsection
