@extends('layouts.app')
@section('title', 'Nilai Saya')

@section('content')
@php
    $breakdown = collect($grade->breakdown_json ?? []);
    // Tahap dianggap "belum dinilai" bila belum ada satu pun kriteria yang diberi nilai.
    $unscored = $breakdown->filter(fn ($b) => array_key_exists('scored_criteria', $b) && (int) $b['scored_criteria'] === 0);
    $isProvisional = $unscored->isNotEmpty();
    $hasOverride = $grade->override_score !== null;
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Rekap Nilai Saya</h1>
    <p class="text-slate-500">{{ $ay->label }} · dihitung otomatis dari assessment + peer 180 + penalti kehadiran.</p>
</div>

@if($isProvisional)
    <div class="mb-6 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
        <p class="font-semibold">ℹ️ Nilai sementara</p>
        <p class="mt-0.5">Tahap {{ $unscored->pluck('code')->join(', ') }} belum dinilai dan untuk sementara dihitung 0. Nilai &amp; indeks akan berubah setelah seluruh assessment dinilai koordinator.</p>
    </div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    <x-stat-card label="NA (sebelum penalti)" :value="number_format($grade->raw_score,1)" icon="calculator" color="blue" />
    <x-stat-card label="Hari Tidak Hadir (Alpa)" :value="$grade->absent_days" icon="calendar" color="indigo" />
    <x-stat-card label="Potongan Penalti" :value="number_format($grade->penalty_points,0).' poin'" icon="warning" color="amber" />
    <x-stat-card :label="$hasOverride ? 'Nilai Akhir (override)' : 'Nilai Akhir'" :value="number_format($grade->effective_score,1)" icon="trophy" color="green" />
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100">
            <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-semibold text-slate-800">Rincian per Tahap Assessment</h2></div>
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr><th class="px-5 py-2 font-medium">Tahap</th><th class="px-5 py-2 font-medium text-right">Rubrik Klp</th><th class="px-5 py-2 font-medium text-right">Peer</th><th class="px-5 py-2 font-medium text-right">Nilai</th><th class="px-5 py-2 font-medium text-right">Bobot</th><th class="px-5 py-2 font-medium text-right">Kontribusi</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($breakdown as $b)
                        @php $notYet = array_key_exists('scored_criteria', $b) && (int) $b['scored_criteria'] === 0; @endphp
                        <tr>
                            <td class="px-5 py-2 font-medium">{{ $b['code'] }}
                                @if($notYet)<span class="ml-1 text-[11px] rounded bg-slate-100 text-slate-500 px-1.5 py-0.5">Belum dinilai</span>
                                @elseif(isset($b['scored_criteria'], $b['total_criteria']) && $b['scored_criteria'] < $b['total_criteria'])<span class="ml-1 text-[11px] rounded bg-amber-50 text-amber-700 px-1.5 py-0.5">{{ $b['scored_criteria'] }}/{{ $b['total_criteria'] }} kriteria</span>@endif
                            </td>
                            <td class="px-5 py-2 text-right">{{ $notYet ? '—' : number_format($b['group_score'],1) }}</td>
                            <td class="px-5 py-2 text-right text-slate-500">{{ $b['peer_score'] !== null ? number_format($b['peer_score'],1) : '—' }}</td>
                            <td class="px-5 py-2 text-right">{{ $notYet ? '—' : number_format($b['stage_score'],1) }}</td>
                            <td class="px-5 py-2 text-right text-slate-500">{{ number_format($b['weight'],0) }}%</td>
                            <td class="px-5 py-2 text-right font-medium">{{ number_format($b['weighted'],2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50">
                    <tr><td colspan="5" class="px-5 py-2 text-right font-semibold">NA (sebelum penalti)</td><td class="px-5 py-2 text-right font-bold">{{ number_format($grade->raw_score,2) }}</td></tr>
                </tfoot>
            </table>
            <p class="px-5 py-3 border-t border-slate-100 text-xs text-slate-400">Nilai tahap = rubrik kelompok × (100% − bobot peer) + rata-rata peer × bobot peer. Bila belum ada penilaian peer, dipakai rubrik penuh.</p>
            @if($grade->penalty_level)
                <div class="px-5 py-3 border-t border-slate-100 text-sm text-red-600">Penalti kehadiran: {{ $grade->penalty_level }}</div>
            @endif
        </div>

        {{-- Rincian nilai rubrik per kriteria (nilai kelompok) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100">
            <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-semibold text-slate-800">Rincian Rubrik per Kriteria</h2><p class="text-xs text-slate-400">Nilai kelompok (sama untuk seluruh anggota tim). Kriteria yang belum dinilai dihitung 0.</p></div>
            <div class="divide-y divide-slate-100">
                @foreach($stages as $s)
                    <div class="px-5 py-3">
                        <p class="text-sm font-semibold text-slate-700 mb-1.5">{{ $s->code }} — {{ $s->name }}</p>
                        @if($s->criteria->isEmpty())
                            <p class="text-xs text-slate-400">Belum ada kriteria.</p>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-sm">
                                @foreach($s->criteria as $c)
                                    <div class="flex justify-between gap-3">
                                        <span class="text-slate-500">{{ $c->name }}</span>
                                        <span class="{{ isset($criterionScores[$c->id]) ? 'font-medium text-slate-800' : 'text-slate-400' }}">{{ isset($criterionScores[$c->id]) ? number_format($criterionScores[$c->id],1) : 'belum dinilai' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6 flex flex-col items-center justify-center">
            <p class="text-slate-500 text-sm">Indeks Nilai{{ $isProvisional ? ' (sementara)' : '' }}</p>
            <div class="my-3 h-28 w-28 rounded-full bg-gradient-to-br from-brand to-pink-500 text-white flex items-center justify-center text-4xl font-bold shadow-lg shadow-rose-200">{{ $grade->grade_letter }}</div>
            <p class="text-2xl font-bold text-brand-dark">{{ number_format($grade->effective_score,1) }}</p>
            @if($hasOverride)
                <p class="text-xs text-amber-600 mt-2 text-center">*Nilai ditetapkan (override) koordinator. Nilai hasil hitung: {{ number_format($grade->final_score,1) }}.</p>
                @if($grade->override_note)<p class="text-xs text-slate-500 mt-1 text-center">Catatan: {{ $grade->override_note }}</p>@endif
            @endif
        </div>

        {{-- Aturan penalti kehadiran (mengikuti pengaturan koordinator) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <h2 class="font-semibold text-slate-800 mb-2"><x-icon name="warning" class="ico" /> Aturan Penalti Kehadiran</h2>
            <p class="text-xs text-slate-400 mb-3">Dihitung dari jumlah sesi berstatus Alpa. Anda saat ini: <span class="font-semibold text-slate-700">{{ $grade->absent_days }} hari alpa</span>.</p>
            <ul class="space-y-1.5 text-sm">
                @forelse($rules as $r)
                    @php $active = $r->matches((int) $grade->absent_days); @endphp
                    <li class="flex justify-between gap-2 rounded-lg px-2 py-1 {{ $active ? 'bg-red-50 text-red-700 font-medium' : 'text-slate-600' }}">
                        <span>{{ $r->min_days }}{{ $r->max_days !== null ? '–'.$r->max_days : '+' }} hari</span>
                        <span>{{ $r->penalty_type === 'fail' ? 'Nilai E' : ($r->penalty_type === 'points_deduction' ? '−'.number_format($r->deduction_points,0).' poin' : 'Tanpa potongan') }}</span>
                    </li>
                @empty
                    <li class="text-slate-400">Tidak ada aturan penalti.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
