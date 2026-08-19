@extends('layouts.app')
@section('title', 'Kelola Mitra')

@section('content')
<div class="mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-bold text-brand-dark">Kelola Mitra</h1>
        <p class="text-slate-500">Master mitra: industri, masyarakat desa, internal.</p>
    </div>
    <div x-data="{ open:false }">
        <button @click="open=true" class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark">+ Tambah Mitra</button>
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="open=false">
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h3 class="font-semibold mb-4">Tambah Mitra</h3>
                <form method="POST" action="{{ route('admin.partners.store') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div><label class="block text-sm font-medium mb-1">Nama Mitra</label><input name="name" required class="w-full rounded-lg border-slate-300 border px-3 py-2"></div>
                    <div><label class="block text-sm font-medium mb-1">Jenis</label>
                        <select name="type" class="w-full rounded-lg border-slate-300 border px-3 py-2">
                            @foreach(config('capstone.partner_types') as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="block text-sm font-medium mb-1">Logo (opsional)</label><input type="file" name="logo" accept="image/*" class="w-full text-sm"></div>
                    <div><label class="block text-sm font-medium mb-1">Alamat (opsional)</label><textarea name="address" rows="2" class="w-full rounded-lg border-slate-300 border px-3 py-2"></textarea></div>
                    <div><label class="block text-sm font-medium mb-1">Contact Person (opsional)</label><input name="contact_person" class="w-full rounded-lg border-slate-300 border px-3 py-2"></div>
                    <div class="flex justify-end gap-2"><button type="button" @click="open=false" class="px-4 py-2 text-sm">Batal</button><button class="rounded-lg bg-brand text-white px-4 py-2 text-sm">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($partners as $p)
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5" x-data="{ edit:false }">
            <div class="flex items-center gap-3">
                @if($p->logo)
                    <img src="{{ route('file.show', ['path' => $p->logo]) }}" alt="" class="h-12 w-12 rounded object-cover border border-slate-200">
                @else
                    <div class="h-12 w-12 rounded bg-slate-100 flex items-center justify-center text-xl">🏢</div>
                @endif
                <div class="flex-1">
                    <h3 class="font-semibold text-slate-800">{{ $p->name }}</h3>
                    <span class="text-xs rounded-full bg-slate-100 px-2 py-0.5">{{ $p->type_label }}</span>
                </div>
            </div>
            @if($p->address)<p class="text-sm text-slate-500 mt-2">{{ $p->address }}</p>@endif
            <p class="text-xs text-slate-400 mt-1">{{ $p->topics_count }} topik terkait</p>
            <div class="mt-3 flex items-center gap-1 text-sm">
                <button @click="edit=true" title="Edit" class="p-1.5 rounded-lg text-brand hover:bg-rose-50"><x-icon name="edit" /></button>
                <form method="POST" action="{{ route('admin.partners.destroy', $p) }}" onsubmit="return confirm('Hapus mitra?')">@csrf @method('DELETE')<button title="Hapus" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50"><x-icon name="trash" /></button></form>
            </div>

            <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 text-left" @click.self="edit=false">
                <div class="bg-white rounded-xl p-6 w-full max-w-md">
                    <h3 class="font-semibold mb-4">Edit {{ $p->name }}</h3>
                    <form method="POST" action="{{ route('admin.partners.update', $p) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf @method('PUT')
                        <div><label class="block text-sm font-medium mb-1">Nama</label><input name="name" value="{{ $p->name }}" class="w-full rounded-lg border-slate-300 border px-3 py-2"></div>
                        <div><label class="block text-sm font-medium mb-1">Jenis</label>
                            <select name="type" class="w-full rounded-lg border-slate-300 border px-3 py-2">@foreach(config('capstone.partner_types') as $k=>$v)<option value="{{ $k }}" @selected($p->type===$k)>{{ $v }}</option>@endforeach</select>
                        </div>
                        <div><label class="block text-sm font-medium mb-1">Ganti Logo</label><input type="file" name="logo" accept="image/*" class="w-full text-sm"></div>
                        <div><label class="block text-sm font-medium mb-1">Alamat</label><textarea name="address" rows="2" class="w-full rounded-lg border-slate-300 border px-3 py-2">{{ $p->address }}</textarea></div>
                        <div><label class="block text-sm font-medium mb-1">Contact Person</label><input name="contact_person" value="{{ $p->contact_person }}" class="w-full rounded-lg border-slate-300 border px-3 py-2"></div>
                        <div class="flex justify-end gap-2"><button type="button" @click="edit=false" class="px-4 py-2 text-sm">Batal</button><button class="rounded-lg bg-brand text-white px-4 py-2 text-sm">Simpan</button></div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <p class="text-slate-400">Belum ada mitra.</p>
    @endforelse
</div>
@endsection
