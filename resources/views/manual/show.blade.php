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
</div>
@endsection
