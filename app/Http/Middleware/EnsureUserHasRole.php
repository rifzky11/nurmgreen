<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Membatasi akses berdasarkan role user.
     *
     * Pemakaian di route: ->middleware('role:admin') atau 'role:admin,customer'.
     * Parameter setelah titik dua otomatis masuk ke $roles (variadic).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Belum login atau role-nya tidak ada di daftar yang diizinkan
        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
