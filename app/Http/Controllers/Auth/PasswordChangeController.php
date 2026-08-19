<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordChangeController extends Controller
{
    public function show(Request $request)
    {
        // Jika sudah tidak wajib ganti, tak perlu di halaman ini.
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.change-password', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        // Sandi baru tidak boleh sama dengan NIM/identitas (sandi default).
        if (strcasecmp($data['password'], $user->identity_number) === 0) {
            return back()->withErrors(['password' => 'Kata sandi baru tidak boleh sama dengan NIM/identitas Anda.']);
        }

        $user->update([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')->with('success', 'Kata sandi berhasil dibuat. Akun Anda aktif sepenuhnya.');
    }
}
