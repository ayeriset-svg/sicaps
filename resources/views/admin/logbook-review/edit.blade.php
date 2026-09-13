@extends('layouts.app')
@section('title', 'Edit Isi — '.$logbook->module->title)

@section('content')
<a href="{{ route('admin.logbook-review.show', $logbook) }}" class="text-sm text-brand hover:underline">← Kembali ke Review</a>
<div class="mt-2 mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Edit Isi Logbook</h1>
    <p class="text-slate-500">
        {{ $logbook->module->code }} · {{ $logbook->module->title }} · Tim {{ $logbook->team->team_name }}
        @if($logbook->module->isIndividual() && $logbook->user) · 👤 {{ $logbook->user->name }} @endif
    </p>
    <p class="text-xs text-amber-600 mt-1">⚠️ Edit oleh koordinator akan menimpa isi yang disubmit mahasiswa. Status review tidak berubah.</p>
</div>

<form method="POST" action="{{ route('admin.logbook-review.update-content', $logbook) }}" class="space-y-5" enctype="multipart/form-data">
    @csrf @method('PUT')
    @foreach($logbook->module->fields() as $field)
        @php $val = $logbook->payload_json[$field['key']] ?? ''; @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <label class="block text-sm font-semibold text-brand-dark mb-2">{{ $field['label'] }}</label>
            @if($field['type'] === 'richtext')
                <x-richtext :name="'fields['.$field['key'].']'" :value="$val" :minHeight="380" />
            @elseif($field['type'] === 'file')
                @php $fname = $logbook->payload_json[$field['key'].'__name'] ?? null; @endphp
                @if($val)
                    <div class="mb-2 flex items-center gap-2 text-sm">
                        <a href="{{ route('file.show', $val) }}" class="text-brand hover:underline break-all">📎 {{ $fname ?: basename($val) }}</a>
                        <span class="text-xs text-slate-400">(berkas saat ini)</span>
                    </div>
                @endif
                <input type="file" name="files[{{ $field['key'] }}]" accept=".pdf,.doc,.docx"
                       class="w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand file:text-white file:px-3 file:py-1.5 file:text-sm">
                <p class="text-xs text-slate-400 mt-1">PDF/Word maks 10 MB.@if($val) Kosongkan bila tidak ingin mengganti.@endif</p>
            @else
                <input type="url" name="fields[{{ $field['key'] }}]" value="{{ $val }}" placeholder="https://..."
                       class="w-full rounded-lg border-slate-300 border px-3 py-2">
            @endif
        </div>
    @endforeach

    <div class="flex justify-end gap-2">
        <a href="{{ route('admin.logbook-review.show', $logbook) }}" class="px-4 py-2 text-sm rounded-lg border border-slate-200">Batal</a>
        <button class="rounded-lg bg-brand text-white px-6 py-2 text-sm font-medium hover:bg-brand-dark">Simpan Perubahan</button>
    </div>
</form>
@endsection
