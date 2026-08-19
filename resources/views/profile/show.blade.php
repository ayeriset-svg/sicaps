@extends('layouts.app')
@section('title', 'Profil Akun')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Profil Akun</h1>
    <p class="text-slate-500">Kelola data akun Anda{{ $user->isSuperadmin() ? ' dan jalankan Mode Observasi mahasiswa.' : '.' }}</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Edit akun --}}
    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Data Akun</h2>
        <dl class="text-sm space-y-1.5 mb-4">
            <div class="flex justify-between"><dt class="text-slate-500">Identitas</dt><dd class="font-medium">{{ $user->identity_number }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Role</dt><dd class="font-medium capitalize">{{ $user->role }}</dd></div>
            @if($user->class_name)<div class="flex justify-between"><dt class="text-slate-500">Kelas</dt><dd class="font-medium">{{ $user->class_name }}</dd></div>@endif
        </dl>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-3 border-t border-rose-100 pt-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium mb-1">Nama</label>
                <input name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border-rose-200 border px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-lg border-rose-200 border px-3 py-2">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Password Baru</label>
                    <input type="password" name="password" placeholder="kosong = tetap" class="w-full rounded-lg border-rose-200 border px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Konfirmasi</label>
                    <input type="password" name="password_confirmation" class="w-full rounded-lg border-rose-200 border px-3 py-2">
                </div>
            </div>
            <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm font-medium hover:bg-brand-dark">Simpan Profil</button>
        </form>
    </div>

    {{-- Panel Observasi (superadmin) --}}
    @if($user->isSuperadmin())
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-rose-100 p-6">
            <div class="flex items-start justify-between gap-3 mb-4 flex-wrap">
                <div>
                    <h2 class="font-semibold text-slate-800">🔍 Mode Observasi</h2>
                    <p class="text-sm text-slate-500">Masuk ke tampilan seorang mahasiswa untuk mengamati — tanpa login/logout akun berbeda. Anda dapat kembali kapan saja.</p>
                </div>
            </div>

            <form method="GET" class="flex flex-wrap gap-2 mb-4 text-sm">
                <input name="q" value="{{ request('q') }}" placeholder="Cari nama / NIM..." class="rounded-lg border-rose-200 border px-3 py-2 flex-1 max-w-xs">
                <select name="class" class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $c)<option value="{{ $c }}" @selected(request('class')==$c)>{{ $c }}</option>@endforeach
                </select>
                <button class="rounded-lg bg-slate-700 text-white px-4 py-2">Cari</button>
                @if(request('q') || request('class'))<a href="{{ route('profile.show') }}" class="px-2 py-2 text-brand hover:underline">Reset</a>@endif
            </form>

            <div class="border border-rose-100 rounded-xl divide-y divide-rose-50 max-h-96 overflow-y-auto">
                @forelse($students as $s)
                    <div class="flex items-center justify-between px-4 py-2.5 hover:bg-rose-50/40">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $s->name }}</p>
                            <p class="text-xs text-slate-400">{{ $s->identity_number }} · {{ $s->class_name ?? '—' }} · {{ $s->angkatan ?? '—' }}</p>
                        </div>
                        <form method="POST" action="{{ route('observe.start', $s) }}">
                            @csrf
                            <button class="rounded-lg bg-brand text-white px-3 py-1.5 text-xs font-semibold hover:bg-brand-dark">Observasi →</button>
                        </form>
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-slate-400 text-sm">Tidak ada mahasiswa.</p>
                @endforelse
            </div>
            <p class="text-xs text-slate-400 mt-3">Menampilkan hingga 50 mahasiswa. Gunakan pencarian untuk mempersempit.</p>
        </div>
    @endif
</div>
@endsection
