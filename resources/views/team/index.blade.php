@extends('layouts.app')
@section('title', 'Tim Saya — Modul 0')

@section('content')
@php $maxMembers = config('capstone.team_max_members'); @endphp
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Modul 0 — Pembentukan Tim & Role</h1>
    <p class="text-slate-500">Buat tim (maks {{ $maxMembers }} mahasiswa), tetapkan peran (boleh lebih dari satu), pilih ranah usaha.</p>
</div>

<datalist id="role-suggestions">
    @foreach(config('capstone.team_roles') as $r)<option value="{{ $r }}">@endforeach
</datalist>

@if(! $team)
    <div class="max-w-xl bg-white rounded-2xl shadow-sm border border-rose-100 p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Buat Tim Baru</h2>
        <form method="POST" action="{{ route('team.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Nama Tim</label>
                <input name="team_name" value="{{ old('team_name') }}" required class="w-full rounded-lg border-slate-300 border px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Ranah Studi Kasus</label>
                <select name="case_type" required class="w-full rounded-lg border-slate-300 border px-3 py-2">
                    @foreach(config('capstone.case_types') as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Peran Anda (Ketua)</label>
                <input name="leader_role" list="role-suggestions" required value="{{ old('leader_role') }}"
                       placeholder="cth: Project Manager, Backend Developer" class="w-full rounded-lg border-slate-300 border px-3 py-2">
                <p class="text-xs text-slate-400 mt-1">Boleh lebih dari satu peran (pisahkan dengan koma).</p>
            </div>
            <button class="rounded-lg bg-brand text-white px-4 py-2 font-medium hover:bg-brand-dark">Buat Tim & Jadi Ketua</button>
        </form>
    </div>
@else
    @php $isLeader = $user->id === $team->leader_id; @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-800">Anggota Tim ({{ $team->members->count() }}/{{ $maxMembers }})</h2>
                    @if($isLeader)<span class="text-xs text-slate-400">Anda ketua tim</span>@endif
                </div>
                <div class="space-y-2">
                    @foreach($team->members as $m)
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3">
                            <div>
                                <p class="font-medium text-slate-800">{{ $m->student->name }}
                                    @if($m->student_id === $team->leader_id)<span class="ml-1 text-xs bg-rose-100 text-brand rounded px-1.5 py-0.5">Ketua</span>@endif
                                </p>
                                <p class="text-sm text-slate-500">{{ $m->student->identity_number }} · {{ $m->role_label }}</p>
                            </div>
                            @if($isLeader && $m->student_id !== $team->leader_id)
                                <form method="POST" action="{{ route('team.members.remove', [$team, $m]) }}" onsubmit="return confirm('Hapus anggota ini?')">
                                    @csrf @method('DELETE')
                                    <button title="Hapus anggota" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50"><x-icon name="trash" /></button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($isLeader && $team->members->count() < $maxMembers)
                    <form method="POST" action="{{ route('team.members.add', $team) }}" class="mt-4 flex flex-col sm:flex-row gap-2">
                        @csrf
                        <select name="student_id" required class="flex-1 rounded-lg border-slate-300 border px-3 py-2 text-sm">
                            <option value="">— Pilih mahasiswa —</option>
                            @foreach($available as $s)<option value="{{ $s->id }}">{{ $s->identity_number }} — {{ $s->name }}</option>@endforeach
                        </select>
                        <input name="assigned_role" list="role-suggestions" required placeholder="Peran (boleh >1, pisah koma)" class="sm:w-64 rounded-lg border-slate-300 border px-3 py-2 text-sm">
                        <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark">Tambah</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6">
            <h2 class="font-semibold text-slate-800 mb-4">Info Tim</h2>
            @if($isLeader)
                <form method="POST" action="{{ route('team.update', $team) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm font-medium mb-1">Nama Tim</label>
                        <input name="team_name" value="{{ $team->team_name }}" class="w-full rounded-lg border-slate-300 border px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Ranah Usaha</label>
                        <select name="case_type" class="w-full rounded-lg border-slate-300 border px-3 py-2">
                            @foreach(config('capstone.case_types') as $k => $v)<option value="{{ $k }}" @selected($team->case_type === $k)>{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <button class="rounded-lg bg-slate-700 text-white px-4 py-2 text-sm hover:bg-slate-800">Simpan</button>
                </form>
            @else
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between"><dt class="text-slate-500">Nama</dt><dd class="font-medium">{{ $team->team_name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Ranah</dt><dd class="font-medium">{{ $team->case_type_label }}</dd></div>
                </dl>
            @endif
        </div>
    </div>
@endif
@endsection
