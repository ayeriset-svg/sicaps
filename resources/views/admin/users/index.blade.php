@extends('layouts.app')
@section('title', 'Master Data User')

@section('content')
<div class="mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-bold text-brand-dark">Master Data User</h1>
        <p class="text-slate-500">Kelola akun Mahasiswa, Dosen, dan Koordinator.</p>
    </div>
    <div class="flex gap-2" x-data="{ addOpen:false, importOpen:false }">
        <button @click="importOpen=!importOpen" class="rounded-lg bg-slate-100 px-4 py-2 text-sm hover:bg-slate-200">Import CSV</button>
        <button @click="addOpen=!addOpen" class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark">+ Tambah User</button>

        <div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="importOpen=false">
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h3 class="font-semibold mb-2">Import User (CSV)</h3>
                <p class="text-xs text-slate-500 mb-3">Header: <code>identity_number,name,email,role,angkatan,class_name,password</code>. Password kosong → default = NIM/NIP.</p>
                <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="file" accept=".csv,.txt" required class="w-full text-sm">
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="importOpen=false" class="px-4 py-2 text-sm">Batal</button>
                        <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm">Import</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="addOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="addOpen=false">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                <h3 class="font-semibold mb-4">Tambah User</h3>
                <form method="POST" action="{{ route('admin.users.store') }}" class="grid grid-cols-2 gap-3">
                    @csrf
                    <div><label class="block text-xs font-medium mb-1">NIM/NIP/NIDN</label><input name="identity_number" required class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-medium mb-1">Nama</label><input name="name" required class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                    <div class="col-span-2"><label class="block text-xs font-medium mb-1">Email</label><input type="email" name="email" required class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-medium mb-1">Role</label>
                        <select name="role" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm">
                            <option value="mahasiswa">Mahasiswa</option><option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                    <div><label class="block text-xs font-medium mb-1">Password</label><input name="password" required class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-medium mb-1">Angkatan</label><input name="angkatan" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-medium mb-1">Kelas</label><input name="class_name" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                    <div class="col-span-2 flex justify-end gap-2 mt-2">
                        <button type="button" @click="addOpen=false" class="px-4 py-2 text-sm">Batal</button>
                        <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<form method="GET" class="mb-4 flex gap-2 text-sm">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama / NIM / email..." class="rounded-lg border-slate-300 border px-3 py-2 flex-1 max-w-sm">
    <select name="role" class="rounded-lg border-slate-300 border px-3 py-2">
        <option value="">Semua Role</option>
        @foreach(['mahasiswa','superadmin'] as $r)<option value="{{ $r }}" @selected(request('role')===$r)>{{ ucfirst($r) }}</option>@endforeach
    </select>
    <select name="per_page" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white" title="Baris per halaman">
        @foreach([10,20,50] as $pp)<option value="{{ $pp }}" @selected((string)request('per_page','20')===(string)$pp)>Show {{ $pp }}</option>@endforeach
        <option value="all" @selected(request('per_page')==='all')>Show All</option>
    </select>
    <button class="rounded-lg bg-brand text-white px-4 py-2 hover:bg-brand-dark">Cari</button>
</form>

<div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr><th class="px-5 py-3 font-medium">Identitas</th><th class="px-5 py-3 font-medium">Nama</th><th class="px-5 py-3 font-medium">Email</th><th class="px-5 py-3 font-medium">Role</th><th class="px-5 py-3 font-medium">Aktif</th><th class="px-5 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($users as $user)
                <tr x-data="{ edit:false }">
                    <td class="px-5 py-3 font-mono text-slate-600">{{ $user->identity_number }}</td>
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $user->name }}<br><span class="text-xs text-slate-400">{{ $user->angkatan }} {{ $user->class_name }}</span></td>
                    <td class="px-5 py-3 text-slate-500">{{ $user->email }}</td>
                    <td class="px-5 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $user->role }}</span></td>
                    <td class="px-5 py-3">{!! $user->is_active ? '✅' : '⛔' !!}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            <button @click="edit=true" title="Edit" class="p-1.5 rounded-lg text-brand hover:bg-rose-50"><x-icon name="edit" /></button>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Hapus user ini?')">@csrf @method('DELETE')<button title="Hapus" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50"><x-icon name="trash" /></button></form>
                        </div>

                        <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 text-left" @click.self="edit=false">
                            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                                <h3 class="font-semibold mb-4">Edit {{ $user->name }}</h3>
                                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid grid-cols-2 gap-3">
                                    @csrf @method('PUT')
                                    <div><label class="block text-xs font-medium mb-1">NIM/NIP</label><input name="identity_number" value="{{ $user->identity_number }}" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                                    <div><label class="block text-xs font-medium mb-1">Nama</label><input name="name" value="{{ $user->name }}" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                                    <div class="col-span-2"><label class="block text-xs font-medium mb-1">Email</label><input name="email" value="{{ $user->email }}" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                                    <div><label class="block text-xs font-medium mb-1">Role</label>
                                        <select name="role" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm">
                                            @foreach(['mahasiswa','superadmin'] as $r)<option value="{{ $r }}" @selected($user->role===$r)>{{ ucfirst($r) }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div><label class="block text-xs font-medium mb-1">Password (kosongkan = tetap)</label><input name="password" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                                    <div><label class="block text-xs font-medium mb-1">Angkatan</label><input name="angkatan" value="{{ $user->angkatan }}" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                                    <div><label class="block text-xs font-medium mb-1">Kelas</label><input name="class_name" value="{{ $user->class_name }}" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                                    <label class="col-span-2 flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($user->is_active)> Akun aktif</label>
                                    <div class="col-span-2 flex justify-end gap-2 mt-2">
                                        <button type="button" @click="edit=false" class="px-4 py-2 text-sm">Batal</button>
                                        <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Tidak ada user.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
