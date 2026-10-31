<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AddressController extends Controller
{
    /**
     * Buku alamat customer. Alamat utama ditampilkan paling atas.
     */
    public function index(Request $request): View
    {
        $addresses = $request->user()->addresses()
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return view('customer.addresses.index', compact('addresses'));
    }

    /**
     * Simpan alamat baru. Alamat pertama otomatis menjadi alamat utama.
     */
    public function store(AddressRequest $request): RedirectResponse
    {
        $user = $request->user();

        $isFirst = $user->addresses()->count() === 0;

        $user->addresses()->create([
            ...$request->validated(),
            'is_default' => $isFirst,
        ]);

        return redirect()
            ->route('addresses.index')
            ->with('success', 'Alamat berhasil ditambahkan.');
    }

    /** Form ubah alamat. */
    public function edit(Request $request, Address $address): View
    {
        $this->authorizeOwner($request, $address);

        return view('customer.addresses.edit', compact('address'));
    }

    /** Simpan perubahan alamat. */
    public function update(AddressRequest $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $address->update($request->validated());

        return redirect()
            ->route('addresses.index')
            ->with('success', 'Alamat "'.$address->label.'" berhasil diperbarui.');
    }

    /**
     * Hapus alamat. Jika yang dihapus adalah alamat utama, alamat lain
     * yang paling lama otomatis dijadikan utama agar user tetap punya
     * alamat default untuk checkout.
     */
    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $wasDefault = $address->is_default;
        $label = $address->label;

        DB::transaction(function () use ($request, $address, $wasDefault) {
            $address->delete();

            if ($wasDefault) {
                $request->user()->addresses()->orderBy('id')->first()
                    ?->update(['is_default' => true]);
            }
        });

        return redirect()
            ->route('addresses.index')
            ->with('success', 'Alamat "'.$label.'" dihapus.');
    }

    /**
     * Jadikan alamat ini sebagai alamat utama (hanya satu per user).
     */
    public function setDefault(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        DB::transaction(function () use ($request, $address) {
            $request->user()->addresses()
                ->whereKeyNot($address->id)
                ->update(['is_default' => false]);

            $address->update(['is_default' => true]);
        });

        return redirect()
            ->route('addresses.index')
            ->with('success', 'Alamat "'.$address->label.'" dijadikan alamat utama.');
    }

    /**
     * Otorisasi kepemilikan: alamat hanya boleh diubah/dihapus pemiliknya.
     */
    private function authorizeOwner(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 403, 'Alamat ini bukan milik Anda.');
    }
}
