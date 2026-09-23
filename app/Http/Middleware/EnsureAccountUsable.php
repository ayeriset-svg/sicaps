<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memutus sesi yang sedang berjalan bila akun dinonaktifkan koordinator atau
 * batas aktivasinya habis (sebelumnya hanya diperiksa saat login).
 * Dilewati selama Mode Observasi (yang login sebenarnya superadmin).
 */
class EnsureAccountUsable
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $request->session()->has('impersonator_id') && ($reason = $user->accessBlockedReason())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['login' => $reason]);
        }

        return $next($request);
    }
}
