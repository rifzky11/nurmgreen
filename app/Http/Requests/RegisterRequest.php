<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Semua orang boleh mengakses form register.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi pendaftaran akun.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            // unique: cek langsung ke tabel users, bukan hanya di sisi browser
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            // min 8 + confirmed: harus ada input password_confirmation yang nilainya sama
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Pesan error bahasa Indonesia agar mudah dipahami user.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar, silakan masuk.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ];
    }
}
