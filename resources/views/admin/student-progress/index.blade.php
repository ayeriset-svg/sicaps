@extends('layouts.app')
@section('title', 'Progres Mahasiswa')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Progres Mahasiswa</h1>
    <p class="text-slate-500">{{ $ay->label }} · pantau modul/tugas mana saja yang sudah dikerjakan tiap mahasiswa.</p>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2 text-sm">
    <select name="class" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
        <option value="">Semua Kelas</option>
        @foreach($classes as $c)<option value="{{ $c }}" @selected(request('class')==$c)>{{ $c }}</option>@endforeach
    </select>
    <select name="team" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
        <option value="">Semua Tim</option>
        @foreach($allTeams as $t)<option value="{{ $t->id }}" @selected(request('team')==$t->id)>{{ $t->team_name }}</option>@endforeach
    </select>
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / NIM..." class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
    <button class="rounded-lg bg-brand text-white px-4 py-2 hover:bg-brand-dark">Cari</button>
    @if(request('class') || request('team') || request('q'))<a href="{{ route('admin.student-progress.index') }}" class="px-2 py-2 text-brand hover:underline">Reset</a>@endif
</form>

<p class="text-xs text-slate-400 mb-3">Dihitung dari <span class="font-medium">{{ $modules->count() }}</span> modul/tugas yang membutuhkan pengerjaan (modul tersembunyi & materi-saja dikecualikan). Tugas individu memakai submission masing-masing mahasiswa; logbook tim memakai status timnya. Klik baris untuk melihat rincian per modul.</p>

<div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">Mahasiswa</th>
                <th class="px-4 py-3 font-medium">Kelas</th>
                <th class="px-4 py-3 font-medium">Tim</th>
                <th class="px-4 py-3 font-medium w-56">Progres (Selesai / Total)</th>
                <th class="px-4 py-3 w-8"></th>
            </tr>
        </thead>
        @forelse($rows as $row)
            @php $pct = $row['total'] ? round($row['done'] / $row['total'] * 100) : 0; @endphp
            <tbody x-data="{ open:false }" class="border-t border-slate-100">
                <tr @click="open=!open" class="cursor-pointer hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-800">{{ $row['student']->name }}<span class="block text-xs font-normal text-slate-400">{{ $row['student']->identity_number }}</span></td>
                    <td class="px-4 py-3 text-slate-500">{{ $row['student']->class_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $row['team']->team_name }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $pct >= 100 ? 'bg-emerald-500' : ($pct > 0 ? 'bg-amber-400' : 'bg-slate-300') }}" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-slate-600 whitespace-nowrap">{{ $row['done'] }}/{{ $row['total'] }}</span>
                        </div>
                        <span class="text-[11px] text-slate-400">{{ $row['submitted'] }} dikerjakan · {{ $row['done'] }} disetujui</span>
                    </td>
                    <td class="px-4 py-3 text-slate-400"><svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg></td>
                </tr>
                <tr x-show="open" x-cloak>
                    <td colspan="5" class="px-4 py-4 bg-slate-50">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($row['items'] as $it)
                                <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <div class="min-w-0">
                                        <p class="text-sm text-slate-700 truncate">{{ $it['module']->code ? $it['module']->code.' · ' : '' }}{{ $it['module']->title }}</p>
                                        <span class="text-[11px] {{ $it['module']->isIndividual() ? 'text-orange-600' : 'text-slate-400' }}">{{ $it['module']->isIndividual() ? 'Tugas Individu' : 'Logbook Tim' }}</span>
                                    </div>
                                    <x-status-badge :status="$it['status']" />
                                </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
            </tbody>
        @empty
            <tbody><tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Tidak ada mahasiswa (yang tergabung dalam tim) sesuai filter.</td></tr></tbody>
        @endforelse
    </table>
</div>
@endsection
