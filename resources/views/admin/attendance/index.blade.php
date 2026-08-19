@extends('layouts.app')
@section('title', 'Presensi Mahasiswa')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Presensi Mahasiswa</h1>
    <p class="text-slate-500">{{ $ay->label }} · {{ $totalWeeks }} minggu × {{ $sessions }} sesi. Default kosong — klik sel untuk mengisi tiap pertemuan.</p>
</div>

<div class="mb-4 flex flex-wrap items-center gap-3 text-xs text-slate-500">
    <form method="GET" class="flex gap-2">
        <select name="class" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-1.5 text-sm bg-white">
            <option value="">Semua Kelas</option>
            @foreach($classes as $c)<option value="{{ $c }}" @selected(request('class')==$c)>{{ $c }}</option>@endforeach
        </select>
        <select name="team" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-1.5 text-sm bg-white">
            <option value="">Semua Kelompok</option>
            @foreach($allTeams as $t)<option value="{{ $t->id }}" @selected(request('team')==$t->id)>{{ $t->team_name }}</option>@endforeach
        </select>
        @if(request('class') || request('team'))<a href="{{ route('admin.attendance.index') }}" class="px-2 py-1.5 text-brand hover:underline">Reset</a>@endif
    </form>
    <span class="ml-auto flex gap-2">
        <span class="rounded bg-slate-100 text-slate-400 px-2 py-0.5">· Kosong</span>
        <span class="rounded bg-emerald-100 text-emerald-700 px-2 py-0.5">H Hadir</span>
        <span class="rounded bg-pink-100 text-pink-700 px-2 py-0.5">I Izin</span>
        <span class="rounded bg-amber-100 text-amber-700 px-2 py-0.5">S Sakit</span>
        <span class="rounded bg-red-100 text-red-700 px-2 py-0.5">A Alpa</span>
    </span>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
    <table class="text-xs border-collapse">
        <thead class="bg-rose-50/60 text-brand-dark/70">
            <tr>
                <th class="px-3 py-2 text-left font-semibold sticky left-0 bg-rose-50 z-10">Mahasiswa</th>
                @for($w = 1; $w <= $totalWeeks; $w++)
                    @for($s = 1; $s <= $sessions; $s++)
                        <th class="px-1 py-2 font-medium text-center w-8">{{ $w }}.{{ $s }}</th>
                    @endfor
                @endfor
                <th class="px-2 py-2 font-semibold text-center">Alpa</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-rose-50">
            @php
                $styles = [
                    '_null'   => 'bg-slate-50 text-slate-300',
                    'present' => 'bg-emerald-100 text-emerald-700',
                    'permit'  => 'bg-pink-100 text-pink-700',
                    'sick'    => 'bg-amber-100 text-amber-700',
                    'absent'  => 'bg-red-100 text-red-700',
                ];
                $labels = ['_null' => '·', 'present' => 'H', 'permit' => 'I', 'sick' => 'S', 'absent' => 'A'];
                // siklus: kosong → H → I → S → A → (kosongkan)
                $next   = ['_null' => 'present', 'present' => 'permit', 'permit' => 'sick', 'sick' => 'absent', 'absent' => 'clear'];
            @endphp
            @forelse($students as $stu)
                <tr>
                    <td class="px-3 py-2 font-medium text-slate-700 sticky left-0 bg-white z-10 whitespace-nowrap">{{ $stu->name }}<span class="block text-[10px] text-slate-400">{{ $stu->class_name }}</span></td>
                    @for($w = 1; $w <= $totalWeeks; $w++)
                        @for($s = 1; $s <= $sessions; $s++)
                            @php $st = $matrix[$stu->id][$w.'-'.$s] ?? '_null'; @endphp
                            <td class="px-0.5 py-1 text-center">
                                <form method="POST" action="{{ route('admin.attendance.store') }}">
                                    @csrf
                                    <input type="hidden" name="student_id" value="{{ $stu->id }}">
                                    <input type="hidden" name="week_number" value="{{ $w }}">
                                    <input type="hidden" name="session_number" value="{{ $s }}">
                                    <input type="hidden" name="status" value="{{ $next[$st] }}">
                                    <button title="{{ $st === '_null' ? 'kosong' : $st }} → klik" class="h-6 w-6 rounded {{ $styles[$st] }} font-bold hover:ring-2 hover:ring-rose-300">{{ $labels[$st] }}</button>
                                </form>
                            </td>
                        @endfor
                    @endfor
                    <td class="px-2 py-2 text-center font-bold {{ ($absentDays[$stu->id] ?? 0) > 10 ? 'text-red-700' : (($absentDays[$stu->id] ?? 0) >= 4 ? 'text-amber-600' : 'text-slate-600') }}">{{ $absentDays[$stu->id] ?? 0 }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $totalWeeks*$sessions + 2 }}" class="px-4 py-8 text-center text-slate-400">Belum ada mahasiswa.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<p class="mt-3 text-xs text-slate-400">Siklus klik: kosong → Hadir → Izin → Sakit → Alpa → kosong. Jalankan rekalkulasi di Rekap Nilai agar penalti terterap.</p>
@endsection
