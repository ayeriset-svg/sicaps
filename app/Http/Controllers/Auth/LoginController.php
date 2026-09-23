<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Terima login via email atau identity_number (NIM/NIP/NIDN)
        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'identity_number';

        $attempt = Auth::attempt([
            $field => $credentials['login'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'));

        if (! $attempt) {
            throw ValidationException::withMessages([
                'login' => 'Kombinasi identitas dan kata sandi tidak cocok.',
            ]);
        }

        // Akun nonaktif / melewati batas aktivasi (sandi awal NIM belum diganti) ditolak.
        if ($reason = $request->user()->accessBlockedReason()) {
            Auth::logout();
            throw ValidationException::withMessages(['login' => $reason]);
        }

        $request->session()->regenerate();

        \App\Models\ActivityLog::create([
            'user_id' => Auth::id(), 'action' => 'login', 'description' => 'Login ke sistem',
            'method' => 'POST', 'route' => 'login', 'url' => $request->fullUrl(), 'ip' => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(), 'action' => 'logout', 'description' => 'Logout dari sistem',
                'method' => 'POST', 'route' => 'logout', 'url' => $request->fullUrl(), 'ip' => $request->ip(),
            ]);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
