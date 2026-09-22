@extends('layouts.app')
@section('title', 'Digital Logbook')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Digital Logbook — Progress Tracker</h1>
    <p class="text-slate-500">Tim {{ $team->team_name }} · rencana 16 minggu.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($modules as $mod)
        @php $lb = $subs[$mod->id] ?? null; $st = $lb?->status_approval ?? 'Not Started'; $needsWork = $mod->requiresSubmission(); $isAsmt = $mod->type === 'assessment'; @endphp
        @if(! $needsWork)
            {{-- Materi saja (modul/assessment tanpa pengerjaan) --}}
            <a href="{{ route('logbook.show', $mod) }}" class="block rounded-2xl shadow-sm border p-5 hover:shadow-md transition {{ $isAsmt ? 'border-pink-400 bg-pink-200' : 'border-rose-100 bg-white' }}">
                <div class="flex items-center justify-between mb-2 gap-2">
                    <span class="text-xs font-medium {{ $isAsmt ? 'text-pink-700' : 'text-slate-400' }}">{{ $mod->week_label }} · {{ $mod->code }}</span>
                    @if($isAsmt)<span class="text-xs rounded-full bg-pink-300 text-pink-900 px-2 py-0.5 font-medium">Assessment</span>@else<span class="text-xs rounded-full bg-slate-100 text-slate-500 px-2 py-0.5 font-medium">📄 Materi saja</span>@endif
                </div>
                <h3 class="font-semibold {{ $isAsmt ? 'text-pink-900' : 'text-slate-800' }} leading-snug">{{ $mod->title }}</h3>
                <p class="text-sm {{ $isAsmt ? 'text-pink-800' : 'text-slate-500' }} mt-1">{{ \Illuminate\Support\Str::limit(trim(html_entity_decode(strip_tags($mod->description ?? ''))), 80) }}</p>
                <span class="block mt-3 text-sm text-brand">Lihat materi →</span>
            </a>
        @else
            {{-- Ada pengerjaan (logbook tim / tugas individu / assessment bersubmisi) --}}
            <a href="{{ route('logbook.show', $mod) }}" class="block rounded-2xl shadow-sm border p-5 hover:shadow-md transition {{ $isAsmt ? 'border-pink-400 bg-pink-200' : ($mod->isIndividual() ? 'border-orange-200 bg-orange-50' : 'border-rose-100 bg-white') }}">
                <div class="flex items-center justify-between mb-2 gap-2">
                    <span class="text-xs font-medium text-slate-400">{{ $mod->week_label }} · {{ $mod->code }}</span>
                    <x-status-badge :status="$st" />
                </div>
                <h3 class="font-semibold text-slate-800 leading-snug">{{ $mod->title }}</h3>
                <p class="text-sm text-slate-500 mt-1">{{ \Illuminate\Support\Str::limit(trim(html_entity_decode(strip_tags($mod->description ?? ''))), 80) }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    @if($isAsmt)
                        <span class="text-xs rounded-full bg-pink-300 text-pink-900 px-2 py-0.5 font-medium">Assessment</span>
                    @endif
                    @if($mod->isIndividual())
                        <span class="text-xs rounded-full bg-orange-100 text-orange-700 px-2 py-0.5 font-medium">👤 Tugas Individu</span>
                    @else
                        <span class="text-xs rounded-full bg-slate-100 text-slate-500 px-2 py-0.5 font-medium">👥 {{ $isAsmt ? 'Kelompok' : 'Logbook Tim' }}</span>
                    @endif
                    @php $stt = $mod->scheduleState(); $dl = $mod->daysToDeadline(); @endphp
                    @switch($stt)
                        @case('open')<span class="text-xs rounded-full bg-emerald-100 text-emerald-700 px-2 py-0.5 font-medium">🔓 Dibuka</span>@break
                        @case('scheduled')<span class="text-xs rounded-full bg-sky-100 text-sky-700 px-2 py-0.5 font-medium">🗓️ Terjadwal</span>@break
                        @case('ended')<span class="text-xs rounded-full bg-red-100 text-red-600 px-2 py-0.5 font-medium">⛔ Berakhir</span>@break
                        @default<span class="text-xs rounded-full bg-slate-100 text-slate-400 px-2 py-0.5 font-medium">🔒 Belum dibuka</span>
                    @endswitch
                    @if($stt==='open' && $dl!==null)
                        <span class="text-xs rounded-full px-2 py-0.5 font-medium {{ $dl<=3 ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-700' }}">⏰ {{ $dl<=0 ? 'hari ini' : $dl.' hari lagi' }}</span>
                    @endif
                </div>
            </a>
        @endif
    @endforeach
</div>
@endsection
