<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Ambang stok menipis yang ditampilkan di dashboard. */
    private const LOW_STOCK_THRESHOLD = 5;

    /** Status pesanan yang dihitung sebagai omzet (sudah dibayar). */
    private const REVENUE_STATUSES = ['paid', 'shipped', 'completed'];

    /**
     * Ringkasan toko untuk admin.
     *
     * Semua angka dihitung dengan agregasi SQL (COUNT/SUM) sehingga database
     * yang menghitung, bukan PHP yang memuat seluruh baris. Relasi yang
     * ditampilkan di tabel di-eager-load agar tidak terjadi N+1 query.
     */
    public function index(): View
    {
        // COUNT(*) langsung di database; model tidak dimuat ke memori.
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();

        // SUM(total_price) dengan filter status = omzet dari pesanan yang
        // sudah dibayar/diproses/selesai; pending & cancelled tidak dihitung.
        $totalRevenue = (float) Order::whereIn('status', self::REVENUE_STATUSES)
            ->sum('total_price');

        // 5 pesanan terbaru. Eloquent menjalankan:
        // 1) query orders + subquery COUNT(order_items),
        // 2) satu query users untuk semua pesanan (eager loading),
        // bukan satu query user per baris (N+1).
        $recentOrders = Order::with('user')
            ->withCount('orderItems')
            ->latest()
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // Produk stok menipis (<= 5). Kategori di-eager-load sekali untuk
        // semua produk, bukan per produk saat view merender nama kategori.
        $lowStockProducts = Product::with('category')
            ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
            ->orderBy('stock')
            ->orderBy('name')
            ->get();

        return view('admin.dashboard', compact(
            'totalProducts',
            'totalCategories',
            'totalOrders',
            'totalRevenue',
            'pendingOrders',
            'recentOrders',
            'lowStockProducts',
        ));
    }
}
