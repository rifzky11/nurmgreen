<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\CheckoutRequest;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Tampilkan form checkout dengan data pengiriman yang sudah terisi
     * dari profil user dan ringkasan isi keranjang.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $selectedIds = $this->selectedItemIds($request);

        if ($selectedIds === []) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Pilih minimal satu produk untuk checkout.');
        }

        $query = CartItem::with('product.category')
            ->where('user_id', $request->user()->id);

        // Jika user mencentang produk tertentu di keranjang, hanya item itu
        // yang ditampilkan & dihitung. Tanpa parameter items = semua item.
        if ($selectedIds !== null) {
            $query->whereIn('id', $selectedIds);
        }

        $items = $query->latest()->get();

        if ($items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Keranjang kamu masih kosong. Tambahkan produk dulu sebelum checkout.');
        }

        // Pastikan semua ID yang diminta memang milik user ini.
        if ($selectedIds !== null && $items->count() !== count($selectedIds)) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Sebagian produk yang dipilih tidak ditemukan di keranjang.');
        }

        // Subtotal dihitung dari harga produk di database, bukan dari browser.
        $subtotal = $items->sum(fn (CartItem $item) => (float) $item->product->price * $item->quantity);
        $total = $subtotal;

        // Master data alamat: alamat utama dipakai untuk prefill form pengiriman.
        $addresses = $request->user()->addresses()
            ->orderByDesc('is_default')
            ->latest()
            ->get();
        $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        return view('customer.checkout', [
            'items' => $items,
            'subtotal' => $subtotal,
            'total' => $total,
            'user' => $request->user(),
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
        ]);
    }

    /**
     * Proses checkout dalam satu transaksi database.
     *
     * Semua langkah (validasi stok terbaru, buat order, snapshot item,
     * potong stok, kosongkan keranjang) harus berhasil bersama. Jika satu
     * langkah gagal, seluruh perubahan dibatalkan (rollback) sehingga tidak
     * ada order setengah jadi, stok terpotong tanpa order, atau keranjang
     * hilang tanpa pesanan.
     */
    public function store(CheckoutRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // ID keranjang yang dipilih; null berarti seluruh isi keranjang.
        $selectedIds = array_key_exists('items', $data)
            ? collect($data['items'])->map(fn ($id) => (int) $id)->unique()->values()->all()
            : null;

        if ($selectedIds === []) {
            return back()->withInput()->with('error', 'Pilih minimal satu produk untuk checkout.');
        }

        // Cek cepat sebelum transaksi; pengecekan ulang tetap dilakukan
        // di dalam transaksi untuk mengantisipasi keranjang berubah bersamaan.
        if (! CartItem::where('user_id', $user->id)->exists()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Keranjang kamu masih kosong. Tambahkan produk dulu sebelum checkout.');
        }

        try {
            $order = DB::transaction(function () use ($user, $data, $selectedIds) {
                $cartQuery = CartItem::query()->where('user_id', $user->id);

                if ($selectedIds !== null) {
                    $cartQuery->whereIn('id', $selectedIds);
                }

                // Kunci hanya baris keranjang yang akan diproses.
                $cartItems = $cartQuery->orderBy('id')->lockForUpdate()->get();

                if ($cartItems->isEmpty()) {
                    throw new CheckoutException('Keranjang kamu masih kosong atau produk yang dipilih sudah tidak ada.');
                }

                // Jumlah harus sama: mencegah ID milik user lain / item yang
                // sudah terhapus ikut diproses diam-diam.
                if ($selectedIds !== null && $cartItems->count() !== count($selectedIds)) {
                    throw new CheckoutException('Sebagian produk yang dipilih tidak ditemukan di keranjang.');
                }

                // Ambil & kunci semua produk terkait untuk validasi stok terbaru.
                // Lock mencegah dua checkout bersamaan memakai stok yang sama.
                $products = Product::query()
                    ->whereIn('id', $cartItems->pluck('product_id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $total = 0.0;

                foreach ($cartItems as $item) {
                    $product = $products->get($item->product_id);

                    if (! $product || ! $product->is_active) {
                        throw new InsufficientStockException(
                            'Produk "'.($product?->name ?? 'tidak dikenal')
                            .'" sudah tidak tersedia. Hapus produk ini dari keranjang untuk melanjutkan.'
                        );
                    }

                    if ($product->stock < $item->quantity) {
                        throw new InsufficientStockException(
                            'Stok "'.$product->name.'" tidak cukup. Diminta '.$item->quantity
                            .', tersedia '.$product->stock.'. Silakan sesuaikan jumlah di keranjang.'
                        );
                    }

                    $total += (float) $product->price * $item->quantity;
                }

                $order = Order::create([
                    'user_id' => $user->id,
                    'order_code' => $this->generateOrderCode(),
                    'total_price' => round($total, 2),
                    'status' => 'pending',
                    'shipping_name' => $data['shipping_name'],
                    'shipping_phone' => $data['shipping_phone'],
                    'shipping_address' => $data['shipping_address'],
                    'notes' => $data['notes'] ?? null,
                ]);

                foreach ($cartItems as $item) {
                    $product = $products->get($item->product_id);
                    $subtotal = round((float) $product->price * $item->quantity, 2);

                    // Snapshot nama & harga: riwayat pesanan tidak berubah
                    // walaupun admin mengedit/menghapus produk nanti.
                    $order->orderItems()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'price' => $product->price,
                        'quantity' => $item->quantity,
                        'subtotal' => $subtotal,
                    ]);

                    // Potong stok dengan syarat stok masih cukup.
                    // Kalau 0 baris terpengaruh, lempar exception agar rollback.
                    $affected = Product::query()
                        ->whereKey($product->id)
                        ->where('stock', '>=', $item->quantity)
                        ->decrement('stock', $item->quantity);

                    if ($affected !== 1) {
                        throw new InsufficientStockException(
                            'Stok "'.$product->name.'" berubah saat checkout. Silakan coba lagi.'
                        );
                    }
                }

                // Hanya item yang diproses yang dihapus; item lain yang tidak
                // dicentang tetap tersimpan di keranjang.
                CartItem::where('user_id', $user->id)
                    ->whereIn('id', $cartItems->pluck('id'))
                    ->delete();

                return $order;
            });
        } catch (CheckoutException $e) {
            // DB::transaction sudah melakukan rollback sebelum exception ini
            // sampai ke sini. Kembalikan user ke form dengan pesan error.
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('checkout.success', $order->order_code)
            ->with('success', 'Pesanan berhasil dibuat. Silakan lakukan pembayaran.');
    }

    /**
     * Halaman sukses + instruksi transfer bank manual.
     * Query dibatasi user_id agar customer lain tidak bisa membuka
     * pesanan orang lain lewat kode pesanan (404, bukan 403, agar
     * keberadaan kode tidak bocor).
     */
    public function success(Request $request, string $orderCode): View
    {
        $order = Order::with('orderItems')
            ->where('user_id', $request->user()->id)
            ->where('order_code', $orderCode)
            ->firstOrFail();

        return view('customer.checkout-success', compact('order'));
    }

    /**
     * Baca pilihan item dari query string (?items[]=1&items[]=2).
     * null = parameter tidak ada (checkout semua item),
     * []   = parameter ada tapi kosong/tidak valid.
     */
    private function selectedItemIds(Request $request): ?array
    {
        if (! $request->has('items')) {
            return null;
        }

        return collect($request->query('items', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Buat kode pesanan unik berformat NG-YYYYMMDD-XXXX.
     * Unique constraint di kolom order_code menjadi pengaman terakhir;
     * pengecekan exists() di sini hanya untuk menghindari tabrakan umum.
     */
    private function generateOrderCode(): string
    {
        $prefix = 'NG-'.now()->format('Ymd').'-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = $prefix.Str::upper(Str::random(4));

            if (! Order::where('order_code', $code)->exists()) {
                return $code;
            }
        }

        // Fallback: ruang acak diperbesar bila 5 percobaan berturut-turut bentrok.
        return $prefix.Str::upper(Str::random(8));
    }
}
