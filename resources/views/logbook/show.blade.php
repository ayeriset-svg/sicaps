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

        <div class="bg-white rounded-2xl shadow-sm border {{ $isIndividual ? 'border-indigo-100' : 'border-rose-100' }} p-5">
            <div class="flex items-center justify-between mb-4 gap-2">
                <h2 class="font-semibold text-slate-800">📝 {{ $isIndividual ? 'Tugas Individu' : 'Form Logbook Tim' }}</h2>
                @if($isIndividual)<span class="text-xs rounded-full bg-indigo-100 text-indigo-700 px-2 py-0.5 font-medium">Dikerjakan per mahasiswa</span>@endif
            </div>

            {{-- Banner status/akses --}}
            @if(! $module->is_open)
                <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800 mb-4">
                    🔒 <span class="font-semibold">Belum dibuka koordinator.</span> Anda belum dapat mengerjakan {{ $isIndividual ? 'tugas' : 'logbook' }} ini. Materi tetap dapat dipelajari.
                </div>
            @elseif($locked)
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 mb-4">
                    ✅ <span class="font-semibold">Sudah disetujui (PASS) & terkunci.</span> {{ $isIndividual ? 'Tugas' : 'Logbook' }} tidak dapat diubah lagi.
                </div>
            @elseif($logbook->status_approval === 'Revision Needed')
                <div class="rounded-lg bg-pink-50 border border-pink-200 px-4 py-3 text-sm text-pink-800 mb-4">
                    <p class="font-semibold">🔁 Perlu Revisi — silakan kerjakan ulang & submit lagi.</p>
                    @if($logbook->feedback)<p class="mt-1">Catatan: {{ $logbook->feedback }}</p>@endif
                </div>
            @endif
            @if(! $isIndividual && ! $isLeader)
                <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-2 text-sm text-amber-700 mb-4">Hanya ketua tim yang dapat mengisi logbook tim. Anda hanya dapat melihat.</div>
            @endif

            @if($mayWork)
                <form method="POST" action="{{ route('logbook.update', $module) }}" class="space-y-4" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    @foreach($module->fields() as $field)
                        @php $val = $logbook->payload_json[$field['key']] ?? ''; @endphp
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ $field['label'] }} @if($field['required'] ?? false)<span class="text-red-500">*</span>@endif</label>
                            @if($field['type'] === 'richtext')
                                <x-richtext :name="'fields['.$field['key'].']'" :value="$val" />
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
                                <p class="text-xs text-slate-400 mt-1">Format: PDF / Word (.doc, .docx), maks 10 MB.@if($val) Kosongkan bila tidak ingin mengganti.@endif</p>
                            @else
                                <input type="url" name="fields[{{ $field['key'] }}]" value="{{ $val }}"
                                       placeholder="https://..." class="w-full rounded-lg border-slate-300 border px-3 py-2">
                            @endif
                        </div>
                    @endforeach
                    <button class="rounded-lg bg-brand text-white px-5 py-2 font-medium hover:bg-brand-dark">Submit {{ $isIndividual ? 'Tugas' : 'Logbook' }}</button>
                </form>
            @else
                {{-- Tampilan read-only isian (tidak dapat diedit) --}}
                @if($logbook->payload_json)
                    <div class="space-y-4">
                        @foreach($module->fields() as $field)
                            @php $val = $logbook->payload_json[$field['key']] ?? null; @endphp
                            <div>
                                <p class="text-sm font-medium text-slate-700 mb-1">{{ $field['label'] }}</p>
                                @if($field['type'] === 'link')
                                    @if($val)<a href="{{ $val }}" target="_blank" rel="noopener" class="text-brand hover:underline break-all">🔗 {{ $val }}</a>@else<span class="text-slate-400 text-sm">—</span>@endif
                                @elseif($field['type'] === 'file')
                                    @php $fname = $logbook->payload_json[$field['key'].'__name'] ?? null; @endphp
                                    @if($val)<a href="{{ route('file.show', $val) }}" class="text-brand hover:underline break-all">📎 {{ $fname ?: basename($val) }}</a>@else<span class="text-slate-400 text-sm">—</span>@endif
                                @else
                                    <div class="rt-content text-sm max-w-none">{!! $val ?: '<span class="text-slate-400">—</span>' !!}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-400">Belum ada isian.</p>
                @endif
            @endif
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
