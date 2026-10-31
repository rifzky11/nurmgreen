<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'phone.max' => 'Nomor HP maksimal 20 karakter.',
            'phone.regex' => 'Nomor HP hanya boleh berisi angka, spasi, +, -, dan tanda kurung (8-20 karakter).',
            'address.max' => 'Alamat maksimal 500 karakter.',
        ];
    }
}
