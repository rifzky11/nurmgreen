<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPaymentProofRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * Unggah/ganti bukti pembayaran. Status pesanan TIDAK diubah di sini —
     * hanya admin yang boleh menandai pesanan sebagai paid.
     */
    public function uploadProof(UploadPaymentProofRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('uploadProof', $order);

        $disk = Storage::disk('local');

        // Re-upload: hapus file lama dulu, baru simpan file baru.
        if ($order->hasPaymentProof()) {
            $disk->delete($order->payment_proof);
        }

        // store() menghasilkan nama file acak (bukan nama asli dari client).
        $path = $request->file('payment_proof')->store('payment-proofs', 'local');

        $order->update([
            'payment_proof' => $path,
            'payment_proof_uploaded_at' => now(),
        ]);

        return back()->with('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.');
    }

    /**
     * Sajikan file bukti dari disk privat. Hanya pemilik pesanan atau admin
     * (dicek OrderPolicy::viewPaymentProof); file tidak bisa diakses langsung.
     */
    public function paymentProof(Order $order): StreamedResponse
    {
        $this->authorize('viewPaymentProof', $order);

        abort_unless($order->hasPaymentProof(), 404);

        return Storage::disk('local')->response($order->payment_proof);
    }

    /**
     * Buyer mengonfirmasi pesanan sudah diterima (shipped -> completed).
     * Tidak ada status yang diterima dari input; status ditentukan server.
     */
    public function markReceived(Order $order): RedirectResponse
    {
        $this->authorize('markReceived', $order);

        $received = DB::transaction(function () use ($order) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            // Cek ulang setelah lock: status bisa berubah oleh request lain.
            if (! $locked->canBeReceived()) {
                return false;
            }

            $locked->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return true;
        });

        if (! $received) {
            return back()->with('error', 'Status pesanan sudah berubah. Muat ulang halaman lalu coba lagi.');
        }

        return back()->with('success', 'Terima kasih, pesanan telah dikonfirmasi diterima.');
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
