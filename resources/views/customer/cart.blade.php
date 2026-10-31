@extends('layouts.app')

@section('title', 'Keranjang')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Keranjang</h2>
            <span class="muted">{{ $items->count() }} jenis produk</span>
        </div>

        @if ($errors->any())
            <div class="flash flash-err">{{ $errors->first() }}</div>
        @endif

        @if ($items->isEmpty())
            <div class="panel empty-state">
                <p>Keranjang kamu masih kosong.</p>
                <a href="{{ route('catalog.index') }}" class="btn btn-solid">Mulai Belanja</a>
            </div>
        @else
            <div class="panel">
                <table class="table cart-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="width:120px">Harga</th>
                            <th style="width:150px">Jumlah</th>
                            <th style="width:120px">Subtotal</th>
                            <th style="width:80px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            @php
                                $product = $item->product;
                                $subtotal = $product->price * $item->quantity;
                                $stokKurang = $product->stock < $item->quantity;
                            @endphp
                            <tr>
                                <td>
                                    <div class="cart-product">
                                        @if ($product->image)
                                            <img src="{{ asset('storage/'.$product->image) }}"
                                                 alt="{{ $product->name }}" class="thumb">
                                        @else
                                            <span class="thumb thumb-fallback">🌿</span>
                                        @endif
                                        <div>
                                            <strong>
                                                <a href="{{ route('catalog.show', $product) }}" class="cart-product-name">
                                                    {{ $product->name }}
                                                </a>
                                            </strong>
                                            <div class="muted">{{ $product->category->name }}</div>

                                            {{-- Peringatan jika kondisi stok/produk berubah sejak ditambahkan --}}
                                            @if (! $product->is_active)
                                                <span class="badge badge-oos">Produk tidak tersedia</span>
                                            @elseif ($stokKurang)
                                                <span class="badge badge-low">Stok tinggal {{ $product->stock }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                <td>
                                    {{-- Ubah quantity: PATCH ke route cart.update --}}
                                    <form action="{{ route('cart.update', $item) }}" method="POST" class="cart-qty-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="quantity" value="{{ $item->quantity }}"
                                               min="1" max="{{ max($product->stock, 1) }}" class="form-control qty-input"
                                               aria-label="Jumlah {{ $product->name }}">
                                        <button type="submit" class="btn btn-outline">Ubah</button>
                                    </form>
                                </td>
                                <td><strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong></td>
                                <td>
                                    <form action="{{ route('cart.destroy', $item) }}" method="POST"
                                          onsubmit="return confirm('Hapus {{ $product->name }} dari keranjang?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Ringkasan belanja: subtotal seluruh item dan total akhir --}}
            <div class="panel cart-summary">
                <div class="cart-summary-line">
                    <span>Subtotal ({{ $items->sum('quantity') }} barang)</span>
                    <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="cart-summary-line cart-summary-total">
                    <span>Total</span>
                    <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
                <p class="muted cart-summary-note">Belum termasuk ongkos kirim.</p>
            </div>

            <div class="cart-footer">
                <a href="{{ route('catalog.index') }}" class="btn btn-outline">&larr; Lanjut Belanja</a>
                <a href="{{ route('checkout.index') }}" class="btn btn-solid">Checkout</a>
            </div>
        @endif
    </div>
@endsection
