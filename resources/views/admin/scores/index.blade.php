@extends('layouts.app')
@section('title', 'Input Penilaian')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Input Penilaian Assessment</h1>
    <p class="text-slate-500">Nilai rubrik kelompok (0–100). Nilai sama untuk seluruh anggota tim.</p>
</div>

<form method="GET" class="mb-5 flex flex-wrap gap-2 text-sm">
    <select name="stage" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
        @foreach($stages as $s)<option value="{{ $s->id }}" @selected($stage && $stage->id===$s->id)>{{ $s->code }} — {{ $s->name }}</option>@endforeach
    </select>
    <select name="class" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
        <option value="">Semua Kelas</option>
        @foreach($classes as $c)<option value="{{ $c }}" @selected(request('class')==$c)>{{ $c }}</option>@endforeach
    </select>
    <select name="team" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
        <option value="">Semua Kelompok</option>
        @foreach($allTeams as $t)<option value="{{ $t->id }}" @selected(request('team')==$t->id)>{{ $t->team_name }}</option>@endforeach
    </select>
    @if(request('class') || request('team'))<a href="{{ route('admin.scores.index', ['stage' => $stage?->id]) }}" class="px-2 py-2 text-brand hover:underline">Reset</a>@endif
</form>

@if(! $stage)
    <p class="text-slate-400">Belum ada stage penilaian.</p>
@else
    <div class="space-y-6">
        @forelse($teams as $team)
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-semibold text-slate-800">{{ $team->team_name }}</h2>
                    <p class="text-sm text-slate-500">{{ $team->members->pluck('student.name')->implode(', ') }}</p>
                </div>
                <form method="POST" action="{{ route('admin.scores.store') }}" class="p-5">
                    @csrf
                    <input type="hidden" name="stage_id" value="{{ $stage->id }}">
                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($stage->criteria as $c)
                            <div class="flex items-center gap-3">
                                <label class="flex-1 text-sm text-slate-600">{{ $c->name }}</label>
                                <input type="number" min="0" max="100" step="0.5" name="scores[{{ $c->id }}]"
                                       value="{{ $existing[$team->id][$c->id] ?? '' }}"
                                       class="w-24 rounded-lg border-slate-300 border px-3 py-1.5 text-sm">
                            </div>
                        @endforeach
                    </div>
                    <button class="mt-4 rounded-lg bg-brand text-white px-4 py-1.5 text-sm hover:bg-brand-dark">Simpan Nilai {{ $stage->code }}</button>
                </form>
            </div>
        @empty
            <p class="text-slate-400">Belum ada tim.</p>
        @endforelse
    </div>
@endif
@endsection
