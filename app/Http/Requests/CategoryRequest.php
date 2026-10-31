<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * Hanya admin yang boleh mengelola kategori.
     * (Route sudah dijaga middleware role:admin, ini lapisan tambahan.)
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Aturan validasi untuk tambah & ubah kategori.
     */
    public function rules(): array
    {
        // Saat update, route model binding mengisi {category}; saat create nilainya null.
        $category = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unik, tapi abaikan kategori yang sedang diedit
                Rule::unique('categories', 'name')->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
            'name.unique' => 'Nama kategori sudah ada, gunakan nama lain.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
        ];
    }
}
