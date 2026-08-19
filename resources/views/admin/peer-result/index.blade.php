@extends('layouts.app')
@section('title', 'Hasil Peer 180°')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Hasil Peer Evaluation 180°</h1>
    <p class="text-slate-500">Rerata nilai peer per mahasiswa untuk tiap tahap.</p>
</div>

<form method="GET" class="mb-5 flex flex-wrap gap-2 text-sm">
    <select name="stage" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
        @foreach($stages as $s)<option value="{{ $s->id }}" @selected($stage && $stage->id===$s->id)>{{ $s->code }} — {{ $s->name }} {{ $s->peer_open ? '(dibuka)' : '(tertutup)' }}</option>@endforeach
    </select>
    <select name="class" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
        <option value="">Semua Kelas</option>
        @foreach($classes as $c)<option value="{{ $c }}" @selected(request('class')==$c)>{{ $c }}</option>@endforeach
    </select>
    <select name="team" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
        <option value="">Semua Kelompok</option>
        @foreach($allTeams as $t)<option value="{{ $t->id }}" @selected(request('team')==$t->id)>{{ $t->team_name }}</option>@endforeach
    </select>
    @if(request('class') || request('team'))<a href="{{ route('admin.peer-result.index', ['stage' => $stage?->id]) }}" class="px-2 py-2 text-brand hover:underline">Reset</a>@endif
</form>

@if(! $stage)
    <p class="text-slate-400">Belum ada stage.</p>
@else
    <div class="space-y-6">
        @foreach($teams as $team)
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100">
                <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-semibold text-slate-800">{{ $team->team_name }}</h2></div>
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-left">
                        <tr><th class="px-5 py-2 font-medium">Mahasiswa</th><th class="px-5 py-2 font-medium text-center">Penilai Masuk</th><th class="px-5 py-2 font-medium text-right">Rerata Peer</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($results[$team->id] ?? [] as $r)
                            <tr>
                                <td class="px-5 py-2">{{ $r['student']->name }}</td>
                                <td class="px-5 py-2 text-center text-slate-500">{{ $r['received'] }}</td>
                                <td class="px-5 py-2 text-right font-semibold">{{ $r['avg'] !== null ? number_format($r['avg'],2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
@endif
@endsection
