<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Keranjang milik user yang sedang login: daftar item, subtotal, dan total.
     */
    public function index(): View
    {
        // Scoping dilakukan di QUERY (where user_id), bukan hanya di view:
        // user tidak mungkin menerima item milik user lain.
        $items = CartItem::with('product.category') // eager loading, hindari N+1
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        // Subtotal = penjumlahan harga x jumlah semua baris.
        // Total saat ini sama dengan subtotal karena belum ada ongkir/pajak.
        $subtotal = $items->sum(fn (CartItem $item) => $item->product->price * $item->quantity);
        $total = $subtotal;

        return view('customer.cart', compact('items', 'subtotal', 'total'));
    }

    /**
     * Tambah produk ke keranjang. Jika produk sudah ada di keranjang,
     * quantity-nya ditambah (satu baris per produk, dijaga unique constraint).
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $userId = $request->user()->id;

        // Cek stok dan penulisan quantity dibungkus satu transaksi + row lock.
        // Dengan lockForUpdate, dua request bersamaan tidak bisa sama-sama
        // lolos cek stok lalu menjadikan isi keranjang melebihi stok.
        $result = DB::transaction(function () use ($product, $validated, $userId) {
            /** @var Product $locked */
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);

            // Produk nonaktif / stok habis tidak boleh masuk keranjang
            if (! $locked->is_active || $locked->stock < 1) {
                return ['ok' => false, 'message' => 'Produk ini sedang tidak tersedia.'];
            }

            $existing = CartItem::query()
                ->where('user_id', $userId)
                ->where('product_id', $locked->id)
                ->lockForUpdate()
                ->first();

            // Jumlah akhir = isi keranjang sekarang + yang baru diminta
            $newQuantity = ($existing?->quantity ?? 0) + $validated['quantity'];

            if ($newQuantity > $locked->stock) {
                return [
                    'ok' => false,
                    'message' => 'Jumlah di keranjang ('.$newQuantity.') melebihi stok yang tersedia ('.$locked->stock.').',
                ];
            }

            if ($existing) {
                $existing->update(['quantity' => $newQuantity]);
            } else {
                CartItem::create([
                    'user_id' => $userId, // pemilik item = user yang login, bukan dari input
                    'product_id' => $locked->id,
                    'quantity' => $validated['quantity'],
                ]);
            }

            return ['ok' => true];
        });

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('cart.index')->with('success', 'Produk ditambahkan ke keranjang.');
    }

    /**
     * Ubah quantity satu item keranjang.
     */
    public function update(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        // Cegah akses item milik user lain (403 jika bukan pemilik)
        $this->authorizeOwner($cartItem);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        // Quantity tidak boleh melebihi stok produk saat ini
        $stock = $cartItem->product->stock;

        if ($validated['quantity'] > $stock) {
            $message = 'Jumlah melebihi stok. Stok tersedia untuk "'
                .$cartItem->product->name.'": '.$stock.'.';

            // Request AJAX: kembalikan JSON agar input bisa dikembalikan tanpa reload.
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);

        // Update via fetch: kirim data terbaru untuk memperbarui subtotal,
        // ringkasan keranjang, dan badge navbar tanpa memuat ulang halaman.
        if ($request->expectsJson()) {
            $lineSubtotal = (float) $cartItem->product->price * $cartItem->quantity;

            return response()->json([
                'quantity' => $cartItem->quantity,
                'line_subtotal' => $lineSubtotal,
                'formatted_line_subtotal' => 'Rp '.number_format($lineSubtotal, 0, ',', '.'),
                'cart_quantity' => (int) $request->user()->cartItems()->sum('quantity'),
                'max_quantity' => $stock,
                'message' => 'Jumlah "'.$cartItem->product->name.'" diperbarui.',
            ]);
        }

        return back()->with('success', 'Jumlah "'.$cartItem->product->name.'" diperbarui.');
    }

    /**
     * Hapus satu item dari keranjang.
     */
    public function destroy(CartItem $cartItem): RedirectResponse
    {
        $this->authorizeOwner($cartItem);

        $nama = $cartItem->product->name;

        $cartItem->delete();

        return back()->with('success', '"'.$nama.'" dihapus dari keranjang.');
    }

    /**
     * Otorisasi kepemilikan item keranjang.
     *
     * Semua aksi pada item (update/destroy) lewat sini: bandingkan
     * user_id pemilik item dengan id user yang sedang login. Route model
     * binding {cartItem} bisa saja menerima id milik orang lain, jadi
     * kepemilikan HARUS dicek sebelum item disentuh — kalau tidak cocok,
     * request dihentikan dengan 403 Forbidden.
     */
    private function authorizeOwner(CartItem $cartItem): void
    {
        abort_unless($cartItem->user_id === (int) auth()->id(), 403, 'Item ini bukan milik Anda.');
    }
}
