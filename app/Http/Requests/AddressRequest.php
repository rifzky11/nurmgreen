<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Validasi master data alamat customer.
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'address' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Label alamat wajib diisi (contoh: Rumah, Kantor).',
            'label.max' => 'Label alamat maksimal 50 karakter.',
            'recipient_name.required' => 'Nama penerima wajib diisi.',
            'recipient_name.max' => 'Nama penerima maksimal 100 karakter.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'phone.max' => 'Nomor HP maksimal 20 karakter.',
            'phone.regex' => 'Nomor HP hanya boleh berisi angka, spasi, +, -, dan tanda kurung (8-20 karakter).',
            'address.required' => 'Alamat lengkap wajib diisi.',
            'address.max' => 'Alamat maksimal 500 karakter.',
        ];
    }
}
