@extends('layouts.app')
@section('title', 'Manajemen Tim')

@section('content')
<div class="mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-bold text-brand-dark">Manajemen Tim</h1>
        <p class="text-slate-500">{{ $ay?->label ?? 'Tidak ada tahun ajaran aktif' }}</p>
    </div>
    <form method="GET" class="flex gap-2 text-sm">
        <select name="class" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
            <option value="">Semua Kelas</option>
            @foreach($classes as $c)<option value="{{ $c }}" @selected(request('class')==$c)>{{ $c }}</option>@endforeach
        </select>
        @if(request('class'))<a href="{{ route('admin.teams.index') }}" class="px-2 py-2 text-brand hover:underline">Reset</a>@endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-rose-50/60 text-brand-dark/70 text-left">
            <tr>
                <th class="px-5 py-3 font-semibold">Tim</th>
                <th class="px-5 py-3 font-semibold">Kelas</th>
                <th class="px-5 py-3 font-semibold">Ketua</th>
                <th class="px-5 py-3 font-semibold">Anggota</th>
                <th class="px-5 py-3 font-semibold">Ranah</th>
                <th class="px-5 py-3 font-semibold">Topik</th>
                <th class="px-5 py-3 font-semibold">Status</th>
                <th class="px-5 py-3 font-semibold text-center">HKI</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-rose-50">
            @forelse($teams as $team)
                <tr class="hover:bg-rose-50/40">
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $team->team_name }}</td>
                    <td class="px-5 py-3">{{ $team->class_name ?? '—' }}</td>
                    <td class="px-5 py-3">{{ $team->leader->name }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ $team->members->count() }} org<span class="text-xs text-slate-400 block">{{ $team->members->pluck('student.name')->implode(', ') }}</span></td>
                    <td class="px-5 py-3">{{ $team->case_type_label ?? '—' }}</td>
                    <td class="px-5 py-3">{{ $team->topic?->title ?? '—' }}</td>
                    <td class="px-5 py-3"><x-status-badge :status="$team->topic_status" /></td>
                    <td class="px-5 py-3 text-center">
                        <form method="POST" action="{{ route('admin.teams.hki', $team) }}">
                            @csrf
                            <button class="rounded-full px-3 py-1 text-xs font-semibold border transition {{ $team->hki_eligible ? 'bg-brand text-white border-brand' : 'bg-white text-slate-500 border-slate-200 hover:border-brand hover:text-brand' }}">
                                {{ $team->hki_eligible ? '⭐ Layak HKI' : 'Tandai HKI' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-5 py-8 text-center text-slate-400">Belum ada tim.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
