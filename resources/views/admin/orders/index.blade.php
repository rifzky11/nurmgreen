@extends('layouts.app')

@section('title', 'Kelola Pesanan')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Kelola Pesanan</h2>
            <span class="muted">{{ $orders->total() }} pesanan</span>
        </div>

        {{-- Filter GET: bisa dibagikan lewat URL dan tetap terbawa saat pindah halaman --}}
        <form action="{{ route('admin.orders.index') }}" method="GET" class="filter-bar">
            <input type="search" name="q" value="{{ $search }}" class="form-control"
                   placeholder="Cari kode pesanan..." aria-label="Cari kode pesanan">

            <select name="status" class="form-control" aria-label="Filter status">
                <option value="">Semua status</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="proof" class="form-control" aria-label="Filter bukti pembayaran">
                <option value="">Semua bukti</option>
                <option value="1" @selected($proof === '1')>Sudah upload bukti</option>
            </select>

            <button type="submit" class="btn btn-solid">Cari</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline">Reset</a>
        </form>

        <div class="panel">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode Pesanan</th>
                        <th>Customer</th>
                        <th style="width:150px">Tanggal</th>
                        <th style="width:170px">Status</th>
                        <th style="width:130px">Bukti</th>
                        <th style="width:80px">Item</th>
                        <th style="width:130px">Total</th>
                        <th style="width:90px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="order-code-link">{{ $order->order_code }}</a>
                            </td>
                            <td>
                                <strong>{{ $order->user->name }}</strong>
                                <div class="muted">{{ $order->user->email }}</div>
                            </td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="status-badge status-{{ $order->statusColor() }}">
                                    {{ $order->statusLabel() }}
                                </span>
                            </td>
                            <td>
                                @if ($order->hasPaymentProof())
                                    <span class="badge badge-on">Bukti terunggah</span>
                                @else
                                    <span class="muted">&mdash;</span>
                                @endif
                            </td>
                            <td>{{ $order->order_items_count }} item</td>
                            <td><strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong></td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="btn btn-outline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="muted">
                                Tidak ada pesanan yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $orders->links('pagination.simple-green') }}
        </div>
    </div>
@endsection
