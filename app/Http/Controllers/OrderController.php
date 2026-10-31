<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Riwayat pesanan milik user yang login (pagination 10 per halaman).
     * Query di-scope user_id, jadi pesanan customer lain tidak pernah ikut.
     */
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->withCount('orderItems')
            ->latest()
            ->paginate(10);

        return view('customer.orders.index', compact('orders'));
    }

    /**
     * Detail satu pesanan. Otorisasi lewat OrderPolicy::view.
     */
    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('orderItems');

        return view('customer.orders.show', compact('order'));
    }

    /**
     * Batalkan pesanan berstatus pending dan kembalikan stok produk.
     *
     * Otorisasi (pemilik + status pending) dicek Policy; status dicek ulang
     * di dalam transaksi dengan lockForUpdate supaya dua request pembatalan
     * bersamaan tidak mengembalikan stok dua kali.
     */
    public function cancel(Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $cancelled = DB::transaction(function () use ($order) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            // Status bisa berubah sejak pengecekan Policy (mis. request lain
            // sudah membatalkan). Jangan restock lagi.
            if (! $locked->canBeCancelled()) {
                return false;
            }

            foreach ($locked->orderItems()->get() as $item) {
                // Kembalikan stok sesuai jumlah yang dulu dipotong saat checkout.
                Product::whereKey($item->product_id)->increment('stock', $item->quantity);
            }

            $locked->update(['status' => 'cancelled']);

            return true;
        });

        if (! $cancelled) {
            return back()->with('error', 'Pesanan ini tidak bisa dibatalkan karena statusnya sudah berubah.');
        }

        return back()->with('success', 'Pesanan '.$order->order_code.' dibatalkan dan stok produk dikembalikan.');
    }
}
