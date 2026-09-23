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

<datalist id="admin-role-suggestions">
    @foreach(config('capstone.team_roles') as $r)<option value="{{ $r }}">@endforeach
</datalist>

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
                <th class="px-5 py-3 font-semibold text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-rose-50">
            @forelse($teams as $team)
                <tr class="hover:bg-rose-50/40" x-data="{ edit:false }">
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
                                @if($team->hki_eligible)<x-icon name="star" class="ico" /> Layak HKI @else Tandai HKI @endif
                            </button>
                        </form>
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            <button @click="edit=true" title="Edit tim" class="p-1.5 rounded-lg text-brand hover:bg-rose-50"><x-icon name="edit" /></button>
                            <form method="POST" action="{{ route('admin.teams.destroy', $team) }}" class="inline" onsubmit="return confirm('Hapus tim {{ $team->team_name }}? SELURUH data terkait (logbook, tugas, nilai, presensi tim) ikut terhapus & tidak dapat dibatalkan.')">@csrf @method('DELETE')<button title="Hapus tim" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50"><x-icon name="trash" /></button></form>
                        </div>

                        {{-- Modal edit tim --}}
                        <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 text-left" @click.self="edit=false">
                            <div class="bg-white rounded-xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
                                <h3 class="font-semibold mb-4">Edit Tim — {{ $team->team_name }}</h3>
                                <form method="POST" action="{{ route('admin.teams.update', $team) }}" class="space-y-3">
                                    @csrf @method('PUT')
                                    <div><label class="block text-xs font-medium mb-1">Nama Tim</label>
                                        <input name="team_name" value="{{ $team->team_name }}" required class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                                    <div><label class="block text-xs font-medium mb-1">Ranah Studi Kasus</label>
                                        <select name="case_type" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm">
                                            <option value="">— Belum dipilih —</option>
                                            @foreach(config('capstone.case_types') as $k => $v)<option value="{{ $k }}" @selected($team->case_type===$k)>{{ $v }}</option>@endforeach
                                        </select></div>
                                    <div><label class="block text-xs font-medium mb-1">Ketua Tim</label>
                                        <select name="leader_id" required class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm">
                                            @foreach($team->members as $m)<option value="{{ $m->student_id }}" @selected($team->leader_id===$m->student_id)>{{ $m->student->name }} ({{ $m->student->identity_number }})</option>@endforeach
                                        </select>
                                        <p class="text-xs text-slate-400 mt-1">Hanya anggota tim yang dapat dijadikan ketua.</p></div>
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button type="button" @click="edit=false" class="px-4 py-2 text-sm">Batal</button>
                                        <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm">Simpan</button>
                                    </div>
                                </form>

                                {{-- Kelola anggota oleh koordinator (tetap bisa walau susunan tim terkunci untuk mahasiswa) --}}
                                <div class="mt-5 pt-4 border-t border-slate-100">
                                    <p class="text-xs font-semibold text-slate-600 mb-2">Anggota Tim ({{ $team->members->count() }}/{{ config('capstone.team_max_members') }})</p>
                                    <ul class="space-y-1 mb-3">
                                        @foreach($team->members as $m)
                                            <li class="flex items-center justify-between gap-2 text-sm">
                                                <span>{{ $m->student?->name }} <span class="text-xs text-slate-400">{{ $m->student?->identity_number }}</span>@if($m->student_id === $team->leader_id)<span class="ml-1 text-[11px] bg-rose-100 text-brand rounded px-1">Ketua</span>@endif</span>
                                                @if($m->student_id !== $team->leader_id)
                                                    <form method="POST" action="{{ route('admin.teams.members.remove', [$team, $m]) }}" onsubmit="return confirm('Keluarkan {{ $m->student?->name }} dari tim? Nilai akhirnya pada tim ini ikut dihapus.')">@csrf @method('DELETE')<button title="Keluarkan" class="p-1 rounded text-red-600 hover:bg-red-50"><x-icon name="trash" /></button></form>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                    @php $cands = $availableByClass->get($team->class_name, collect()); @endphp
                                    @if($team->members->count() < config('capstone.team_max_members'))
                                        @if($cands->isEmpty())
                                            <p class="text-xs text-slate-400">Tidak ada mahasiswa kelas {{ $team->class_name ?? '-' }} yang belum bertim.</p>
                                        @else
                                            <form method="POST" action="{{ route('admin.teams.members.add', $team) }}" class="space-y-2">
                                                @csrf
                                                <select name="student_id" required class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm">
                                                    <option value="">— Tambah mahasiswa kelas {{ $team->class_name }} —</option>
                                                    @foreach($cands as $s)<option value="{{ $s->id }}">{{ $s->identity_number }} — {{ $s->name }}</option>@endforeach
                                                </select>
                                                <div class="flex gap-2">
                                                    <input name="assigned_role" list="admin-role-suggestions" required placeholder="Peran" class="flex-1 rounded-lg border-slate-300 border px-3 py-2 text-sm">
                                                    <button class="rounded-lg border border-brand text-brand px-3 py-2 text-sm hover:bg-rose-50">Tambah</button>
                                                </div>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="px-5 py-8 text-center text-slate-400">Belum ada tim.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
