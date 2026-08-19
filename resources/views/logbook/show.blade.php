@extends('layouts.app')
@section('title', $module->title)

@section('content')
<a href="{{ route('logbook.index') }}" class="text-sm text-brand hover:underline">← Semua Modul</a>

<div class="mt-2 mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <span class="text-xs font-medium text-slate-400">{{ $module->week_label }} · {{ $module->code }}</span>
        <h1 class="text-2xl font-bold text-brand-dark">{{ $module->title }}</h1>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('logbook.print', $module) }}" target="_blank" class="rounded-lg border border-rose-200 text-brand px-3 py-1.5 text-sm hover:bg-rose-50">🖨️ Simpan PDF</a>
        <x-status-badge :status="$logbook->status_approval" />
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <h2 class="font-semibold text-slate-800 mb-3">📘 Materi Modul</h2>
            @php $anyMaterial = false; @endphp
            <div class="space-y-4">
                @foreach(\App\Models\Module::MATERIAL_FIELDS as $key => $label)
                    @if(filled($module->$key))
                        @php $anyMaterial = true; @endphp
                        <div>
                            <p class="text-xs font-semibold text-brand-dark uppercase tracking-wide mb-1">{{ $label }}</p>
                            <div class="rt-content text-sm text-slate-600 max-w-none">{!! $module->$key !!}</div>
                        </div>
                    @endif
                @endforeach
                @unless($anyMaterial)<p class="text-sm text-slate-400">Materi belum diisi koordinator.</p>@endunless
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <h2 class="font-semibold text-slate-800 mb-4">📝 Form Logbook</h2>
            @if($logbook->status_approval === 'Revision Needed')
                <div class="rounded-lg bg-pink-50 border border-pink-200 px-4 py-3 text-sm text-pink-800 mb-4">
                    <p class="font-semibold">🔁 Perlu Revisi — silakan kerjakan ulang & submit lagi.</p>
                    @if($logbook->feedback)<p class="mt-1">Catatan: {{ $logbook->feedback }}</p>@endif
                </div>
            @elseif($logbook->status_approval === 'Approved')
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-700 mb-4">✅ Sudah disetujui. Anda tetap dapat memperbarui bila diperlukan.</div>
            @endif
            @if(! $isLeader)
                <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-2 text-sm text-amber-700 mb-4">Hanya ketua tim yang dapat submit logbook.</div>
            @endif
            <form method="POST" action="{{ route('logbook.update', $module) }}" class="space-y-4" enctype="multipart/form-data">
                @csrf @method('PUT')
                @foreach($module->fields() as $field)
                    @php $val = $logbook->payload_json[$field['key']] ?? ''; @endphp
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ $field['label'] }} @if($field['required'] ?? false)<span class="text-red-500">*</span>@endif</label>
                        @if($field['type'] === 'richtext')
                            <x-richtext :name="'fields['.$field['key'].']'" :value="$val" :disabled="! $isLeader" />
                        @elseif($field['type'] === 'file')
                            @php $fname = $logbook->payload_json[$field['key'].'__name'] ?? null; @endphp
                            @if($val)
                                <div class="mb-2 flex items-center gap-2 text-sm">
                                    <a href="{{ route('file.show', $val) }}" class="text-brand hover:underline break-all">📎 {{ $fname ?: basename($val) }}</a>
                                    <span class="text-xs text-slate-400">(berkas saat ini)</span>
                                </div>
                            @endif
                            <input type="file" name="files[{{ $field['key'] }}]" accept=".pdf,.doc,.docx" @unless($isLeader) disabled @endunless
                                   class="w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand file:text-white file:px-3 file:py-1.5 file:text-sm">
                            <p class="text-xs text-slate-400 mt-1">Format: PDF / Word (.doc, .docx), maks 10 MB.@if($val) Kosongkan bila tidak ingin mengganti.@endif</p>
                        @else
                            <input type="url" name="fields[{{ $field['key'] }}]" value="{{ $val }}" @unless($isLeader) disabled @endunless
                                   placeholder="https://..." class="w-full rounded-lg border-slate-300 border px-3 py-2">
                        @endif
                    </div>
                @endforeach
                @if($isLeader)
                    <button class="rounded-lg bg-brand text-white px-5 py-2 font-medium hover:bg-brand-dark">Submit Logbook</button>
                @endif
            </form>
        </div>
    </div>

    <div class="space-y-6">
        {{-- Batasan AI tugas ini --}}
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <h2 class="font-semibold text-slate-800 mb-2">🤖 Batasan Penggunaan AI</h2>
            <x-ai-level :level="$module->ai_policy_level" :showDesc="true" />
            <p class="text-xs text-slate-400 mt-2">Panduan: {{ $module->aiLevel()['guide'] }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <h2 class="font-semibold text-slate-800 mb-2">💬 Feedback Koordinator</h2>
            @if($logbook->feedback)
                <p class="text-sm text-slate-600 whitespace-pre-line">{{ $logbook->feedback }}</p>
            @else
                <p class="text-sm text-slate-400">Belum ada feedback.</p>
            @endif

            {{-- Hasil pemeriksaan AI (muncul setelah dicek koordinator) --}}
            @if($logbook->ai_checked_at)
                @php $ai = $logbook->ai_percentage; $band = $ai===null ? 'slate' : ($ai>=60?'red':($ai>=30?'amber':'emerald')); @endphp
                <div class="mt-4 rounded-xl border p-4 bg-{{ $band }}-50 border-{{ $band }}-200">
                    <p class="text-xs uppercase tracking-wide text-{{ $band }}-700/70">Estimasi Indikasi AI</p>
                    <p class="text-2xl font-extrabold text-{{ $band }}-700">{{ $ai!==null ? number_format($ai,1).'%' : 'N/A' }}</p>
                    <p class="text-xs text-slate-500 mt-1">
                        Teks {{ $logbook->ai_text_percentage!==null ? number_format($logbook->ai_text_percentage,1).'%' : '—' }} ·
                        Gambar {{ $logbook->ai_image_percentage!==null ? number_format($logbook->ai_image_percentage,1).'%' : '—' }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-2">⚠️ Estimasi indikatif, bukan vonis. Diperiksa {{ $logbook->ai_checked_at->format('d M Y H:i') }}.</p>
                </div>
            @endif
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <h2 class="font-semibold text-slate-800 mb-3">✅ Status Validasi</h2>
            <x-status-badge :status="$logbook->status_approval" />
            @if($logbook->submitted_at)<p class="text-xs text-slate-400 mt-2">Submit terakhir: {{ $logbook->submitted_at->format('d M Y H:i') }}</p>@endif
        </div>
        @if($logbook->versions->isNotEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
                <h2 class="font-semibold text-slate-800 mb-3">🕓 Riwayat Versi</h2>
                <ul class="space-y-2 text-sm">
                    @foreach($logbook->versions as $v)
                        <li class="flex justify-between text-slate-500"><span>v{{ $v->version_number }} · {{ $v->status_snapshot }}</span><span>{{ $v->created_at->format('d/m H:i') }}</span></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection
