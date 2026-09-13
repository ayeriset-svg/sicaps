@extends('layouts.app')
@section('title', $book->title)

@section('content')
<a href="{{ route('manual.index') }}" class="text-sm text-brand hover:underline">← Semua Manual Book</a>

<div class="mt-2 mb-6 flex items-start justify-between flex-wrap gap-3">
    <h1 class="text-2xl font-bold text-brand-dark">{{ $book->title }}</h1>
    @unless($book->is_published)
        <span class="rounded-full bg-slate-100 text-slate-500 px-3 py-1 text-xs font-medium">Draf (belum terbit)</span>
    @endunless
</div>

<div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6 sm:p-8">
    @if(filled($book->content))
        <div class="rt-content max-w-none">{!! $book->content !!}</div>
    @else
        <p class="text-slate-400">Isi panduan belum diisi.</p>
    @endif

    @if($book->file_path)
        <div class="mt-6 pt-4 border-t border-rose-100">
            <a href="{{ route('file.show', $book->file_path) }}" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 text-brand px-4 py-2 text-sm hover:bg-rose-50">
                📎 Unduh lampiran: {{ $book->file_name ?: basename($book->file_path) }}
            </a>
        </div>
    @endif
</div>
@endsection
