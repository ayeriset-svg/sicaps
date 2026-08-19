@extends('layouts.app')
@section('title', 'Kelola Modul')

@section('content')
<div class="mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-bold text-brand-dark">Kelola Modul & Pertemuan</h1>
        <p class="text-slate-500">{{ $ay->label }} · atur urutan, materi, dan field logbook tiap modul.</p>
    </div>
    <a href="{{ route('admin.modules.create') }}" class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark">+ Tambah Modul</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr><th class="px-4 py-3 font-medium">#</th><th class="px-4 py-3 font-medium">Minggu</th><th class="px-4 py-3 font-medium">Kode</th><th class="px-4 py-3 font-medium">Judul</th><th class="px-4 py-3 font-medium">Tipe</th><th class="px-4 py-3 font-medium">Field</th><th class="px-4 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($modules as $m)
                <tr>
                    <td class="px-4 py-3 text-slate-400">{{ $m->order_index }}</td>
                    <td class="px-4 py-3 font-medium">{{ $m->week_label }}</td>
                    <td class="px-4 py-3">{{ $m->code }}</td>
                    <td class="px-4 py-3 text-slate-800">
                        {{ $m->title }}
                        <span class="block mt-1"><x-ai-level :level="$m->ai_policy_level" /></span>
                    </td>
                    <td class="px-4 py-3">
                        @php $tc = ['module'=>'bg-rose-100 text-brand','assessment'=>'bg-rose-100 text-brand','other'=>'bg-slate-100 text-slate-600'][$m->type]; @endphp
                        <span class="rounded-full px-2 py-0.5 text-xs {{ $tc }}">{{ $m->type }}{{ $m->assessment_stage ? ' · '.$m->assessment_stage : '' }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-500">{{ $m->isLogbook() ? count($m->fields()).' field' : '—' }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            <a href="{{ route('admin.modules.edit', $m) }}" title="Edit" class="p-1.5 rounded-lg text-brand hover:bg-rose-50"><x-icon name="edit" /></a>
                            <form method="POST" action="{{ route('admin.modules.destroy', $m) }}" class="inline" onsubmit="return confirm('Hapus modul?')">@csrf @method('DELETE')<button title="Hapus" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50"><x-icon name="trash" /></button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Belum ada modul.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
