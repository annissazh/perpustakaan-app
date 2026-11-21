<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Batasi akses route berdasarkan role pengguna.
     *
     * Superadmin selalu diizinkan karena memiliki akses penuh atas sistem.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Pastikan user sudah login.
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->role === Role::Superadmin) {
            return $next($request);
        }

        if (in_array($user->role->value, $roles, true)) {
            return $next($request);
        }

        // Tidak punya akses (misal: anggota mencoba membuka halaman admin).
        return redirect()->route('dashboard')
            ->with('error', 'Anda tidak memiliki izin untuk mengakses halaman tersebut.');
    }
}
