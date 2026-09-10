@extends('layouts.app')
@section('title', $book ? 'Edit Manual Book' : 'Tambah Manual Book')

@section('content')
@php $action = $book ? route('admin.manual-books.update', $book) : route('admin.manual-books.store'); @endphp

<a href="{{ route('admin.manual-books.index') }}" class="text-sm text-brand hover:underline">← Kelola Manual Book</a>
<div class="mt-2 mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">{{ $book ? 'Edit Manual Book' : 'Tambah Manual Book' }}</h1>
    <p class="text-slate-500">Isi panduan mendukung teks + gambar. Tampil ke seluruh pengguna bila berstatus "Terbit".</p>
</div>

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($book)@method('PUT')@endif

    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-3"><label class="block text-xs font-medium mb-1">Judul</label>
                <input name="title" value="{{ old('title', $book->title ?? '') }}" required class="w-full rounded-lg border-rose-200 border px-3 py-2 text-sm"></div>
            <div><label class="block text-xs font-medium mb-1">Urutan</label>
                <input type="number" min="0" name="order_index" value="{{ old('order_index', $book->order_index ?? 0) }}" class="w-full rounded-lg border-rose-200 border px-3 py-2 text-sm"></div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $book->is_published ?? true))>
            <span class="font-medium">Terbitkan</span> <span class="text-slate-400">— tampil ke mahasiswa & superadmin. Nonaktif = draf.</span>
        </label>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6">
        <label class="block text-sm font-semibold text-brand-dark mb-2">Isi Panduan</label>
        <x-richtext name="content" :value="old('content', $book->content ?? '')" :minHeight="520" />
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('admin.manual-books.index') }}" class="px-4 py-2 text-sm rounded-lg border border-slate-200">Batal</a>
        <button class="rounded-lg bg-brand text-white px-6 py-2 text-sm font-medium hover:bg-brand-dark">{{ $book ? 'Simpan Perubahan' : 'Tambah Manual Book' }}</button>
    </div>
</form>
@endsection
