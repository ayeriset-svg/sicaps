@extends('layouts.app')
@section('title', 'Manual Book')

@section('content')
<div class="mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-bold text-brand-dark">📘 Manual Book</h1>
        <p class="text-slate-500">Panduan penggunaan SIM-CAPSTONE.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if(auth()->user()->isSuperadmin())
            <a href="{{ route('manual.guide', 'superadmin') }}" target="_blank" class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark">📕 Panduan Superadmin (PDF)</a>
            <a href="{{ route('manual.guide', 'mahasiswa') }}" target="_blank" class="rounded-lg border border-rose-200 text-brand px-4 py-2 text-sm hover:bg-rose-50">📗 Panduan Mahasiswa (PDF)</a>
            <a href="{{ route('admin.manual-books.index') }}" class="rounded-lg border border-rose-200 text-brand px-4 py-2 text-sm hover:bg-rose-50">⚙️ Kelola</a>
        @else
            <a href="{{ route('manual.guide', 'mahasiswa') }}" target="_blank" class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark">📗 Panduan Mahasiswa (PDF)</a>
        @endif
    </div>
</div>

@forelse($books as $book)
    @if($loop->first)<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">@endif
        <a href="{{ route('manual.show', $book) }}" class="block bg-white rounded-2xl shadow-sm border border-rose-100 p-5 hover:shadow-md transition">
            <div class="text-2xl mb-2">📖</div>
            <h3 class="font-semibold text-slate-800 leading-snug">{{ $book->title }}</h3>
            <p class="text-sm text-slate-500 mt-1">{{ \Illuminate\Support\Str::limit(trim(strip_tags($book->content)), 90) ?: 'Buka untuk membaca panduan.' }}</p>
            <span class="inline-block mt-3 text-sm text-brand">Baca →</span>
        </a>
    @if($loop->last)</div>@endif
@empty
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-800">
        Belum ada manual book yang tersedia.
        @if(auth()->user()->isSuperadmin())
            <a href="{{ route('admin.manual-books.create') }}" class="font-semibold underline">Tambahkan sekarang →</a>
        @endif
    </div>
@endforelse
@endsection
