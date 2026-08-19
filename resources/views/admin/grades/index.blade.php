@extends('layouts.app')
@section('title', 'Rekap Nilai Akhir')

@section('content')
<div class="mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-bold text-brand-dark">Rekap Nilai Akhir</h1>
        <p class="text-slate-500">{{ $ay->label }} · NA setelah pembobotan, peer 180, & penalti kehadiran.</p>
    </div>
    <div class="flex items-center gap-2">
        <form method="GET">
            <select name="class" onchange="this.form.submit()" class="rounded-lg border-slate-300 border px-3 py-2 text-sm">
                <option value="">Semua Kelas</option>
                @foreach($classes as $c)<option value="{{ $c }}" @selected(request('class')==$c)>{{ $c }}</option>@endforeach
            </select>
        </form>
        <form method="POST" action="{{ route('admin.grades.recalculate') }}">@csrf<button class="rounded-lg bg-brand text-white px-4 py-2 text-sm font-medium hover:bg-brand-dark">🔄 Rekalkulasi</button></form>
    </div>
</div>

@if($grades->isEmpty())
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-800">Belum ada nilai. Klik <strong>Rekalkulasi</strong>.</div>
@else
<div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
    @foreach($grades as $g)<form id="ovr-{{ $g->id }}" method="POST" action="{{ route('admin.grades.override', $g) }}">@csrf @method('PUT')</form>@endforeach
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr><th class="px-4 py-3 font-medium">#</th><th class="px-4 py-3 font-medium">Mahasiswa</th><th class="px-4 py-3 font-medium text-right">NA</th><th class="px-4 py-3 font-medium text-right">Alpa</th><th class="px-4 py-3 font-medium text-right">Penalti</th><th class="px-4 py-3 font-medium text-right">Akhir</th><th class="px-4 py-3 font-medium text-center">Indeks</th><th class="px-4 py-3 font-medium">Override</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($grades as $i => $g)
                <tr>
                    <td class="px-4 py-3 text-slate-400">{{ $i+1 }}</td>
                    <td class="px-4 py-3 font-medium text-slate-800">{{ $g->student->name }}<span class="block text-xs text-slate-400">{{ $g->student->identity_number }} · {{ $g->student->class_name }}</span></td>
                    <td class="px-4 py-3 text-right">{{ number_format($g->raw_score,1) }}</td>
                    <td class="px-4 py-3 text-right {{ $g->absent_days > 10 ? 'text-red-700 font-semibold' : '' }}">{{ $g->absent_days }}</td>
                    <td class="px-4 py-3 text-right">{{ $g->penalty_points > 0 ? '−'.number_format($g->penalty_points,0) : ($g->penalty_level && str_contains($g->penalty_level,'Berat') ? 'FAIL' : '0') }}</td>
                    <td class="px-4 py-3 text-right font-semibold">{{ number_format($g->final_score,1) }}</td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 font-bold text-slate-700">{{ $g->grade_letter }}</span></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1">
                            <input form="ovr-{{ $g->id }}" type="number" step="0.1" name="override_score" value="{{ $g->override_score }}" placeholder="—" class="w-20 rounded border-slate-300 border px-2 py-1">
                            <button form="ovr-{{ $g->id }}" title="Simpan override" class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50"><x-icon name="save" /></button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p class="mt-3 text-xs text-slate-400">Override mengganti nilai akhir tanpa mengubah data mentah. Kosongkan lalu Set untuk menghapus.</p>
@endif
@endsection
