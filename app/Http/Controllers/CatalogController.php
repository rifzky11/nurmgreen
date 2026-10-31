<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Katalog pengunjung: pencarian nama, filter kategori, urutan harga,
     * dan pagination. Filter dirangkai lewat scope di model Product.
     */
    public function index(): View
    {
        $q = request()->query('q');
        $sort = request()->query('sort');

        // Filter kategori memakai slug di URL (?category=pupuk-media)
        // supaya URL-nya terbaca, bukan id mentah.
        $category = Category::where('slug', request()->query('category'))
            ->first(); // slug tidak dikenal = tanpa filter kategori

        $products = Product::with('category') // eager loading, hindari N+1
            ->active() // hanya is_active = true
            ->search($q) // LIKE nama produk (binding, aman dari SQL injection)
            ->inCategory($category)
            ->sorted($sort) // whitelist: latest | price_asc | price_desc
            ->paginate(9)
            ->withQueryString(); // link halaman membawa q, category, dan sort

        $categories = Category::orderBy('name')->get();

        return view('catalog.index', compact('products', 'categories', 'category', 'q', 'sort'));
    }

    /**
     * Detail produk berdasarkan slug: /produk/{slug}.
     * Route model binding {product:slug} sudah mengambil produk sesuai slug;
     * produk nonaktif tetap 404 agar tidak bisa dilihat pengunjung.
     */
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load('category');

        // Rekomendasi sederhana: produk aktif lain dalam kategori yang sama,
        // dikesampingkan dari produk yang sedang dibuka.
        $related = Product::with('category')
            ->active()
            ->inCategory($product->category)
            ->whereKeyNot($product->id)
            ->latest()
            ->limit(4)
            ->get();

        return view('catalog.show', compact('product', 'related'));
    }
}
