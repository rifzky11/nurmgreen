<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Daftar semua pesanan: pencarian kode pesanan, filter status,
     * dan pagination. Filter memakai GET agar bisa dibagikan lewat URL.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $proof = (string) $request->query('proof', '');

        $orders = Order::with('user')
            ->withCount('orderItems')
            ->when($search !== '', function ($query) use ($search) {
                // Escape wildcard LIKE agar % dan _ dicari sebagai karakter biasa.
                $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

                $query->where('order_code', 'like', '%'.$escaped.'%');
            })
            ->when(in_array($status, Order::STATUSES, true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($proof === '1', function ($query) {
                // Filter "Sudah upload bukti pembayaran".
                $query->whereNotNull('payment_proof');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'proof' => $proof,
            'statusOptions' => Order::statusLabels(),
        ]);
    }

    /** Detail pesanan untuk admin: customer, pengiriman, item, dan status. */
    public function show(Order $order): View
    {
        $order->load(['orderItems', 'user']);

        return view('admin.orders.show', [
            'order' => $order,
            'statusLabels' => Order::statusLabels(),
        ]);
    }

    /**
     * Ubah status pesanan sesuai state machine Order::allowedTransitions().
     * Jika status baru "cancelled", stok semua item dikembalikan.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        // Validasi tahap pertama: status tujuan harus termasuk transisi yang
        // sah dari status pesanan saat ini (mis. pending hanya boleh paid/cancelled).
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in($order->nextStatuses())],
        ], [
            'status.required' => 'Status baru wajib dipilih.',
            'status.in' => 'Perubahan status dari "'.$order->statusLabel().'" ke status yang dipilih tidak diizinkan.',
        ]);

        $newStatus = $validated['status'];

        $updated = DB::transaction(function () use ($order, $newStatus) {
            // Lock baris order supaya dua request bersamaan tidak sama-sama
            // lolos validasi lalu mengembalikan stok dua kali.
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            // Validasi tahap kedua dengan data terbaru (setelah lock).
            if (! $locked->canTransitionTo($newStatus)) {
                return false;
            }

            // Stok hanya dikembalikan ketika pesanan dibatalkan.
            if ($newStatus === 'cancelled') {
                foreach ($locked->orderItems()->get() as $item) {
                    Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                }
            }

            $attributes = ['status' => $newStatus];

            // Catat waktu pengiriman saat admin mengubah paid -> shipped.
            if ($newStatus === 'shipped') {
                $attributes['shipped_at'] = now();
            }

            $locked->update($attributes);

            return true;
        });

        if (! $updated) {
            return back()->with('error', 'Status pesanan sudah berubah. Muat ulang halaman lalu coba lagi.');
        }

        $message = 'Status pesanan '.$order->order_code.' diubah menjadi "'
            .$order->fresh()->statusLabel().'".';

        if ($newStatus === 'cancelled') {
            $message .= ' Stok produk sudah dikembalikan.';
        }

        return back()->with('success', $message);
    }
}
