<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memaksa user yang ditandai must_change_password untuk mengganti sandi
 * sebelum mengakses halaman lain (aktivasi akun via ganti sandi pertama).
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
