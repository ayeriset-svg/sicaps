<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mode Observasi bersifat hanya-baca: superadmin yang sedang mengamati akun
 * mahasiswa tidak boleh menyimpan apa pun atas nama mahasiswa tersebut.
 */
class ObserveModeReadOnly
{
    /** Aksi tulis yang tetap diizinkan selama observasi. */
    private const ALLOWED_ROUTES = ['observe.stop', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('impersonator_id')
            && ! $request->isMethodSafe()
            && ! in_array(optional($request->route())->getName(), self::ALLOWED_ROUTES, true)) {
            $request->attributes->set('observe_blocked', true); // jangan dicatat sebagai aksi berhasil

            return back()->with('error', 'Mode Observasi hanya untuk melihat — perubahan data tidak diizinkan. Kembali ke akun superadmin untuk mengubah data.');
        }

        return $next($request);
    }
}
