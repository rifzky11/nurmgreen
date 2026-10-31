@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Dashboard Admin</h2>
            <span class="muted">Ringkasan toko NurmGreen</span>
        </div>

        {{-- Kartu ringkasan: angka berasal dari COUNT/SUM di database --}}
        <div class="stat-grid">
            <a href="{{ route('admin.products.index') }}" class="panel stat-card">
                <span class="stat-label">Total Produk</span>
                <span class="stat-value">{{ number_format($totalProducts, 0, ',', '.') }}</span>
                <span class="stat-link">Kelola produk</span>
            </a>

            <a href="{{ route('admin.categories.index') }}" class="panel stat-card">
                <span class="stat-label">Total Kategori</span>
                <span class="stat-value">{{ number_format($totalCategories, 0, ',', '.') }}</span>
                <span class="stat-link">Kelola kategori</span>
            </a>

            <a href="{{ route('admin.orders.index') }}" class="panel stat-card">
                <span class="stat-label">Total Pesanan</span>
                <span class="stat-value">{{ number_format($totalOrders, 0, ',', '.') }}</span>
                <span class="stat-link">Lihat pesanan</span>
            </a>

            <div class="panel stat-card stat-accent">
                <span class="stat-label">Omzet</span>
                <span class="stat-value">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                <span class="stat-link muted">Dibayar, dikirim, selesai</span>
            </div>

            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="panel stat-card stat-warning">
                <span class="stat-label">Menunggu Pembayaran</span>
                <span class="stat-value">{{ number_format($pendingOrders, 0, ',', '.') }}</span>
                <span class="stat-link">Tindak lanjuti</span>
            </a>

            <a href="{{ route('admin.orders.index', ['status' => 'pending', 'proof' => '1']) }}"
               class="panel stat-card stat-warning">
                <span class="stat-label">Menunggu Verifikasi</span>
                <span class="stat-value">{{ number_format($pendingWithProof, 0, ',', '.') }}</span>
                <span class="stat-link">Pesanan dengan bukti</span>
            </a>
        </div>

        <div class="dashboard-grid">
            {{-- 5 pesanan terbaru; user & jumlah item sudah di-eager-load --}}
            <div class="panel">
                <div class="dash-head">
                    <h3 class="dash-title">5 Pesanan Terbaru</h3>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline">Lihat Semua</a>
                </div>

                @if ($recentOrders->isEmpty())
                    <p class="muted">Belum ada pesanan masuk.</p>
                @else
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Customer</th>
                                <th style="width:150px">Status</th>
                                <th style="width:120px">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentOrders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                           class="order-code-link">{{ $order->order_code }}</a>
                                        <div class="muted">{{ $order->order_items_count }} item</div>
                                    </td>
                                    <td>
                                        {{ $order->user->name }}
                                        <div class="muted">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-{{ $order->statusColor() }}">
                                            {{ $order->statusLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Produk dengan stok <= 5, kategori sudah di-eager-load --}}
            <div class="panel">
                <div class="dash-head">
                    <h3 class="dash-title">Stok Menipis (&le; 5)</h3>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline">Kelola</a>
                </div>

                @if ($lowStockProducts->isEmpty())
                    <p class="muted">Semua produk memiliki stok di atas 5.</p>
                @else
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th style="width:110px">Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lowStockProducts as $product)
                                <tr>
                                    <td>
                                        {{ $product->name }}
                                        <div class="muted">{{ $product->category->name }}</div>
                                    </td>
                                    <td>
                                        @if ($product->stock === 0)
                                            <span class="badge badge-oos">Habis</span>
                                        @else
                                            <span class="badge badge-low">Sisa {{ $product->stock }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection
