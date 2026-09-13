@extends('layouts.app')
@section('title', $book ? 'Edit Manual Book' : 'Tambah Manual Book')

@section('content')
@php $action = $book ? route('admin.manual-books.update', $book) : route('admin.manual-books.store'); @endphp

<a href="{{ route('admin.manual-books.index') }}" class="text-sm text-brand hover:underline">← Kelola Manual Book</a>
<div class="mt-2 mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">{{ $book ? 'Edit Manual Book' : 'Tambah Manual Book' }}</h1>
    <p class="text-slate-500">Isi panduan mendukung teks + gambar. Tampil ke seluruh pengguna bila berstatus "Terbit".</p>
</div>

<form method="POST" action="{{ $action }}" class="space-y-6" enctype="multipart/form-data">
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

    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6">
        <label class="block text-sm font-semibold text-brand-dark mb-2">Lampiran Berkas <span class="font-normal text-slate-400">(opsional)</span></label>
        @if($book && $book->file_path)
            <div class="mb-3 flex items-center gap-3 text-sm">
                <a href="{{ route('file.show', $book->file_path) }}" class="text-brand hover:underline break-all">📎 {{ $book->file_name ?: basename($book->file_path) }}</a>
                <label class="flex items-center gap-1.5 text-xs text-red-600"><input type="checkbox" name="remove_file" value="1"> Hapus berkas</label>
            </div>
        @endif
        <input type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.png,.jpg,.jpeg,.zip"
               class="w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand file:text-white file:px-3 file:py-1.5 file:text-sm">
        <p class="text-xs text-slate-400 mt-1">PDF / Word / PowerPoint / Excel / gambar / ZIP, maks 20 MB.@if($book && $book->file_path) Unggah berkas baru untuk mengganti.@endif</p>
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('admin.manual-books.index') }}" class="px-4 py-2 text-sm rounded-lg border border-slate-200">Batal</a>
        <button class="rounded-lg bg-brand text-white px-6 py-2 text-sm font-medium hover:bg-brand-dark">{{ $book ? 'Simpan Perubahan' : 'Tambah Manual Book' }}</button>
    </div>
</form>
@endsection
