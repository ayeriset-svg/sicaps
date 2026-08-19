<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Mode Observasi: superadmin dapat "mengamati" tampilan sebagai mahasiswa tanpa
 * logout/login. Id superadmin asli disimpan di session untuk kembali.
 */
class ImpersonationController extends Controller
{
    public function start(User $user)
    {
        abort_unless(Auth::user()->isSuperadmin(), 403);
        abort_if($user->id === Auth::id(), 422, 'Tidak dapat mengamati akun sendiri.');
        abort_unless($user->isMahasiswa(), 422, 'Hanya dapat mengamati akun mahasiswa.');

        session(['impersonator_id' => Auth::id()]);
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Mode observasi aktif — Anda mengamati sebagai ' . $user->name . '.');
    }

    public function stop(Request $request)
    {
        $originalId = session('impersonator_id');
        if (! $originalId) {
            return redirect()->route('dashboard');
        }

        Auth::loginUsingId($originalId);
        session()->forget('impersonator_id');

        return redirect()->route('dashboard')->with('success', 'Kembali ke akun superadmin.');
    }
}
