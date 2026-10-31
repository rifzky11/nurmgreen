<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /** Halaman profil (berlaku untuk admin & customer). */
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    /** Simpan perubahan nama, nomor HP, dan alamat. */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Hanya kolom profil yang diubah; email & role tidak pernah dari input.
        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?: null,
            'address' => $data['address'] ?: null,
        ]);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    /** Ubah password setelah memverifikasi password saat ini. */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Ganti ID session setelah kredensial berubah.
        $request->session()->regenerate();

        return back()->with('success', 'Password berhasil diubah.');
    }
}
