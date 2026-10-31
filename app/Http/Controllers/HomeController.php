<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Beranda untuk pengunjung: kategori + produk terbaru.
     * Hanya produk aktif yang ditampilkan (scope active).
     */
    public function index(): View
    {
        $categories = Category::orderBy('name')->get();

        // Eager loading category supaya kartu produk tidak query kategori
        // satu per satu (hindari N+1).
        $products = Product::with('category')
            ->active()
            ->latest() // ORDER BY created_at DESC, kolom terbaru dulu
            ->limit(8)
            ->get();

        return view('home', compact('categories', 'products'));
    }
}
