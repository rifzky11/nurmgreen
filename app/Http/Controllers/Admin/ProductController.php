<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Daftar produk dengan pencarian nama, filter kategori, dan pagination.
     */
    public function index(Request $request): View
    {
        // Ambil input filter dari query string (?q=...&category=...)
        $search = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category');

        $products = Product::with('category') // eager loading, hindari N+1
            ->when($search !== '', function ($query) use ($search) {
                // LIKE dengan binding otomatis (aman dari SQL injection)
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString(); // link pagination tetap membawa filter

        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories', 'search', 'categoryId'));
    }

    /** Form tambah produk. */
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    /** Simpan produk baru (termasuk upload gambar). */
    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        // store('products','public') menyimpan ke storage/app/public/products
        // dan mengembalikan path relatif, mis. "products/abc123.jpg"
        $data['image'] = $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;

        Product::create($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk "'.$data['name'].'" berhasil ditambahkan.');
    }

    /** Detail produk (preview admin). */
    public function show(Product $product): View
    {
        $product->load('category');

        return view('admin.products.show', compact('product'));
    }

    /** Form ubah produk. */
    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /** Simpan perubahan produk. */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            // Upload gambar baru dulu, baru hapus file lama
            $data['image'] = $request->file('image')->store('products', 'public');

            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
        } else {
            // Tidak upload gambar baru: jangan timpa gambar lama dengan null
            unset($data['image']);
        }

        $product->update($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk "'.$product->name.'" berhasil diperbarui.');
    }

    /** Hapus produk beserta file gambarnya. */
    public function destroy(Product $product): RedirectResponse
    {
        $nama = $product->name;

        try {
            $product->delete();
        } catch (QueryException $e) {
            // FK restrict: produk yang sudah masuk keranjang/pesanan tidak boleh dihapus
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Produk "'.$nama.'" tidak bisa dihapus karena sudah tercatat '
                    .'di keranjang atau pesanan. Nonaktifkan produk ini saja.');
        }

        // File baru dihapus setelah baris database benar-benar terhapus
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk "'.$nama.'" berhasil dihapus.');
    }

    /**
     * Slug unik dari nama produk.
     * Contoh: "Monstera Deliciosa" -> "monstera-deliciosa".
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'produk';
        $slug = $base;
        $counter = 2;

        while (
            Product::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
