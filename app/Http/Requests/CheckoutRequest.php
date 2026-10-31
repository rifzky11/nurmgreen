<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Hanya customer yang login yang boleh checkout.
     * Pembatasan role juga sudah dijaga middleware di route.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Validasi data pengiriman. Total harga TIDAK diterima dari form;
     * nilainya dihitung ulang di server dari harga produk saat checkout.
     */
    public function rules(): array
    {
        return [
            // ID baris keranjang yang dipilih user. Opsional: tanpa "items"
            // berarti checkout seluruh isi keranjang (kompatibel dengan form lama).
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*' => ['integer', 'distinct'],
            'shipping_name' => ['required', 'string', 'max:100'],
            'shipping_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.min' => 'Pilih minimal satu produk untuk checkout.',
            'items.*.integer' => 'Produk yang dipilih tidak valid.',
            'items.*.distinct' => 'Produk yang dipilih tidak boleh duplikat.',
            'shipping_name.required' => 'Nama penerima wajib diisi.',
            'shipping_name.max' => 'Nama penerima maksimal 100 karakter.',
            'shipping_phone.required' => 'Nomor HP wajib diisi.',
            'shipping_phone.max' => 'Nomor HP maksimal 20 karakter.',
            'shipping_phone.regex' => 'Nomor HP hanya boleh berisi angka, spasi, +, -, dan tanda kurung (8-20 karakter).',
            'shipping_address.required' => 'Alamat pengiriman wajib diisi.',
            'shipping_address.max' => 'Alamat maksimal 500 karakter.',
            'notes.max' => 'Catatan maksimal 500 karakter.',
        ];
    }
}
