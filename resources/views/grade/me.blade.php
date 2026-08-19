@extends('layouts.app')
@section('title', 'Nilai Saya')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Rekap Nilai Saya</h1>
    <p class="text-slate-500">{{ $ay->label }} · dihitung otomatis dari assessment + peer 180 + penalti kehadiran.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    <x-stat-card label="Nilai Akhir (NA)" :value="number_format($grade->raw_score,1)" icon="🧮" color="blue" />
    <x-stat-card label="Hari Tidak Hadir" :value="$grade->absent_days" icon="📅" color="indigo" />
    <x-stat-card label="Potongan Penalti" :value="number_format($grade->penalty_points,0).' poin'" icon="⚠️" color="amber" />
    <x-stat-card label="Nilai Setelah Penalti" :value="number_format($grade->effective_score,1)" icon="🏆" color="green" />
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-rose-100">
        <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-semibold text-slate-800">Rincian per Tahap Assessment</h2></div>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr><th class="px-5 py-2 font-medium">Tahap</th><th class="px-5 py-2 font-medium text-right">Rubrik Klp</th><th class="px-5 py-2 font-medium text-right">Peer</th><th class="px-5 py-2 font-medium text-right">Nilai</th><th class="px-5 py-2 font-medium text-right">Bobot</th><th class="px-5 py-2 font-medium text-right">Kontribusi</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($grade->breakdown_json ?? [] as $b)
                    <tr>
                        <td class="px-5 py-2 font-medium">{{ $b['code'] }}</td>
                        <td class="px-5 py-2 text-right">{{ number_format($b['group_score'],1) }}</td>
                        <td class="px-5 py-2 text-right text-slate-500">{{ $b['peer_score'] !== null ? number_format($b['peer_score'],1) : '—' }}</td>
                        <td class="px-5 py-2 text-right">{{ number_format($b['stage_score'],1) }}</td>
                        <td class="px-5 py-2 text-right text-slate-500">{{ number_format($b['weight'],0) }}%</td>
                        <td class="px-5 py-2 text-right font-medium">{{ number_format($b['weighted'],2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50">
                <tr><td colspan="5" class="px-5 py-2 text-right font-semibold">Nilai Akhir (NA)</td><td class="px-5 py-2 text-right font-bold">{{ number_format($grade->raw_score,2) }}</td></tr>
            </tfoot>
        </table>
        @if($grade->penalty_level)
            <div class="px-5 py-3 border-t border-slate-100 text-sm text-red-600">Penalti kehadiran: {{ $grade->penalty_level }}</div>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6 flex flex-col items-center justify-center">
        <p class="text-slate-500 text-sm">Indeks Nilai</p>
        <div class="my-3 h-28 w-28 rounded-full bg-gradient-to-br from-brand to-pink-500 text-white flex items-center justify-center text-4xl font-bold shadow-lg shadow-rose-200">{{ $grade->grade_letter }}</div>
        <p class="text-2xl font-bold text-brand-dark">{{ number_format($grade->effective_score,1) }}</p>
        @if($grade->override_score !== null)<p class="text-xs text-amber-600 mt-2">*Nilai di-override koordinator</p>@endif
    </div>
</div>
@endsection
