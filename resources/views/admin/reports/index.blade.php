@extends('layouts.app')
@section('title', 'Summary Report')

@section('content')
<div class="mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-bold text-brand-dark">Summary Report</h1>
        <p class="text-slate-500">Rekapitulasi nilai per kelas, sebaran indeks, topik & HKI.</p>
    </div>
    <form method="GET">
        <select name="year" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 text-sm bg-white">
            @foreach($years as $y)<option value="{{ $y->id }}" @selected($ay && $ay->id===$y->id)>{{ $y->label }} {{ $y->is_active ? '(aktif)' : ($y->is_archived ? '(arsip)' : '') }}</option>@endforeach
        </select>
    </form>
</div>

@if(! $ay || $overallCount === 0)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-800">Belum ada data nilai untuk periode ini.</div>
@else
@php
    $colors = ['A'=>'#A61010','AB'=>'#C81E1E','B'=>'#DC2626','BC'=>'#E11D48','C'=>'#F43F5E','D'=>'#FB7185','E'=>'#9CA3AF'];
    $r = 68; $circ = 2 * M_PI * $r; $offset = 0;
    $maxDist = max(1, max($distribution));
    $lulus = collect(['A','AB','B','BC','C'])->sum(fn($i)=>$distribution[$i]??0);
    $tidak = ($distribution['D']??0)+($distribution['E']??0);
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-stat-card label="Total Mahasiswa" :value="$overallCount" icon="🎓" color="brand" />
    <x-stat-card label="Rata-rata Nilai" :value="number_format($overallAvg,1)" icon="📊" color="pink" :progress="$overallAvg" />
    <x-stat-card label="Lulus (≥ C)" :value="$lulus" icon="✅" color="green" :hint="$overallCount ? round($lulus/$overallCount*100).'% dari total' : null" />
    <x-stat-card label="Tidak Lulus (D/E)" :value="$tidak" icon="⚠️" color="amber" />
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 items-stretch">
    {{-- Donut --}}
    <div class="bg-white rounded-2xl border border-rose-100 shadow-sm p-6 flex flex-col">
        <h2 class="font-semibold text-slate-800 mb-4">Sebaran Indeks Nilai</h2>
        <div class="relative mx-auto w-48 h-48">
            <svg viewBox="0 0 180 180" class="w-48 h-48 -rotate-90">
                <circle cx="90" cy="90" r="{{ $r }}" fill="none" stroke="#f5e1e1" stroke-width="24"/>
                @foreach($indices as $idx)
                    @php $c = $distribution[$idx] ?? 0; if ($c==0) continue; $len = $c/$overallCount*$circ; @endphp
                    <circle cx="90" cy="90" r="{{ $r }}" fill="none" stroke="{{ $colors[$idx] }}" stroke-width="24"
                            stroke-dasharray="{{ $len }} {{ $circ-$len }}" stroke-dashoffset="{{ -$offset }}" stroke-linecap="butt"/>
                    @php $offset += $len; @endphp
                @endforeach
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-3xl font-extrabold text-brand-dark">{{ $overallCount }}</span>
                <span class="text-xs text-slate-400 uppercase tracking-wide">Mahasiswa</span>
            </div>
        </div>
        <div class="mt-5 grid grid-cols-2 gap-x-5 gap-y-1.5 text-sm">
            @foreach($indices as $idx)
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-sm" style="background: {{ $colors[$idx] }}"></span>
                    <span class="text-slate-600 font-medium">{{ $idx }}</span>
                    <span class="ml-auto text-slate-500">{{ $distribution[$idx] ?? 0 }} <span class="text-slate-300">·</span> {{ $overallCount ? round(($distribution[$idx]??0)/$overallCount*100) : 0 }}%</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Recap + bar chart --}}
    <div class="lg:col-span-2 flex flex-col gap-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-2xl border border-rose-100 bg-gradient-to-br from-brand to-brand-dark text-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wider text-pink-100/80">Topik dari Mitra</p>
                <p class="text-3xl font-extrabold mt-1">{{ $topicMitra }}</p>
                <p class="text-xs text-pink-100/70 mt-1">dari {{ $teamCount }} tim bertopik</p>
            </div>
            <div class="rounded-2xl border border-pink-100 bg-gradient-to-br from-pink-500 to-rose-500 text-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wider text-pink-50/80">Topik Mandiri</p>
                <p class="text-3xl font-extrabold mt-1">{{ $topicMandiri }}</p>
                <p class="text-xs text-pink-50/70 mt-1">submit mandiri</p>
            </div>
            <div class="rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wider text-brand-dark/60">Tim Layak HKI</p>
                <p class="text-3xl font-extrabold mt-1 text-brand">{{ $hkiCount }}</p>
                <p class="text-xs text-slate-400 mt-1">berhak diajukan HKI</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-rose-100 shadow-sm p-6 flex-1">
            <h2 class="font-semibold text-slate-800 mb-4">Distribusi Indeks Nilai</h2>
            <div class="space-y-2.5">
                @foreach($indices as $idx)
                    @php $c = $distribution[$idx] ?? 0; @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-8 text-sm font-bold text-right" style="color: {{ $colors[$idx] }}">{{ $idx }}</span>
                        <div class="flex-1 bg-rose-50 rounded-full h-6 overflow-hidden">
                            <div class="h-6 rounded-full flex items-center justify-end px-2 text-[11px] font-semibold text-white"
                                 style="width: {{ max(6, round($c/$maxDist*100)) }}%; background: linear-gradient(90deg, {{ $colors[$idx] }}bb, {{ $colors[$idx] }});">
                                {{ $c > 0 ? $c : '' }}
                            </div>
                        </div>
                        <span class="w-10 text-xs text-slate-400 text-right">{{ $overallCount ? round($c/$overallCount*100) : 0 }}%</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-rose-100 shadow-sm overflow-x-auto">
    <div class="px-5 py-4 border-b border-rose-100"><h2 class="font-semibold text-slate-800">Rekapitulasi Nilai Akhir per Kelas</h2></div>
    <table class="min-w-full text-sm">
        <thead class="bg-rose-50/60 text-brand-dark/70 text-left">
            <tr>
                <th class="px-5 py-3 font-semibold">Kelas</th>
                @foreach($indices as $idx)<th class="px-3 py-3 font-semibold text-center">{{ $idx }}</th>@endforeach
                <th class="px-4 py-3 font-semibold text-center">Jumlah</th>
                <th class="px-4 py-3 font-semibold text-right">Rata-rata</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-rose-50">
            @foreach($recap as $class => $row)
                <tr class="hover:bg-rose-50/40">
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $class }}</td>
                    @foreach($indices as $idx)<td class="px-3 py-3 text-center {{ ($row[$idx]??0) ? 'text-slate-800 font-medium' : 'text-slate-300' }}">{{ $row[$idx] ?? 0 }}</td>@endforeach
                    <td class="px-4 py-3 text-center font-semibold">{{ $row['total'] }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-brand">{{ $row['total'] ? number_format($row['sum']/$row['total'],2) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-rose-50/60">
            <tr>
                <td class="px-5 py-3 font-bold text-brand-dark">TOTAL</td>
                @foreach($indices as $idx)<td class="px-3 py-3 text-center font-semibold">{{ $distribution[$idx] ?? 0 }}</td>@endforeach
                <td class="px-4 py-3 text-center font-bold">{{ $overallCount }}</td>
                <td class="px-4 py-3 text-right font-bold text-brand">{{ number_format($overallAvg,2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- ================= Rekap Indikasi AI per Kelas ================= --}}
<div class="mt-6">
    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <h2 class="font-semibold text-slate-800">🤖 Rekap Indikasi AI per Kelas</h2>
        <span class="text-xs text-slate-400">Estimasi indikatif dari logbook yang sudah diperiksa · ambang "tinggi" ≥ 60%</span>
    </div>

    @if($aiCheckedTotal === 0)
        <div class="rounded-xl border border-slate-200 bg-white p-5 text-slate-400 text-sm">Belum ada logbook yang diperiksa AI pada periode ini.</div>
    @else
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <x-stat-card label="Logbook Diperiksa" :value="$aiCheckedTotal" icon="🔍" color="brand" />
            <x-stat-card label="Rata-rata Indikasi AI" :value="number_format($aiAvgTotal,1).'%'" icon="🤖" color="pink" :progress="$aiAvgTotal" />
            <x-stat-card label="Indikasi Tinggi (≥60%)" :value="$aiHighTotal" icon="⚠️" color="amber" />
            <x-stat-card label="Indikasi Rendah (<60%)" :value="$aiCheckedTotal - $aiHighTotal" icon="✅" color="green" />
        </div>

        <div class="bg-white rounded-2xl border border-rose-100 shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-rose-50/60 text-brand-dark/70 text-left">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Kelas</th>
                        <th class="px-4 py-3 font-semibold text-center">Logbook Diperiksa</th>
                        <th class="px-4 py-3 font-semibold text-right">Rata-rata AI</th>
                        <th class="px-4 py-3 font-semibold text-center">Indikasi Tinggi (≥60%)</th>
                        <th class="px-4 py-3 font-semibold text-left">Sebaran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rose-50">
                    @foreach($aiRecap as $class => $row)
                        @php $avg = $row['checked'] ? $row['sum']/$row['checked'] : 0; $band = $avg>=60?'#be123c':($avg>=30?'#b45309':'#15803d'); @endphp
                        <tr class="hover:bg-rose-50/40">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $class }}</td>
                            <td class="px-4 py-3 text-center">{{ $row['checked'] }}</td>
                            <td class="px-4 py-3 text-right font-bold" style="color: {{ $band }}">{{ number_format($avg,1) }}%</td>
                            <td class="px-4 py-3 text-center {{ $row['high'] ? 'font-semibold text-red-600' : 'text-slate-400' }}">{{ $row['high'] }}</td>
                            <td class="px-4 py-3">
                                <div class="w-40 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-2.5 rounded-full" style="width: {{ max(3, round($avg)) }}%; background: {{ $band }}"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-rose-50/60">
                    <tr>
                        <td class="px-5 py-3 font-bold text-brand-dark">TOTAL</td>
                        <td class="px-4 py-3 text-center font-bold">{{ $aiCheckedTotal }}</td>
                        <td class="px-4 py-3 text-right font-bold text-brand">{{ number_format($aiAvgTotal,1) }}%</td>
                        <td class="px-4 py-3 text-center font-bold">{{ $aiHighTotal }}</td>
                        <td class="px-4 py-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="mt-2 text-xs text-slate-400">⚠️ Angka indikasi AI bersifat estimasi/heuristik (bukan vonis), dihitung dari logbook yang telah diperiksa koordinator.</p>
    @endif
</div>
@endif
@endsection
