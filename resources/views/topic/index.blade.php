@extends('layouts.app')
@section('title', 'Topik & Mitra — Modul 1')

@section('content')
@php $isLeader = auth()->id() === $team->leader_id; $topic = $team->topic; @endphp
<div class="mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-bold text-brand-dark">Modul 1 — Topik & Mitra</h1>
        <p class="text-slate-500">Pilih topik dari katalog koordinator, atau ajukan topik mandiri.</p>
    </div>
    <x-status-badge :status="$team->topic_status" />
</div>

@if($team->topic_review_note)
    <div class="mb-4 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm">
        <span class="font-semibold">Catatan Koordinator:</span> {{ $team->topic_review_note }}
    </div>
@endif

{{-- Topik terpilih saat ini --}}
@if($topic)
    <div class="mb-6 bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-xs rounded-full bg-rose-100 text-brand px-2 py-0.5 font-medium">{{ $topic->origin === 'katalog' ? 'Dari Katalog' : 'Mandiri' }}</span>
            <h2 class="font-semibold text-slate-800">Topik Terpilih</h2>
        </div>
        <p class="text-lg font-bold text-slate-800">{{ $topic->title }}</p>
        @if($topic->partner_label)<p class="text-sm text-slate-500">Mitra: {{ $topic->partner_label }}@if($topic->partner) ({{ $topic->partner->type_label }})@endif</p>@endif

        {{-- Editor fitur tim: menambah/mengurangi fitur tanpa mengubah master topik --}}
        @if($isLeader)
            <form method="POST" action="{{ route('topic.features') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-rose-100 pt-4">
                @csrf @method('PUT')
                <div class="md:col-span-2">
                    <p class="text-sm font-semibold text-brand-dark">Fitur yang Ditawarkan Tim</p>
                    <p class="text-xs text-slate-400">Sesuaikan (tambah/kurangi) fitur untuk tim Anda. Master topik dari koordinator tidak berubah.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Fitur Umum</label>
                    <textarea name="custom_general_features" rows="3" class="w-full rounded-lg border-rose-200 border px-3 py-2">{{ $team->custom_general_features }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Fitur AI</label>
                    <textarea name="custom_ai_features" rows="3" class="w-full rounded-lg border-rose-200 border px-3 py-2">{{ $team->custom_ai_features }}</textarea>
                </div>
                <div class="md:col-span-2 flex items-center gap-3">
                    <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark">Simpan Fitur Tim</button>
                    @if($topic->origin==='katalog' && ($topic->general_features || $topic->ai_features))
                        <span class="text-xs text-slate-400">Master: {{ \Illuminate\Support\Str::limit($topic->general_features, 60) }}</span>
                    @endif
                </div>
            </form>
        @else
            @if($team->effective_general_features)<p class="text-sm mt-2"><span class="text-slate-400">Fitur umum:</span> {{ $team->effective_general_features }}</p>@endif
            @if($team->effective_ai_features)<p class="text-sm"><span class="text-slate-400">Fitur AI:</span> {{ $team->effective_ai_features }}</p>@endif
        @endif
    </div>
@endif

@if($isLeader)
<div x-data="{ tab: '{{ $topic && $topic->origin==='mandiri' ? 'mandiri' : 'katalog' }}' }">
    <div class="flex gap-2 mb-4">
        <button @click="tab='katalog'" :class="tab==='katalog' ? 'bg-brand text-white' : 'bg-white text-slate-600 border border-slate-200'" class="rounded-lg px-4 py-2 text-sm">📚 Pilih dari Katalog</button>
        <button @click="tab='mandiri'" :class="tab==='mandiri' ? 'bg-brand text-white' : 'bg-white text-slate-600 border border-slate-200'" class="rounded-lg px-4 py-2 text-sm">✏️ Ajukan Mandiri</button>
    </div>

    {{-- Katalog --}}
    <div x-show="tab==='katalog'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($catalog as $t)
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
                <h3 class="font-semibold text-slate-800">{{ $t->title }}</h3>
                @if($t->partner)<p class="text-sm text-slate-500">{{ $t->partner->name }} · {{ $t->partner->type_label }}</p>@endif
                @if($t->general_features)<p class="text-sm mt-2 text-slate-600"><span class="text-slate-400">Fitur:</span> {{ \Illuminate\Support\Str::limit($t->general_features, 90) }}</p>@endif
                @if($t->ai_features)<p class="text-sm text-slate-600"><span class="text-slate-400">AI:</span> {{ \Illuminate\Support\Str::limit($t->ai_features, 90) }}</p>@endif
                <form method="POST" action="{{ route('topic.choose') }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="topic_id" value="{{ $t->id }}">
                    <button class="rounded-lg bg-brand text-white px-4 py-1.5 text-sm hover:bg-brand-dark">Pilih Topik Ini</button>
                </form>
            </div>
        @empty
            <p class="text-slate-400">Belum ada topik katalog yang tersedia.</p>
        @endforelse
    </div>

    {{-- Mandiri --}}
    <div x-show="tab==='mandiri'" x-cloak class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6 max-w-2xl">
        <form method="POST" action="{{ route('topic.mandiri') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Judul Topik</label>
                <input name="title" value="{{ old('title', $topic && $topic->origin==='mandiri' ? $topic->title : '') }}" required class="w-full rounded-lg border-slate-300 border px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Nama Mitra (opsional)</label>
                <input name="partner_name" value="{{ old('partner_name', $topic->partner_name ?? '') }}" placeholder="cth: UD Sumber Rejeki / Desa Sukamaju"
                       class="w-full rounded-lg border-slate-300 border px-3 py-2">
                <p class="text-xs text-slate-400 mt-1">Ketik nama mitra Anda sendiri (kosongkan jika internal/tanpa mitra).</p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Fitur Umum</label>
                <textarea name="general_features" rows="2" class="w-full rounded-lg border-slate-300 border px-3 py-2">{{ old('general_features', $topic->general_features ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Fitur AI</label>
                <textarea name="ai_features" rows="2" class="w-full rounded-lg border-slate-300 border px-3 py-2">{{ old('ai_features', $topic->ai_features ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Deskripsi / Latar Belakang</label>
                <textarea name="description" rows="3" class="w-full rounded-lg border-slate-300 border px-3 py-2">{{ old('description', $topic->description ?? '') }}</textarea>
            </div>
            <button class="rounded-lg bg-brand text-white px-5 py-2 font-medium hover:bg-brand-dark">Ajukan Topik Mandiri</button>
        </form>
    </div>
</div>
@else
    <p class="text-sm text-slate-500">Hanya ketua tim yang dapat memilih/mengajukan topik.</p>
@endif
@endsection
