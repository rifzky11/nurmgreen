@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Checkout</h2>
            <span class="muted">{{ $items->sum('quantity') }} barang</span>
        </div>

        <div class="checkout-grid">
            {{-- Kolom kiri: data pengiriman, terisi otomatis dari profil user --}}
            <div class="panel">
                <h3 class="checkout-title">Data Pengiriman</h3>

                <form action="{{ route('checkout.store') }}" method="POST" novalidate>
                    @csrf

                    {{-- Hanya item keranjang yang dipilih yang ikut diproses --}}
                    @foreach ($items as $item)
                        <input type="hidden" name="items[]" value="{{ $item->id }}">
                    @endforeach

                    {{-- Pilih alamat tersimpan untuk mengisi form di bawah --}}
                    @if ($addresses->isNotEmpty())
                        <div class="saved-address">
                            <div class="saved-address-head">
                                <span>Alamat Tersimpan</span>
                                <a href="{{ route('addresses.index') }}">Kelola Alamat</a>
                            </div>

                            @foreach ($addresses as $address)
                                <label class="address-option">
                                    <input type="radio" name="address_choice" value="{{ $address->id }}"
                                           data-name="{{ $address->recipient_name }}"
                                           data-phone="{{ $address->phone }}"
                                           data-address="{{ $address->address }}"
                                           @checked($address->is_default)>
                                    <span>
                                        <strong>{{ $address->label }}</strong>
                                        @if ($address->is_default)
                                            <span class="nav-role">Utama</span>
                                        @endif
                                        <span class="muted">{{ $address->recipient_name }} &middot; {{ $address->phone }}</span>
                                        <span class="muted">{{ $address->address }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="form-hint">
                            Belum ada alamat tersimpan.
                            <a href="{{ route('addresses.index') }}">Tambahkan alamat</a>
                            agar checkout berikutnya lebih cepat.
                        </p>
                    @endif

                    <div class="form-group">
                        <label for="shipping_name">Nama Penerima</label>
                        <input type="text" id="shipping_name" name="shipping_name" class="form-control"
                               value="{{ old('shipping_name', $defaultAddress?->recipient_name ?? $user->name) }}"
                               maxlength="100" required autofocus>
                        @error('shipping_name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="shipping_phone">Nomor HP</label>
                        <input type="tel" id="shipping_phone" name="shipping_phone" class="form-control"
                               value="{{ old('shipping_phone', $defaultAddress?->phone ?? $user->phone) }}"
                               maxlength="20" placeholder="08xxxxxxxxxx" required>
                        @error('shipping_phone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="shipping_address">Alamat Pengiriman</label>
                        <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3"
                                  maxlength="500" required>{{ old('shipping_address', $defaultAddress?->address ?? $user->address) }}</textarea>
                        @error('shipping_address')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan (opsional)</label>
                        <textarea id="notes" name="notes" class="form-control" rows="2" maxlength="500"
                                  placeholder="Contoh: kirim setelah jam 16.00">{{ old('notes') }}</textarea>
                        @error('notes')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-solid checkout-submit">Buat Pesanan</button>
                </form>
            </div>

            {{-- Kolom kanan: ringkasan barang & total yang dihitung server --}}
            <aside class="panel checkout-summary">
                <h3 class="checkout-title">Ringkasan Pesanan</h3>

                <ul class="checkout-items">
                    @foreach ($items as $item)
                        <li>
                            <span>
                                {{ $item->product->name }}
                                <span class="muted">&times; {{ $item->quantity }}</span>
                            </span>
                            <span>
                                Rp {{ number_format($item->product->price * $item->quantity, 0, ',', '.') }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                <div class="cart-summary-line">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="cart-summary-line cart-summary-total">
                    <span>Total</span>
                    <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
                <p class="muted cart-summary-note">Belum termasuk ongkos kirim. Pembayaran via transfer bank.</p>

                <a href="{{ route('cart.index') }}" class="btn btn-outline">Ubah Keranjang</a>
            </aside>
        </div>
    </div>

    {{-- Memilih alamat tersimpan langsung mengisi form pengiriman --}}
    <script>
        (() => {
            const name = document.getElementById('shipping_name');
            const phone = document.getElementById('shipping_phone');
            const address = document.getElementById('shipping_address');
            if (!name || !phone || !address) return;

            document.querySelectorAll('input[name="address_choice"]').forEach((radio) => {
                radio.addEventListener('change', () => {
                    if (!radio.checked) return;

                    name.value = radio.dataset.name;
                    phone.value = radio.dataset.phone;
                    address.value = radio.dataset.address;
                });
            });
        })();
    </script>
@endsection
