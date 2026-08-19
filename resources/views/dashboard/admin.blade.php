@extends('layouts.app')
@section('title', 'Dashboard Koordinator')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Dashboard Koordinator</h1>
    <p class="text-slate-500">Ringkasan sistem SIM-CAPSTONE {{ $ay?->label ?? '(belum ada tahun ajaran aktif)' }}.</p>
</div>

@if(! $ay)
    <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 text-amber-800">
        Belum ada Tahun Ajaran aktif.
        <a href="{{ route('admin.academic-years.index') }}" class="font-semibold underline">Buat & aktifkan tahun ajaran</a> dulu.
    </div>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <x-stat-card label="Mahasiswa" :value="$stats['mahasiswa']" icon="🎓" color="blue" />
    <x-stat-card label="Tim Capstone" :value="$stats['teams']" icon="👥" color="green" />
    <x-stat-card label="Topik Pending" :value="$stats['topic_pending']" icon="📝" color="amber" />
    <x-stat-card label="Logbook Perlu Review" :value="$stats['logbook_pending']" icon="⏳" color="indigo" />
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-rose-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">Tim Terbaru</h2>
            <a href="{{ route('admin.teams.index') }}" class="text-sm text-brand hover:underline">Lihat semua →</a>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr><th class="px-5 py-3 font-medium">Tim</th><th class="px-5 py-3 font-medium">Ketua</th><th class="px-5 py-3 font-medium">Anggota</th><th class="px-5 py-3 font-medium">Status Topik</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($recentTeams as $team)
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $team->team_name }}</td>
                        <td class="px-5 py-3">{{ $team->leader->name }}</td>
                        <td class="px-5 py-3">{{ $team->members->count() }} org</td>
                        <td class="px-5 py-3"><x-status-badge :status="$team->topic_status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">Belum ada tim.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
        <h2 class="font-semibold text-slate-800 mb-3">Aksi Cepat</h2>
        <div class="space-y-2 text-sm">
            <a href="{{ route('admin.modules.index') }}" class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">🗂️ Kelola Modul</a>
            <a href="{{ route('admin.logbook-review.index') }}" class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">📖 Review Logbook</a>
            <a href="{{ route('admin.scores.index') }}" class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">✍️ Input Penilaian</a>
            <a href="{{ route('admin.grades.index') }}" class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">📊 Rekap Nilai</a>
            <a href="{{ route('admin.reports.index') }}" class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">📈 Summary Report</a>
        </div>
    </div>
</div>
@endif
@endsection
