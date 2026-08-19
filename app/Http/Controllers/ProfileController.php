<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        // Panel observasi hanya untuk superadmin yang tidak sedang mengobservasi.
        $students = collect();
        $classes = collect();
        if ($user->isSuperadmin() && ! $request->session()->has('impersonator_id')) {
            $students = User::where('role', 'mahasiswa')
                ->when($request->filled('class'), fn ($q) => $q->where('class_name', $request->class))
                ->when($request->filled('q'), function ($q) use ($request) {
                    $s = $request->q;
                    $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('identity_number', 'like', "%$s%"));
                })
                ->orderBy('class_name')->orderBy('name')->limit(50)->get();
            $classes = User::where('role', 'mahasiswa')->whereNotNull('class_name')->distinct()->orderBy('class_name')->pluck('class_name');
        }

        return view('profile.show', compact('user', 'students', 'classes'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return back()->with('success', 'Profil diperbarui.');
    }
}
