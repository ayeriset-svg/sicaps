<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('q'), function ($q) use ($request) {
                $s = $request->q;
                $q->where(fn ($w) => $w->where('name', 'like', "%$s%")
                    ->orWhere('identity_number', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%"));
            })
            ->orderBy('role')->orderBy('name');

        $perPage = $request->input('per_page', 20);
        $perPage = $perPage === 'all'
            ? max(1, (clone $query)->count())
            : (in_array((int) $perPage, [10, 20, 50], true) ? (int) $perPage : 20);

        $users = $query->paginate($perPage)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'identity_number' => ['required', 'string', 'max:30', 'unique:users,identity_number'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(['superadmin', 'mahasiswa'])],
            'angkatan' => ['nullable', 'string', 'max:10'],
            'class_name' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $data['password'] = Hash::make($data['password']);
        // Mahasiswa wajib ganti sandi saat login pertama (aktivasi).
        $data['must_change_password'] = $data['role'] === 'mahasiswa';
        User::create($data);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'identity_number' => ['required', 'string', 'max:30', Rule::unique('users', 'identity_number')->ignore($user->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['superadmin', 'mahasiswa'])],
            'angkatan' => ['nullable', 'string', 'max:10'],
            'class_name' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $data['is_active'] = $request->boolean('is_active');

        $user->update($data);

        return back()->with('success', 'Data user diperbarui.');
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 422, 'Tidak dapat menghapus akun sendiri.');
        $user->delete();

        return back()->with('success', 'User dihapus.');
    }

    /**
     * Import massal via CSV. Header: identity_number,name,email,role,angkatan,class_name,password
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = null;
        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim($h)), $row);
                continue;
            }
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $rowData = @array_combine($header, array_pad($row, count($header), null));
            $identity = trim($rowData['identity_number'] ?? '');
            $email = trim($rowData['email'] ?? '');

            if ($identity === '' || $email === '') {
                $skipped++;
                continue;
            }
            if (User::where('identity_number', $identity)->orWhere('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            $role = in_array($rowData['role'] ?? '', ['superadmin', 'mahasiswa'], true)
                ? $rowData['role'] : 'mahasiswa';

            User::create([
                'identity_number' => $identity,
                'name' => trim($rowData['name'] ?? $identity),
                'email' => $email,
                'role' => $role,
                'angkatan' => trim($rowData['angkatan'] ?? '') ?: null,
                'class_name' => trim($rowData['class_name'] ?? '') ?: null,
                'password' => Hash::make(trim($rowData['password'] ?? '') ?: $identity),
                // Sandi default = NIM/NIP → wajib diganti saat login pertama.
                'must_change_password' => true,
            ]);
            $created++;
        }
        fclose($handle);

        return back()->with('success', "Import selesai: {$created} user dibuat, {$skipped} dilewati (duplikat/invalid).");
    }
}
