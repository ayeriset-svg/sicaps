@extends('layouts.app')
@section('title', 'Penalty Kehadiran')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Aturan Penalty Kehadiran</h1>
    <p class="text-slate-500">{{ $ay->label }} · pengurangan nilai berdasarkan jumlah HARI tidak hadir.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
        @foreach($rules as $r)
            <form id="rule-{{ $r->id }}" method="POST" action="{{ route('admin.penalty.update', $r) }}">@csrf @method('PUT')</form>
            <form id="rule-del-{{ $r->id }}" method="POST" action="{{ route('admin.penalty.destroy', $r) }}" onsubmit="return confirm('Hapus aturan?')">@csrf @method('DELETE')</form>
        @endforeach
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr><th class="px-4 py-3 font-medium">Label</th><th class="px-4 py-3 font-medium">Min Hari</th><th class="px-4 py-3 font-medium">Max Hari</th><th class="px-4 py-3 font-medium">Tipe</th><th class="px-4 py-3 font-medium">Potongan (poin)</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rules as $r)
                    <tr>
                        <td class="px-4 py-3"><input form="rule-{{ $r->id }}" name="label" value="{{ $r->label }}" class="w-full rounded border-slate-300 border px-2 py-1"></td>
                        <td class="px-4 py-3"><input form="rule-{{ $r->id }}" type="number" name="min_days" value="{{ $r->min_days }}" class="w-16 rounded border-slate-300 border px-2 py-1"></td>
                        <td class="px-4 py-3"><input form="rule-{{ $r->id }}" type="number" name="max_days" value="{{ $r->max_days }}" placeholder="∞" class="w-16 rounded border-slate-300 border px-2 py-1"></td>
                        <td class="px-4 py-3">
                            <select form="rule-{{ $r->id }}" name="penalty_type" class="rounded border-slate-300 border px-2 py-1">
                                @foreach(['none'=>'None','points_deduction'=>'Kurangi Poin','fail'=>'Fail (E)'] as $k=>$v)<option value="{{ $k }}" @selected($r->penalty_type===$k)>{{ $v }}</option>@endforeach
                            </select>
                        </td>
                        <td class="px-4 py-3"><input form="rule-{{ $r->id }}" type="number" step="0.01" name="deduction_points" value="{{ $r->deduction_points }}" class="w-20 rounded border-slate-300 border px-2 py-1"></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                <button form="rule-{{ $r->id }}" title="Simpan" class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50"><x-icon name="save" /></button>
                                <button form="rule-del-{{ $r->id }}" title="Hapus" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50"><x-icon name="trash" /></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">Belum ada aturan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6">
            <h2 class="font-semibold text-slate-800 mb-4">Tambah Aturan</h2>
            <form method="POST" action="{{ route('admin.penalty.store') }}" class="space-y-3">
                @csrf
                <div><label class="block text-sm font-medium mb-1">Label</label><input name="label" class="w-full rounded-lg border-slate-300 border px-3 py-2"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-sm font-medium mb-1">Min Hari</label><input type="number" name="min_days" required class="w-full rounded-lg border-slate-300 border px-3 py-2"></div>
                    <div><label class="block text-sm font-medium mb-1">Max Hari</label><input type="number" name="max_days" placeholder="kosong = ∞" class="w-full rounded-lg border-slate-300 border px-3 py-2"></div>
                </div>
                <div><label class="block text-sm font-medium mb-1">Tipe</label>
                    <select name="penalty_type" class="w-full rounded-lg border-slate-300 border px-3 py-2">
                        <option value="none">None</option><option value="points_deduction">Kurangi Poin</option><option value="fail">Fail (E)</option>
                    </select>
                </div>
                <div><label class="block text-sm font-medium mb-1">Potongan (poin)</label><input type="number" step="0.01" name="deduction_points" value="0" required class="w-full rounded-lg border-slate-300 border px-3 py-2"></div>
                <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm w-full hover:bg-brand-dark">Tambah</button>
            </form>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-500">
            <p class="font-medium text-slate-700 mb-1">Default RPS:</p>
            Ringan 4–6 hari → −7 poin · Sedang 7–10 hari → −15 poin · Berat &gt;10 hari → Fail (Grade E). Ubah lalu jalankan rekalkulasi di Rekap Nilai.
        </div>
    </div>
</div>
@endsection
