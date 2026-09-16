@extends('layouts.app')
@section('title', 'Capaian Pembelajaran (CLO)')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Capaian Pembelajaran</h1>
    <p class="text-slate-500">{{ $ay->label }} · Kelola PLO → CLO → Sub-CLO. Sub-CLO dipetakan ke tiap modul praktikum.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- ============ PLO ============ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
        <h2 class="font-semibold text-slate-800 mb-3">PLO <span class="text-slate-400 text-sm">Program Learning Outcome</span></h2>
        <form method="POST" action="{{ route('admin.plos.store') }}" class="flex flex-col gap-2 mb-4">
            @csrf
            <div class="flex gap-2">
                <input name="code" placeholder="PLO1" required class="w-24 rounded-lg border-rose-200 border px-2 py-1.5 text-sm">
                <input name="order_index" type="number" min="0" placeholder="#" class="w-14 rounded-lg border-rose-200 border px-2 py-1.5 text-sm" title="Urutan">
                <button class="rounded-lg bg-brand text-white px-3 py-1.5 text-sm shrink-0">+ Tambah</button>
            </div>
            <textarea name="description" rows="2" placeholder="Deskripsi PLO" class="w-full rounded-lg border-rose-200 border px-2 py-1.5 text-sm"></textarea>
        </form>
        <div class="space-y-2">
            @forelse($plos as $p)
                <div x-data="{ edit:false }" class="rounded-lg border border-slate-200 p-3 text-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div><span class="font-semibold text-brand-dark">{{ $p->code }}</span> <span class="text-slate-600">{{ $p->description }}</span></div>
                        <div class="flex gap-1 shrink-0">
                            <button @click="edit=!edit" class="text-brand text-xs">edit</button>
                            <form method="POST" action="{{ route('admin.plos.destroy', $p) }}" onsubmit="return confirm('Hapus {{ $p->code }}?')">@csrf @method('DELETE')<button class="text-red-500 text-xs">hapus</button></form>
                        </div>
                    </div>
                    <form x-show="edit" x-cloak method="POST" action="{{ route('admin.plos.update', $p) }}" class="mt-2 space-y-1">
                        @csrf @method('PUT')
                        <div class="flex gap-1"><input name="code" value="{{ $p->code }}" class="w-24 rounded border-slate-300 border px-2 py-1 text-xs"><input name="order_index" type="number" value="{{ $p->order_index }}" class="w-14 rounded border-slate-300 border px-2 py-1 text-xs"></div>
                        <textarea name="description" rows="2" class="w-full rounded border-slate-300 border px-2 py-1 text-xs">{{ $p->description }}</textarea>
                        <button class="rounded bg-brand text-white px-2 py-1 text-xs">Simpan</button>
                    </form>
                </div>
            @empty
                <p class="text-slate-400 text-sm">Belum ada PLO.</p>
            @endforelse
        </div>
    </div>

    {{-- ============ CLO ============ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
        <h2 class="font-semibold text-slate-800 mb-3">CLO <span class="text-slate-400 text-sm">Course Learning Outcome</span></h2>
        <form method="POST" action="{{ route('admin.clos.store') }}" class="flex flex-col gap-2 mb-4">
            @csrf
            <div class="flex gap-2">
                <input name="code" placeholder="CLO1" required class="w-24 rounded-lg border-rose-200 border px-2 py-1.5 text-sm">
                <select name="plo_id" class="flex-1 rounded-lg border-rose-200 border px-2 py-1.5 text-sm"><option value="">— PLO —</option>@foreach($plos as $p)<option value="{{ $p->id }}">{{ $p->code }}</option>@endforeach</select>
                <input name="order_index" type="number" min="0" placeholder="#" class="w-14 rounded-lg border-rose-200 border px-2 py-1.5 text-sm">
            </div>
            <textarea name="description" rows="2" placeholder="Deskripsi CLO" class="w-full rounded-lg border-rose-200 border px-2 py-1.5 text-sm"></textarea>
            <button class="rounded-lg bg-brand text-white px-3 py-1.5 text-sm self-start">+ Tambah CLO</button>
        </form>
        <div class="space-y-2">
            @forelse($clos as $c)
                <div x-data="{ edit:false }" class="rounded-lg border border-slate-200 p-3 text-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div><span class="font-semibold text-brand-dark">{{ $c->code }}</span> @if($c->plo)<span class="text-[11px] rounded bg-slate-100 px-1">{{ $c->plo->code }}</span>@endif <span class="text-slate-600">{{ $c->description }}</span></div>
                        <div class="flex gap-1 shrink-0">
                            <button @click="edit=!edit" class="text-brand text-xs">edit</button>
                            <form method="POST" action="{{ route('admin.clos.destroy', $c) }}" onsubmit="return confirm('Hapus {{ $c->code }} & Sub-CLO di bawahnya?')">@csrf @method('DELETE')<button class="text-red-500 text-xs">hapus</button></form>
                        </div>
                    </div>
                    <form x-show="edit" x-cloak method="POST" action="{{ route('admin.clos.update', $c) }}" class="mt-2 space-y-1">
                        @csrf @method('PUT')
                        <div class="flex gap-1"><input name="code" value="{{ $c->code }}" class="w-24 rounded border-slate-300 border px-2 py-1 text-xs"><select name="plo_id" class="flex-1 rounded border-slate-300 border px-2 py-1 text-xs"><option value="">— PLO —</option>@foreach($plos as $p)<option value="{{ $p->id }}" @selected($c->plo_id===$p->id)>{{ $p->code }}</option>@endforeach</select><input name="order_index" type="number" value="{{ $c->order_index }}" class="w-14 rounded border-slate-300 border px-2 py-1 text-xs"></div>
                        <textarea name="description" rows="2" class="w-full rounded border-slate-300 border px-2 py-1 text-xs">{{ $c->description }}</textarea>
                        <button class="rounded bg-brand text-white px-2 py-1 text-xs">Simpan</button>
                    </form>
                </div>
            @empty
                <p class="text-slate-400 text-sm">Belum ada CLO.</p>
            @endforelse
        </div>
    </div>

    {{-- ============ Sub-CLO ============ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
        <h2 class="font-semibold text-slate-800 mb-3">Sub-CLO</h2>
        @if($clos->isEmpty())
            <p class="text-amber-600 text-sm mb-3">Tambahkan CLO dulu sebelum Sub-CLO.</p>
        @endif
        <form method="POST" action="{{ route('admin.sub-clos.store') }}" class="flex flex-col gap-2 mb-4">
            @csrf
            <div class="flex gap-2">
                <input name="code" placeholder="Sub-CLO1.1" required class="w-28 rounded-lg border-rose-200 border px-2 py-1.5 text-sm">
                <select name="clo_id" required class="flex-1 rounded-lg border-rose-200 border px-2 py-1.5 text-sm"><option value="">— CLO —</option>@foreach($clos as $c)<option value="{{ $c->id }}">{{ $c->code }}</option>@endforeach</select>
                <input name="order_index" type="number" min="0" placeholder="#" class="w-14 rounded-lg border-rose-200 border px-2 py-1.5 text-sm">
            </div>
            <textarea name="description" rows="2" placeholder="Deskripsi Sub-CLO" class="w-full rounded-lg border-rose-200 border px-2 py-1.5 text-sm"></textarea>
            <button class="rounded-lg bg-brand text-white px-3 py-1.5 text-sm self-start" @disabled($clos->isEmpty())>+ Tambah Sub-CLO</button>
        </form>
        <div class="space-y-2">
            @forelse($subClos as $sc)
                <div x-data="{ edit:false }" class="rounded-lg border border-slate-200 p-3 text-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div><span class="font-semibold text-brand-dark">{{ $sc->code }}</span> <span class="text-[11px] rounded bg-slate-100 px-1">{{ $sc->clo?->code }}</span> <span class="text-slate-600">{{ $sc->description }}</span></div>
                        <div class="flex gap-1 shrink-0">
                            <button @click="edit=!edit" class="text-brand text-xs">edit</button>
                            <form method="POST" action="{{ route('admin.sub-clos.destroy', $sc) }}" onsubmit="return confirm('Hapus {{ $sc->code }}?')">@csrf @method('DELETE')<button class="text-red-500 text-xs">hapus</button></form>
                        </div>
                    </div>
                    <form x-show="edit" x-cloak method="POST" action="{{ route('admin.sub-clos.update', $sc) }}" class="mt-2 space-y-1">
                        @csrf @method('PUT')
                        <div class="flex gap-1"><input name="code" value="{{ $sc->code }}" class="w-28 rounded border-slate-300 border px-2 py-1 text-xs"><select name="clo_id" class="flex-1 rounded border-slate-300 border px-2 py-1 text-xs">@foreach($clos as $c)<option value="{{ $c->id }}" @selected($sc->clo_id===$c->id)>{{ $c->code }}</option>@endforeach</select><input name="order_index" type="number" value="{{ $sc->order_index }}" class="w-14 rounded border-slate-300 border px-2 py-1 text-xs"></div>
                        <textarea name="description" rows="2" class="w-full rounded border-slate-300 border px-2 py-1 text-xs">{{ $sc->description }}</textarea>
                        <button class="rounded bg-brand text-white px-2 py-1 text-xs">Simpan</button>
                    </form>
                </div>
            @empty
                <p class="text-slate-400 text-sm">Belum ada Sub-CLO.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
