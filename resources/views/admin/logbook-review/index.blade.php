@extends('layouts.app')
@section('title', 'Review Logbook')

@section('content')
<div class="mb-4">
    <h1 class="text-2xl font-bold text-brand-dark">Review Logbook</h1>
    <p class="text-slate-500">Antrian logbook & tugas seluruh tim · {{ $ay->label }}.</p>
</div>

{{-- Tabs --}}
<div class="flex gap-2 mb-4 border-b border-rose-100">
    @php $tabBase = array_filter(['status'=>request('status'),'module'=>request('module'),'class'=>request('class')]); @endphp
    <a href="{{ route('admin.logbook-review.index', $tabBase + ['tab'=>'pending']) }}"
       class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab==='pending' ? 'border-brand text-brand' : 'border-transparent text-slate-500 hover:text-brand' }}">
        Perlu Review <span class="ml-1 rounded-full bg-amber-100 text-amber-700 px-1.5 text-xs">{{ $pendingCount }}</span>
    </a>
    <a href="{{ route('admin.logbook-review.index', $tabBase + ['tab'=>'reviewed']) }}"
       class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab==='reviewed' ? 'border-brand text-brand' : 'border-transparent text-slate-500 hover:text-brand' }}">
        Sudah Direview <span class="ml-1 rounded-full bg-emerald-100 text-emerald-700 px-1.5 text-xs">{{ $reviewedCount }}</span>
    </a>
</div>

{{-- Filters --}}
<form method="GET" class="mb-4 flex flex-wrap gap-2 text-sm">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <select name="module" onchange="this.form.submit()" class="rounded-lg border-slate-300 border px-3 py-2">
        <option value="">Semua Modul</option>
        @foreach($modules as $m)<option value="{{ $m->id }}" @selected((int)request('module')===$m->id)>{{ $m->code }} — {{ \Illuminate\Support\Str::limit($m->title,28) }}</option>@endforeach
    </select>
    <select name="class" onchange="this.form.submit()" class="rounded-lg border-slate-300 border px-3 py-2">
        <option value="">Semua Kelas</option>
        @foreach($classes as $c)<option value="{{ $c }}" @selected(request('class')===$c)>{{ $c }}</option>@endforeach
    </select>
    <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 border px-3 py-2">
        <option value="">Semua Status</option>
        @foreach(['Pending'=>'Menunggu Review','Revision Needed'=>'Perlu Revisi','Approved'=>'Disetujui','Rejected'=>'Ditolak'] as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>@endforeach
    </select>
    @if(request()->hasAny(['module','class','status']))<a href="{{ route('admin.logbook-review.index', ['tab'=>$tab]) }}" class="px-2 py-2 text-brand hover:underline">Reset</a>@endif
</form>

<div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr><th class="px-5 py-3 font-medium">Tim</th><th class="px-5 py-3 font-medium">Kelas</th><th class="px-5 py-3 font-medium">Modul</th><th class="px-5 py-3 font-medium">Submit</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($logbooks as $lb)
                <tr>
                    <td class="px-5 py-3 font-medium text-slate-800">
                        {{ $lb->team->team_name }}
                        @if($lb->user_id)<span class="block text-xs font-normal text-indigo-600">👤 {{ $lb->user->name ?? '—' }}</span>@endif
                    </td>
                    <td class="px-5 py-3 text-slate-500">{{ $lb->team->class_name ?? '—' }}</td>
                    <td class="px-5 py-3">
                        {{ $lb->module->code }} — {{ \Illuminate\Support\Str::limit($lb->module->title,30) }}
                        @if($lb->module->isIndividual())<span class="ml-1 rounded-full bg-indigo-100 text-indigo-700 px-1.5 py-0.5 text-xs">Tugas</span>@endif
                    </td>
                    <td class="px-5 py-3 text-slate-500">{{ $lb->submitted_at?->format('d/m H:i') ?? '—' }}</td>
                    <td class="px-5 py-3"><x-status-badge :status="$lb->status_approval" /></td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            <a href="{{ route('admin.logbook-review.print', $lb) }}" target="_blank" title="Generate PDF" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="printer" /></a>
                            <a href="{{ route('admin.logbook-review.show', $lb) }}" title="Review" class="p-1.5 rounded-lg text-brand hover:bg-rose-50"><x-icon name="eye" /></a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Tidak ada logbook.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
