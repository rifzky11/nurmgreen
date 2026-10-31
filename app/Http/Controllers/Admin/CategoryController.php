<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Daftar kategori + jumlah produk, dibagi per halaman.
     */
    public function index(): View
    {
        // withCount('products') menambah kolom products_count tanpa query tambahan per baris
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    /** Form tambah kategori. */
    public function create(): View
    {
        return view('admin.categories.create');
    }

    /** Simpan kategori baru. */
    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Category::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori "'.$data['name'].'" berhasil ditambahkan.');
    }

    /** Form ubah kategori. */
    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    /** Simpan perubahan kategori. */
    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        $category->update([
            'name' => $data['name'],
            // Slug dibuat ulang mengikuti nama baru, tetap unik & tidak bentrok dengan dirinya sendiri
            'slug' => $this->uniqueSlug($data['name'], $category->id),
            'description' => $data['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori "'.$category->name.'" berhasil diperbarui.');
    }

    /**
     * Hapus kategori, tapi tolak jika masih dipakai produk.
     * FK restrict di database adalah lapisan kedua kalau pengecekan ini terlewat.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $jumlahProduk = $category->products()->count();

        if ($jumlahProduk > 0) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Kategori "'.$category->name.'" tidak bisa dihapus karena masih memiliki '
                    .$jumlahProduk.' produk. Pindahkan atau hapus produknya terlebih dahulu.');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori "'.$category->name.'" berhasil dihapus.');
    }

    /**
     * Membuat slug unik dari nama.
     * Contoh: "Tanaman Hias" -> "tanaman-hias"; kalau sudah ada -> "tanaman-hias-2".
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $slug = $base;
        $counter = 2;

        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
