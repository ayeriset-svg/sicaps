@extends('layouts.app')
@section('title', 'Tahun Ajaran')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Manajemen Tahun Ajaran & Angkatan</h1>
    <p class="text-slate-500">Aktifkan satu tahun ajaran; membuat baru otomatis menyiapkan bobot & penalty default.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr><th class="px-5 py-3 font-medium">Tahun Ajaran</th><th class="px-5 py-3 font-medium">Tim</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium text-right">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($years as $y)
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $y->label }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $y->teams_count }}</td>
                        <td class="px-5 py-3">
                            @if($y->is_active)<span class="inline-flex rounded-full bg-green-100 text-green-700 px-2.5 py-0.5 text-xs font-medium">Aktif</span>
                            @elseif($y->is_archived)<span class="inline-flex rounded-full bg-slate-100 text-slate-500 px-2.5 py-0.5 text-xs">Diarsipkan</span>
                            @else<span class="inline-flex rounded-full bg-amber-100 text-amber-700 px-2.5 py-0.5 text-xs">Nonaktif</span>@endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                @unless($y->is_active)
                                    <form method="POST" action="{{ route('admin.academic-years.activate', $y) }}" class="inline">@csrf<button title="Aktifkan" class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50"><x-icon name="power" /></button></form>
                                @endunless
                                @unless($y->is_archived)
                                    <form method="POST" action="{{ route('admin.academic-years.archive', $y) }}" class="inline" onsubmit="return confirm('Arsipkan tahun ajaran ini?')">@csrf<button title="Arsipkan" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="archive" /></button></form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">Belum ada tahun ajaran.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Tambah Tahun Ajaran</h2>
        <form method="POST" action="{{ route('admin.academic-years.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Tahun</label>
                <input name="year" placeholder="2025/2026" value="{{ old('year') }}" required class="w-full rounded-lg border-slate-300 border px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Semester</label>
                <select name="semester" class="w-full rounded-lg border-slate-300 border px-3 py-2">
                    <option value="ganjil">Ganjil</option>
                    <option value="genap">Genap</option>
                </select>
            </div>
            <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm font-medium hover:bg-brand-dark w-full">Buat Tahun Ajaran</button>
        </form>
    </div>
</div>
@endsection
