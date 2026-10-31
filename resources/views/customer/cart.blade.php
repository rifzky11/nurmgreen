@extends('layouts.app')

@section('title', 'Keranjang')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Keranjang</h2>
            <span class="muted" id="cartTypeCount">{{ $items->count() }} jenis produk</span>
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
            <p class="muted cart-select-hint">
                Centang produk yang ingin dibeli, lalu klik.
            </p>

            <div class="panel">
                <table class="table cart-table">
                    <thead>
                        <tr>
                            <th style="width:36px">
                                <input type="checkbox" id="selectAll" class="cart-select-all"
                                       checked aria-label="Pilih semua produk">
                            </th>
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
                                $lineSubtotal = $product->price * $item->quantity;
                                $stokKurang = $product->stock < $item->quantity;
                            @endphp
                            <tr>
                                <td>
                                    {{-- Checkbox terhubung ke form checkout di bawah (atribut form) --}}
                                    <input type="checkbox" class="cart-select" name="items[]"
                                           value="{{ $item->id }}" form="checkout-form" checked
                                           data-subtotal="{{ (int) round($lineSubtotal) }}"
                                           data-quantity="{{ $item->quantity }}"
                                           aria-label="Pilih {{ $product->name }}">
                                </td>
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
                                    {{-- Jumlah tersimpan otomatis saat nilainya berubah (AJAX). --}}
                                    <div class="qty-wrap">
                                        <form action="{{ route('cart.update', $item) }}" method="POST"
                                              class="cart-qty-form">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="quantity" value="{{ $item->quantity }}"
                                                   min="1" max="{{ max($product->stock, 1) }}"
                                                   class="form-control qty-input" inputmode="numeric"
                                                   aria-label="Jumlah {{ $product->name }}">
                                        </form>
                                        <span class="cart-line-error" aria-live="polite"></span>
                                    </div>
                                </td>
                                <td><strong class="line-subtotal">Rp {{ number_format($lineSubtotal, 0, ',', '.') }}</strong></td>
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
                    <span id="summaryLabel">Subtotal ({{ $items->sum('quantity') }} barang)</span>
                    <span id="summarySubtotal">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="cart-summary-line cart-summary-total">
                    <span>Total</span>
                    <span id="summaryTotal">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
                <p class="muted cart-summary-note">Belum termasuk ongkos kirim.</p>
            </div>

            {{-- Form kosong: hanya membawa checkbox items[] yang dicentang ke halaman checkout --}}
            <form id="checkout-form" action="{{ route('checkout.index') }}" method="GET"></form>

            <div class="cart-footer">
                <a href="{{ route('catalog.index') }}" class="btn btn-outline">&larr; Lanjut Belanja</a>
                <button type="submit" form="checkout-form" class="btn btn-solid"
                        id="checkoutButton">Checkout</button>
            </div>

            <script>
                (() => {
                    const all = document.getElementById('selectAll');
                    const boxes = () => document.querySelectorAll('.cart-select');
                    const label = document.getElementById('summaryLabel');
                    const subtotalEl = document.getElementById('summarySubtotal');
                    const totalEl = document.getElementById('summaryTotal');
                    const checkoutButton = document.getElementById('checkoutButton');
                    const typeCount = document.getElementById('cartTypeCount');
                    if (!all) return;

                    const rupiah = (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(value);

                    // Jumlah jenis produk, subtotal & total mengikuti checkbox yang dicentang.
                    const updateSummary = () => {
                        let subtotal = 0;
                        let quantity = 0;
                        let productTypes = 0;

                        boxes().forEach((box) => {
                            if (!box.checked) return;
                            subtotal += Number(box.dataset.subtotal || 0);
                            quantity += Number(box.dataset.quantity || 0);
                            productTypes++;
                        });

                        label.textContent = `Subtotal (${quantity} barang)`;
                        subtotalEl.textContent = rupiah(subtotal);
                        totalEl.textContent = rupiah(subtotal);

                        if (typeCount) {
                            typeCount.textContent = `${productTypes} jenis produk`;
                        }

                        if (checkoutButton) {
                            checkoutButton.disabled = quantity === 0;
                        }
                    };

                    all.addEventListener('change', () => {
                        boxes().forEach((box) => { box.checked = all.checked; });
                        updateSummary();
                    });

                    boxes().forEach((box) => box.addEventListener('change', () => {
                        all.checked = [...boxes()].every((item) => item.checked);
                        updateSummary();
                    }));

                    // — Jumlah produk tersimpan otomatis, tanpa tombol "Ubah" —
                    const updateBadge = (quantity) => {
                        const badge = document.getElementById('cartCountBadge');
                        if (!badge) return;

                        badge.hidden = quantity === 0;
                        badge.textContent = quantity > 99 ? '99+' : String(quantity);
                    };

                    const saveQuantity = async (form, input, checkbox, lineSubtotalEl, errorEl) => {
                        const previous = form.dataset.savedValue ?? input.value;
                        if (input.value === previous) return;

                        form.classList.add('is-saving');
                        errorEl.textContent = '';

                        try {
                            const token = form.querySelector('input[name="_token"]')?.value ?? '';
                            const response = await fetch(form.action, {
                                method: 'PATCH',
                                headers: {
                                    'X-CSRF-TOKEN': token,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                },
                                body: new FormData(form),
                            });
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                errorEl.textContent = data.message
                                    || data.errors?.quantity?.[0]
                                    || 'Jumlah tidak valid.';
                                input.value = previous;
                                return;
                            }

                            form.dataset.savedValue = String(data.quantity);
                            input.value = data.quantity;
                            input.max = data.max_quantity;

                            // Sinkronkan data baris agar subtotal/total ikut berubah.
                            if (checkbox) {
                                checkbox.dataset.subtotal = data.line_subtotal;
                                checkbox.dataset.quantity = data.quantity;
                            }

                            if (lineSubtotalEl) {
                                lineSubtotalEl.textContent = data.formatted_line_subtotal;
                            }

                            updateSummary();
                            updateBadge(Number(data.cart_quantity));
                        } catch (error) {
                            errorEl.textContent = 'Gagal menyimpan jumlah. Coba lagi.';
                            input.value = previous;
                        } finally {
                            form.classList.remove('is-saving');
                        }
                    };

                    document.querySelectorAll('.cart-qty-form').forEach((form) => {
                        const input = form.querySelector('.qty-input');
                        const row = form.closest('tr');
                        const checkbox = row?.querySelector('.cart-select');
                        const lineSubtotalEl = row?.querySelector('.line-subtotal');
                        const errorEl = form.parentElement.querySelector('.cart-line-error');
                        let timer;

                        form.dataset.savedValue = input.value;

                        const save = () => {
                            clearTimeout(timer);
                            saveQuantity(form, input, checkbox, lineSubtotalEl, errorEl);
                        };

                        // Debounce saat mengetik; "change" langsung menyimpan
                        // saat input kehilangan fokus atau panah ditekan.
                        input.addEventListener('input', () => {
                            clearTimeout(timer);
                            timer = setTimeout(save, 600);
                        });

                        input.addEventListener('change', save);

                        form.addEventListener('submit', (event) => {
                            event.preventDefault();
                            save();
                        });
                    });

                    updateSummary();
                })();
            </script>
        @endif
    </div>
@endsection
