@extends('layouts.app')
@section('title', $module ? 'Edit Modul' : 'Tambah Modul')

@section('content')
@php
    $fields = $module ? $module->fields() : config('capstone.default_logbook_fields');
    $action = $module ? route('admin.modules.update', $module) : route('admin.modules.store');
@endphp

<a href="{{ route('admin.modules.index') }}" class="text-sm text-brand hover:underline">← Kelola Modul</a>
<div class="mt-2 mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">{{ $module ? 'Edit Modul' : 'Tambah Modul' }}</h1>
    <p class="text-slate-500">{{ $ay?->label }} · atur identitas modul, materi (teks+gambar), dan field logbook.</p>
</div>

<form method="POST" action="{{ $action }}" x-data="{ fields: {{ \Illuminate\Support\Js::from(array_values($fields)) }} }" class="space-y-6">
    @csrf
    @if($module)@method('PUT')@endif

    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Identitas Modul</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div><label class="block text-xs font-medium mb-1">Urutan</label><input type="number" name="order_index" value="{{ old('order_index', $module->order_index ?? 0) }}" required class="w-full rounded-lg border-rose-200 border px-3 py-2 text-sm"></div>
            <div><label class="block text-xs font-medium mb-1">Label Minggu</label><input name="week_label" value="{{ old('week_label', $module->week_label ?? '') }}" placeholder="W1 / W3.1" required class="w-full rounded-lg border-rose-200 border px-3 py-2 text-sm"></div>
            <div><label class="block text-xs font-medium mb-1">Kode</label><input name="code" value="{{ old('code', $module->code ?? '') }}" placeholder="M0 / A1" class="w-full rounded-lg border-rose-200 border px-3 py-2 text-sm"></div>
            <div><label class="block text-xs font-medium mb-1">Tipe</label>
                <select name="type" class="w-full rounded-lg border-rose-200 border px-3 py-2 text-sm">
                    @foreach(['module'=>'Modul (logbook)','assessment'=>'Assessment (milestone)','other'=>'Lainnya (logbook)'] as $k=>$v)<option value="{{ $k }}" @selected(old('type', $module->type ?? 'module')===$k)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-span-2 lg:col-span-3"><label class="block text-xs font-medium mb-1">Judul</label><input name="title" value="{{ old('title', $module->title ?? '') }}" required class="w-full rounded-lg border-rose-200 border px-3 py-2 text-sm"></div>
            <div><label class="block text-xs font-medium mb-1">Stage (jika assessment)</label>
                <select name="assessment_stage" class="w-full rounded-lg border-rose-200 border px-3 py-2 text-sm">
                    <option value="">— Tidak ada —</option>
                    @foreach(['A1','A2','A3'] as $st)<option value="{{ $st }}" @selected(old('assessment_stage', $module->assessment_stage ?? '')===$st)>{{ $st }}</option>@endforeach
                </select>
            </div>
        </div>

        {{-- Batasan penggunaan AI (Tabel V.5) --}}
        <div class="mt-4" x-data="{ lvl: '{{ old('ai_policy_level', $module->ai_policy_level ?? 1) }}' }">
            <label class="block text-xs font-medium mb-1">Batasan Penggunaan AI (Level)</label>
            <select name="ai_policy_level" x-model="lvl" class="w-full sm:w-96 rounded-lg border-rose-200 border px-3 py-2 text-sm">
                @foreach(config('capstone.ai_levels') as $n => $lv)
                    <option value="{{ $n }}" @selected((int)old('ai_policy_level', $module->ai_policy_level ?? 1)===$n)>Level {{ $n }} — {{ $lv['name'] }}</option>
                @endforeach
            </select>
            <div class="mt-2 rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600">
                @foreach(config('capstone.ai_levels') as $n => $lv)
                    <div x-show="lvl==='{{ $n }}'" x-cloak>
                        <p><span class="font-semibold text-brand-dark">{{ $lv['desc'] }}</span></p>
                        <p class="mt-1 text-slate-500">Panduan dosen: {{ $lv['guide'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6" x-data="{ indiv: {{ old('is_individual', $module->is_individual ?? false) ? 'true' : 'false' }} }">
        <h2 class="font-semibold text-slate-800 mb-4">Pengerjaan & Akses</h2>
        <div class="space-y-3">
            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="is_open" value="1" class="mt-0.5" @checked(old('is_open', $module->is_open ?? false))>
                <span><span class="font-medium">Buka untuk dikerjakan mahasiswa</span> — bila dimatikan, mahasiswa belum bisa mengisi (hanya melihat materi).</span>
            </label>
            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="is_individual" value="1" x-model="indiv" class="mt-0.5" @checked(old('is_individual', $module->is_individual ?? false))>
                <span><span class="font-medium">Tugas Individu</span> — wajib dikerjakan tiap mahasiswa (tidak diwakilkan ketua tim). Jika di-PASS, mahasiswa otomatis ditandai HADIR pada presensi.</span>
            </label>
            <div x-show="indiv" x-cloak class="grid grid-cols-2 gap-3 pl-6 max-w-md">
                <div><label class="block text-xs font-medium mb-1">Presensi: Minggu ke-</label>
                    <input type="number" min="1" max="16" name="attendance_week" value="{{ old('attendance_week', $module->attendance_week ?? '') }}" placeholder="1-16" class="w-full rounded-lg border-rose-200 border px-3 py-2 text-sm"></div>
                <div><label class="block text-xs font-medium mb-1">Sesi ke-</label>
                    <input type="number" min="1" max="2" name="attendance_session" value="{{ old('attendance_session', $module->attendance_session ?? '') }}" placeholder="1-2" class="w-full rounded-lg border-rose-200 border px-3 py-2 text-sm"></div>
                <p class="col-span-2 text-xs text-slate-400">Opsional: saat tugas ini di-PASS, mahasiswa otomatis HADIR pada slot presensi di atas. Kosongkan bila tak perlu.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6">
        <h2 class="font-semibold text-slate-800 mb-1">Materi Modul</h2>
        <p class="text-xs text-slate-400 mb-4">Mengikuti template dokumen. Semua bagian mendukung teks + gambar.</p>
        <div class="space-y-6">
            @foreach(\App\Models\Module::MATERIAL_FIELDS as $key => $label)
                <div>
                    <label class="block text-sm font-semibold text-brand-dark mb-1">{{ $label }}</label>
                    <x-richtext :name="$key" :value="old($key, $module->$key ?? '')" :minHeight="in_array($key,['description','tasks']) ? 520 : 380" />
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6">
        <div class="flex items-center justify-between mb-2">
            <h2 class="font-semibold text-slate-800">Field Logbook</h2>
            <button type="button" @click="fields.push({label:'',type:'richtext',required:false})" class="text-sm text-brand hover:underline">+ Tambah Field</button>
        </div>
        <p class="text-xs text-slate-400 mb-3">Field diabaikan untuk tipe "assessment". "Teks + Gambar" = editor dokumen; "Link" = input URL; "Unggah Berkas" = mahasiswa upload PDF/Word (maks 10 MB).</p>
        <div class="space-y-2">
            <template x-for="(f, i) in fields" :key="i">
                <div class="flex items-center gap-2">
                    <input :name="'field_label['+i+']'" x-model="f.label" placeholder="Label field" class="flex-1 rounded-lg border-rose-200 border px-3 py-2 text-sm">
                    <select :name="'field_type['+i+']'" x-model="f.type" class="rounded-lg border-rose-200 border px-3 py-2 text-sm">
                        <option value="richtext">Teks + Gambar</option>
                        <option value="link">Link</option>
                        <option value="file">Unggah Berkas (PDF/Word)</option>
                    </select>
                    <label class="flex items-center gap-1 text-xs whitespace-nowrap"><input type="checkbox" :name="'field_required['+i+']'" x-model="f.required"> Wajib</label>
                    <button type="button" @click="fields.splice(i,1)" class="text-red-500 text-sm px-1">✕</button>
                </div>
            </template>
        </div>
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('admin.modules.index') }}" class="px-4 py-2 text-sm rounded-lg border border-slate-200">Batal</a>
        <button class="rounded-lg bg-brand text-white px-6 py-2 text-sm font-medium hover:bg-brand-dark">{{ $module ? 'Simpan Perubahan' : 'Tambah Modul' }}</button>
    </div>
</form>
@endsection
