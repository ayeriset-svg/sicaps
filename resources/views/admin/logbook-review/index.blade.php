@extends('layouts.app')
@section('title', 'Review Logbook')

@section('content')
<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-bold text-brand-dark">Review Logbook</h1>
        <p class="text-slate-500">Antrian logbook seluruh tim.</p>
    </div>
    <form method="GET">
        <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 border px-3 py-2 text-sm">
            <option value="">Semua Status</option>
            @foreach(['Pending'=>'Menunggu Review','Revision Needed'=>'Perlu Revisi','Approved'=>'Disetujui'] as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>@endforeach
        </select>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr><th class="px-5 py-3 font-medium">Tim</th><th class="px-5 py-3 font-medium">Kelas</th><th class="px-5 py-3 font-medium">Modul</th><th class="px-5 py-3 font-medium">Submit</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($logbooks as $lb)
                <tr>
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $lb->team->team_name }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ $lb->team->class_name ?? '—' }}</td>
                    <td class="px-5 py-3">{{ $lb->module->code }} — {{ \Illuminate\Support\Str::limit($lb->module->title,30) }}</td>
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
