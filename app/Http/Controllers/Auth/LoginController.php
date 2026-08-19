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

        if (! $request->user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => 'Akun Anda tidak aktif. Hubungi koordinator.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
