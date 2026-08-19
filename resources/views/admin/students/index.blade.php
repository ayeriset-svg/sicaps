@extends('layouts.app')
@section('title', 'Master Mahasiswa')

@section('content')
<div class="mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-bold text-brand-dark">Master Data Mahasiswa</h1>
        <p class="text-slate-500">Data lengkap per kelas & angkatan. Import mendukung data + nilai historis.</p>
    </div>
    <div x-data="{ open:false }">
        <button @click="open=true" class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark">Import CSV</button>
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="open=false">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                <h3 class="font-semibold mb-2">Import Master Mahasiswa + Nilai Historis</h3>
                <p class="text-xs text-slate-500 mb-3">Header CSV:<br>
                    <code class="text-[11px]">identity_number,name,email,angkatan,class_name,password,year,semester,final_score,grade_letter</code><br>
                    Kolom <code>year..grade_letter</code> opsional — bila diisi, dibuat nilai historis (tahun ajaran otomatis diarsipkan).
                </p>
                <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="file" accept=".csv,.txt" required class="w-full text-sm">
                    <div class="flex justify-end gap-2"><button type="button" @click="open=false" class="px-4 py-2 text-sm">Batal</button><button class="rounded-lg bg-brand text-white px-4 py-2 text-sm">Import</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($pendingCount > 0)
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-center justify-between flex-wrap gap-2">
        <span>⏳ <strong>{{ $pendingCount }}</strong> mahasiswa <strong>belum aktivasi</strong> (masih pakai sandi default = NIM, belum ganti sandi saat login pertama).</span>
        <a href="{{ route('admin.students.index', ['activation' => 'pending']) }}" class="rounded-lg bg-amber-600 text-white px-3 py-1.5 hover:bg-amber-700">Lihat yang belum aktivasi →</a>
    </div>
@endif

<form method="GET" class="mb-4 flex flex-wrap gap-2 text-sm">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama / NIM..." class="rounded-lg border-slate-300 border px-3 py-2 flex-1 max-w-xs">
    <select name="angkatan" class="rounded-lg border-slate-300 border px-3 py-2"><option value="">Semua Angkatan</option>@foreach($angkatans as $a)<option value="{{ $a }}" @selected(request('angkatan')==$a)>{{ $a }}</option>@endforeach</select>
    <select name="class" class="rounded-lg border-slate-300 border px-3 py-2"><option value="">Semua Kelas</option>@foreach($classes as $c)<option value="{{ $c }}" @selected(request('class')==$c)>{{ $c }}</option>@endforeach</select>
    <select name="activation" class="rounded-lg border-slate-300 border px-3 py-2">
        <option value="">Semua Status</option>
        <option value="pending" @selected(request('activation')==='pending')>Belum Aktivasi</option>
        <option value="active" @selected(request('activation')==='active')>Sudah Aktivasi</option>
    </select>
    <select name="per_page" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white" title="Baris per halaman">
        @foreach([10,20,50] as $pp)<option value="{{ $pp }}" @selected((string)request('per_page','20')===(string)$pp)>Show {{ $pp }}</option>@endforeach
        <option value="all" @selected(request('per_page')==='all')>Show All</option>
    </select>
    <button class="rounded-lg bg-brand text-white px-4 py-2 hover:bg-brand-dark">Filter</button>
    @if(request()->hasAny(['q','angkatan','class','activation','per_page']))<a href="{{ route('admin.students.index') }}" class="px-2 py-2 text-brand hover:underline">Reset</a>@endif
</form>

<div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr><th class="px-5 py-3 font-medium">NIM</th><th class="px-5 py-3 font-medium">Nama</th><th class="px-5 py-3 font-medium">Angkatan</th><th class="px-5 py-3 font-medium">Kelas</th><th class="px-5 py-3 font-medium">Email</th><th class="px-5 py-3 font-medium">Aktivasi</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($students as $s)
                <tr>
                    <td class="px-5 py-3 font-mono text-slate-600">{{ $s->identity_number }}</td>
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $s->name }}</td>
                    <td class="px-5 py-3">{{ $s->angkatan ?? '—' }}</td>
                    <td class="px-5 py-3">{{ $s->class_name ?? '—' }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ $s->email }}</td>
                    <td class="px-5 py-3">
                        @if($s->must_change_password)
                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 text-amber-700 px-2.5 py-0.5 text-xs font-semibold">⏳ Belum Aktivasi</span>
                        @else
                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 px-2.5 py-0.5 text-xs font-semibold">✓ Aktif</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Tidak ada data mahasiswa.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $students->links() }}</div>
@endsection
