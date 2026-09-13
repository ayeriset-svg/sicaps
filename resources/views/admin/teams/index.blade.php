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
                                {{ $team->hki_eligible ? '⭐ Layak HKI' : 'Tandai HKI' }}
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
                            <div class="bg-white rounded-xl p-6 w-full max-w-md">
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
