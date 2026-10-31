<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /** Hanya admin yang boleh mengelola produk. */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Validasi ketat untuk tambah & ubah produk.
     */
    public function rules(): array
    {
        return [
            // Kategori harus benar-benar ada di tabel categories
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            // numeric: boleh desimal; min 0 agar tidak ada harga negatif
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'stock' => ['required', 'integer', 'min:0'],
            // image: file harus gambar asli, ekstensi tertentu, maksimal 2048 KB (2 MB)
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // Checkbox aktif dikirim sebagai 0/1
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'name.required' => 'Nama produk wajib diisi.',
            'name.max' => 'Nama produk maksimal 255 karakter.',
            'description.required' => 'Deskripsi wajib diisi.',
            'description.max' => 'Deskripsi maksimal 5000 karakter.',
            'price.required' => 'Harga wajib diisi.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.min' => 'Harga tidak boleh negatif.',
            'price.max' => 'Harga terlalu besar.',
            'stock.required' => 'Stok wajib diisi.',
            'stock.integer' => 'Stok harus berupa bilangan bulat.',
            'stock.min' => 'Stok tidak boleh negatif.',
            'image.image' => 'File yang diunggah harus berupa gambar.',
            'image.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.',
            'image.max' => 'Ukuran gambar maksimal 2 MB.',
            // Muncul kalau PHP menolak upload (mis. file melebihi upload_max_filesize)
            'image.uploaded' => 'Gambar gagal diunggah. Kemungkinan ukurannya melebihi batas server '
                .'(maksimal 2 MB) atau koneksi terputus. Coba kompres gambarnya lalu unggah ulang.',
            'is_active.required' => 'Status produk wajib dipilih.',
        ];
    }
}
