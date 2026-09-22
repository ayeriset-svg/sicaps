@extends('layouts.app')
@section('title', 'Audit Log')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Audit Log</h1>
    <p class="text-slate-500">Pemantauan aktivitas pengguna (login & perubahan data).</p>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2 text-sm">
    <input name="q" value="{{ request('q') }}" placeholder="Cari aktivitas..." class="rounded-lg border-slate-300 border px-3 py-2 flex-1 max-w-xs">
    <select name="user" onchange="this.form.submit()" class="rounded-lg border-slate-300 border px-3 py-2">
        <option value="">Semua User</option>
        @foreach($users as $u)<option value="{{ $u->id }}" @selected((int)request('user')===$u->id)>{{ $u->name }} ({{ $u->role }})</option>@endforeach
    </select>
    <select name="action" onchange="this.form.submit()" class="rounded-lg border-slate-300 border px-3 py-2">
        <option value="">Semua Aksi</option>
        @foreach($actions as $a)<option value="{{ $a }}" @selected(request('action')===$a)>{{ strtoupper($a) }}</option>@endforeach
    </select>
    <button class="rounded-lg bg-brand text-white px-4 py-2 hover:bg-brand-dark">Filter</button>
    @if(request()->hasAny(['q','user','action']))<a href="{{ route('admin.activity-logs.index') }}" class="px-2 py-2 text-brand hover:underline">Reset</a>@endif
</form>

<div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr><th class="px-4 py-3 font-medium">Waktu</th><th class="px-4 py-3 font-medium">User</th><th class="px-4 py-3 font-medium">Aksi</th><th class="px-4 py-3 font-medium">Aktivitas</th><th class="px-4 py-3 font-medium">IP</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($logs as $log)
                @php $ac = ['login'=>'bg-emerald-100 text-emerald-700','logout'=>'bg-slate-100 text-slate-500','delete'=>'bg-red-100 text-red-700','post'=>'bg-sky-100 text-sky-700','put'=>'bg-amber-100 text-amber-700','patch'=>'bg-amber-100 text-amber-700'][$log->action] ?? 'bg-slate-100 text-slate-600'; @endphp
                <tr>
                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="px-4 py-3 font-medium text-slate-800">{{ $log->user->name ?? '—' }}<span class="block text-xs text-slate-400">{{ $log->user->role ?? '' }}</span></td>
                    <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $ac }}">{{ strtoupper($log->action) }}</span></td>
                    <td class="px-4 py-3 text-slate-600">{{ $log->description }}<span class="block text-[11px] text-slate-400">{{ $log->method }} · {{ \Illuminate\Support\Str::limit($log->url, 60) }}</span></td>
                    <td class="px-4 py-3 text-slate-400 text-xs">{{ $log->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada aktivitas tercatat.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
