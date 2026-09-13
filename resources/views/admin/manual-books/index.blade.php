@extends('layouts.app')
@section('title', 'Kelola Manual Book')

@section('content')
<div class="mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-bold text-brand-dark">Kelola Manual Book</h1>
        <p class="text-slate-500">Panduan penggunaan sistem yang tampil ke seluruh pengguna.</p>
    </div>
    <a href="{{ route('admin.manual-books.create') }}" class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark">+ Tambah Manual Book</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr><th class="px-5 py-3 font-medium">#</th><th class="px-5 py-3 font-medium">Judul</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium">Diperbarui</th><th class="px-5 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($books as $b)
                <tr>
                    <td class="px-5 py-3 text-slate-400">{{ $b->order_index }}</td>
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $b->title }}
                        @if($b->file_path)<span class="ml-1 text-xs text-slate-400" title="{{ $b->file_name }}">📎</span>@endif
                    </td>
                    <td class="px-5 py-3">
                        @if($b->is_published)
                            <span class="rounded-full bg-emerald-100 text-emerald-700 px-2 py-0.5 text-xs font-medium">Terbit</span>
                        @else
                            <span class="rounded-full bg-slate-100 text-slate-500 px-2 py-0.5 text-xs font-medium">Draf</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-slate-500">{{ $b->updated_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            <a href="{{ route('manual.show', $b) }}" target="_blank" title="Lihat" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="eye" /></a>
                            <a href="{{ route('admin.manual-books.edit', $b) }}" title="Edit" class="p-1.5 rounded-lg text-brand hover:bg-rose-50"><x-icon name="edit" /></a>
                            <form method="POST" action="{{ route('admin.manual-books.destroy', $b) }}" class="inline" onsubmit="return confirm('Hapus manual book ini?')">@csrf @method('DELETE')<button title="Hapus" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50"><x-icon name="trash" /></button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">Belum ada manual book. Klik "Tambah Manual Book".</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
