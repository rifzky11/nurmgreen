@extends('layouts.app')

@section('title', 'Pesanan Saya')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Pesanan Saya</h2>
            <span class="muted">{{ $orders->total() }} pesanan</span>
        </div>

        @if ($orders->isEmpty())
            <div class="panel empty-state">
                <p>Kamu belum punya pesanan.</p>
                <a href="{{ route('catalog.index') }}" class="btn btn-solid">Mulai Belanja</a>
            </div>
        @else
            <div class="panel">
                <table class="table order-table">
                    <thead>
                        <tr>
                            <th>Kode Pesanan</th>
                            <th style="width:150px">Tanggal</th>
                            <th style="width:170px">Status</th>
                            <th style="width:90px">Item</th>
                            <th style="width:130px">Total</th>
                            <th style="width:90px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('orders.show', $order) }}"
                                       class="order-code-link">{{ $order->order_code }}</a>
                                </td>
                                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="status-badge status-{{ $order->status }}">
                                        {{ $order->statusLabel() }}
                                    </span>
                                </td>
                                <td>{{ $order->order_items_count }} item</td>
                                <td><strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong></td>
                                <td>
                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-outline">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $orders->links('pagination.simple-green') }}
        @endif
    </div>
@endsection
