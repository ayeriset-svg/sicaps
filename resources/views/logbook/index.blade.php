@extends('layouts.app')
@section('title', 'Digital Logbook')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Digital Logbook — Progress Tracker</h1>
    <p class="text-slate-500">Tim {{ $team->team_name }} · rencana 16 minggu.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($modules as $mod)
        @php $lb = $subs[$mod->id] ?? null; $st = $lb?->status_approval ?? 'Not Started'; @endphp
        @if($mod->type === 'assessment')
            <div class="block rounded-2xl shadow-sm border border-pink-200 bg-gradient-to-br from-pink-50 to-rose-50 p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-pink-500">{{ $mod->week_label }} · {{ $mod->code }}</span>
                    <span class="text-xs rounded-full bg-pink-200 text-pink-800 px-2 py-0.5 font-medium">Assessment</span>
                </div>
                <h3 class="font-semibold text-brand-dark leading-snug">{{ $mod->title }}</h3>
                <p class="text-sm text-brand mt-1">{{ \Illuminate\Support\Str::limit($mod->description, 80) }}</p>
            </div>
        @else
            <a href="{{ route('logbook.show', $mod) }}" class="block bg-white rounded-2xl shadow-sm border {{ $mod->isIndividual() ? 'border-indigo-100' : 'border-rose-100' }} p-5 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-2 gap-2">
                    <span class="text-xs font-medium text-slate-400">{{ $mod->week_label }} · {{ $mod->code }}</span>
                    <x-status-badge :status="$st" />
                </div>
                <h3 class="font-semibold text-slate-800 leading-snug">{{ $mod->title }}</h3>
                <p class="text-sm text-slate-500 mt-1">{{ \Illuminate\Support\Str::limit($mod->description, 80) }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    @if($mod->isIndividual())
                        <span class="text-xs rounded-full bg-indigo-100 text-indigo-700 px-2 py-0.5 font-medium">👤 Tugas Individu</span>
                    @else
                        <span class="text-xs rounded-full bg-slate-100 text-slate-500 px-2 py-0.5 font-medium">👥 Logbook Tim</span>
                    @endif
                    @if($mod->is_open)
                        <span class="text-xs rounded-full bg-emerald-100 text-emerald-700 px-2 py-0.5 font-medium">🔓 Dibuka</span>
                    @else
                        <span class="text-xs rounded-full bg-slate-100 text-slate-400 px-2 py-0.5 font-medium">🔒 Belum dibuka</span>
                    @endif
                </div>
            </a>
        @endif
    @endforeach
</div>
@endsection
