@extends('layouts.app')
@section('title', 'Dashboard Mahasiswa')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Halo, {{ explode(' ', $user->name)[0] }} 👋</h1>
    <p class="text-slate-500">{{ $ay?->label ?? 'Tidak ada tahun ajaran aktif' }}</p>
</div>

@if(! $team)
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-6">
        <h2 class="font-semibold text-brand-dark mb-1">Anda belum tergabung dalam tim</h2>
        <p class="text-brand-dark text-sm mb-3">Jika Anda ketua, buat tim baru pada Modul 0. Jika anggota, minta ketua menambahkan Anda.</p>
        <a href="{{ route('team.index') }}" class="inline-block rounded-lg bg-brand text-white px-4 py-2 text-sm font-medium hover:bg-brand-dark">Kelola Tim →</a>
    </div>
@else
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-slate-800">Progres Modul</h2>
                <span class="text-sm text-slate-500">{{ $progress }}% disetujui</span>
            </div>
            <div class="h-3 rounded-full bg-slate-200 overflow-hidden mb-6"><div class="h-full bg-gradient-to-r from-brand to-pink-500" style="width: {{ $progress }}%"></div></div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach($modules as $mod)
                    @php $lb = $logbooks[$mod->id] ?? null; $st = $lb?->status_approval ?? 'Not Started'; @endphp
                    @if($mod->type === 'assessment')
                        <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-center">
                            <span class="block text-xs text-rose-400">{{ $mod->week_label }}</span>
                            <span class="block font-semibold text-brand text-sm">{{ $mod->code }}</span>
                        </div>
                    @else
                        <a href="{{ route('logbook.show', $mod) }}" class="block rounded-lg border p-3 text-center hover:shadow transition
                            {{ $st==='Approved' ? 'border-green-300 bg-green-50' : ($st==='Revision Needed' ? 'border-orange-300 bg-orange-50' : ($st==='Pending' ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white')) }}">
                            <span class="block text-xs text-slate-400">{{ $mod->week_label }}</span>
                            <span class="block font-semibold text-slate-700 text-sm">{{ $mod->code }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <h2 class="font-semibold text-slate-800 mb-3">Tim Saya</h2>
            <p class="text-lg font-bold text-slate-800">{{ $team->team_name }}</p>
            <p class="text-sm text-slate-500 mb-3">{{ $team->case_type_label ?? 'Ranah usaha belum dipilih' }}</p>
            <dl class="text-sm space-y-1.5">
                <div class="flex justify-between"><dt class="text-slate-500">Ketua</dt><dd class="font-medium">{{ $team->leader->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Anggota</dt><dd class="font-medium">{{ $team->members->count() }} orang</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Topik</dt><dd><x-status-badge :status="$team->topic_status" /></dd></div>
            </dl>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <h2 class="font-semibold text-slate-800 mb-2">Aksi Cepat</h2>
            <div class="space-y-2 text-sm">
                <a href="{{ route('topic.index') }}" class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">📝 Topik & Mitra</a>
                <a href="{{ route('peer.index') }}" class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">🤝 Peer 180°</a>
                <a href="{{ route('grade.me') }}" class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">📊 Nilai Saya</a>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
