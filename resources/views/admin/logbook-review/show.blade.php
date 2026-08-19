@extends('layouts.app')
@section('title', 'Review — '.$logbook->module->title)

@section('content')
<a href="{{ route('admin.logbook-review.index') }}" class="text-sm text-brand hover:underline">← Antrian Review</a>
<div class="mt-2 mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <span class="text-xs text-slate-400">{{ $logbook->module->week_label }} · {{ $logbook->module->code }} · Tim {{ $logbook->team->team_name }} · {{ $logbook->team->class_name }}</span>
        <h1 class="text-2xl font-bold text-brand-dark">{{ $logbook->module->title }}</h1>
    </div>
    <a href="{{ route('admin.logbook-review.print', $logbook) }}" target="_blank" class="rounded-lg border border-rose-200 text-brand px-3 py-1.5 text-sm hover:bg-rose-50">🖨️ Generate PDF</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-4">
        @foreach($logbook->module->fields() as $field)
            @php $val = $logbook->payload_json[$field['key']] ?? null; @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
                <h2 class="font-semibold text-slate-800 mb-2">{{ $field['label'] }}</h2>
                @if($field['type'] === 'link')
                    @if($val)<a href="{{ $val }}" target="_blank" rel="noopener" class="text-brand hover:underline break-all">🔗 {{ $val }}</a>@else<span class="text-slate-400 text-sm">—</span>@endif
                @elseif($field['type'] === 'file')
                    @php $fname = $logbook->payload_json[$field['key'].'__name'] ?? null; @endphp
                    @if($val)<a href="{{ route('file.show', $val) }}" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 text-brand px-3 py-1.5 text-sm hover:bg-rose-50">📎 Unduh {{ $fname ?: basename($val) }}</a>@else<span class="text-slate-400 text-sm">— belum ada berkas —</span>@endif
                @else
                    <div class="rt-content text-sm max-w-none">{!! $val ?: '<span class="text-slate-400">—</span>' !!}</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="space-y-6">
        {{-- Pemeriksaan indikasi AI --}}
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <h2 class="font-semibold text-slate-800">🤖 Periksa Indikasi AI</h2>
                <x-ai-level :level="$logbook->module->ai_policy_level" />
            </div>
            <p class="text-xs text-slate-400 mb-3">Batasan tugas: {{ $logbook->module->aiLevel()['desc'] }}</p>

            @if($logbook->ai_checked_at)
                @php $ai = $logbook->ai_percentage; $band = $ai===null ? 'slate' : ($ai>=60?'red':($ai>=30?'amber':'emerald')); @endphp
                <div class="rounded-xl border p-4 mb-3 bg-{{ $band }}-50 border-{{ $band }}-200">
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-{{ $band }}-700/70">Estimasi Indikasi AI</p>
                            <p class="text-3xl font-extrabold text-{{ $band }}-700">{{ $ai!==null ? number_format($ai,1).'%' : 'N/A' }}</p>
                        </div>
                        <div class="text-right text-xs text-slate-500">
                            <div>Teks: {{ $logbook->ai_text_percentage!==null ? number_format($logbook->ai_text_percentage,1).'%' : '—' }}</div>
                            <div>Gambar: {{ $logbook->ai_image_percentage!==null ? number_format($logbook->ai_image_percentage,1).'%' : '—' }}</div>
                        </div>
                    </div>
                    @php $det = $logbook->ai_detail_json ?? []; $engine = $det['engine'] ?? 'heuristic'; @endphp
                    <div class="mt-3 text-xs text-slate-500 space-y-0.5">
                        @if(isset($det['text']['words']))<div>Teks: {{ $det['text']['words'] }} kata · frasa formal {{ $det['text']['formal_hits'] ?? 0 }}× · bahasa gaul {{ $det['text']['colloquial_hits'] ?? 0 }}× · variasi kalimat (CV) {{ $det['text']['burstiness_cv'] ?? '—' }}</div>@endif
                        @if(isset($det['image']['total']))<div>Gambar: {{ $det['image']['flagged'] ?? 0 }}/{{ $det['image']['total'] }} terdeteksi tanda-tangan AI</div>@endif
                    </div>
                    <p class="mt-2 text-[11px] text-slate-400">
                        Sumber: {{ $engine === 'provider' ? 'API detektor eksternal' : 'heuristik lokal (indikatif, bukan vonis)' }} ·
                        Diperiksa {{ $logbook->ai_checked_at->format('d M Y H:i') }}.
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.logbook-review.check-ai', $logbook) }}">
                @csrf
                <button class="w-full rounded-lg bg-slate-800 text-white px-4 py-2 text-sm font-medium hover:bg-slate-900">
                    {{ $logbook->ai_checked_at ? '🔄 Periksa Ulang AI' : '🤖 Periksa AI Sekarang' }}
                </button>
            </form>
            <p class="text-[11px] text-slate-400 mt-2">Hasil akan tampil ke mahasiswa bersama feedback review.</p>
        </div>

        {{-- Pemeriksaan tata tulis (proofreader / technical editor) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5" x-data="{ open: false }">
            <div class="flex items-center justify-between mb-2">
                <h2 class="font-semibold text-slate-800">📝 Periksa Tata Tulis</h2>
                <span class="text-[11px] text-slate-400">Proofreader &amp; Technical Editor</span>
            </div>
            <p class="text-xs text-slate-400 mb-3">Ejaan baku · istilah asing · kata ganti · konsistensi · rujukan · struktur · kapitalisasi.</p>

            @php $pr = $logbook->proofread_json ?? null; @endphp
            @if($logbook->proofread_checked_at && $pr)
                @php $ps = $pr['score']; $pband = $ps===null ? 'slate' : ($ps>=80?'emerald':($ps>=60?'amber':'red')); @endphp
                <div class="rounded-xl border p-4 mb-3 bg-{{ $pband }}-50 border-{{ $pband }}-200">
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-{{ $pband }}-700/70">Skor Tata Tulis</p>
                            <p class="text-3xl font-extrabold text-{{ $pband }}-700">{{ $ps!==null ? $ps.'/100' : 'N/A' }}</p>
                        </div>
                        <div class="text-right text-xs text-slate-500">
                            <div class="font-semibold text-{{ $pband }}-700">{{ $pr['total_issues'] }} catatan</div>
                            <div>{{ $pr['checked_words'] ?? 0 }} kata</div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-600">{{ $pr['summary'] }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">Rule-based (indikatif) · Diperiksa {{ $logbook->proofread_checked_at->format('d M Y H:i') }}.</p>
                </div>

                @if(!empty($pr['issues']))
                    <div class="space-y-2 mb-3 max-h-96 overflow-y-auto pr-1">
                        @foreach($pr['issues'] as $it)
                            @php
                                $catColors = [
                                    'Ejaan'=>'rose','Istilah_Asing'=>'indigo','Kata_Ganti'=>'purple',
                                    'Konsistensi'=>'amber','Rujukan_Ambigu'=>'sky','Struktur_Paragraf'=>'teal','Capitalization'=>'slate',
                                ];
                                $cc = $catColors[$it['category']] ?? 'slate';
                            @endphp
                            <div class="rounded-lg border border-slate-200 p-3 text-xs">
                                <span class="inline-block rounded bg-{{ $cc }}-100 text-{{ $cc }}-700 px-1.5 py-0.5 font-medium mb-1.5">{{ str_replace('_',' ',$it['category']) }}</span>
                                <p class="text-slate-500 line-through decoration-rose-300">{{ $it['original_text'] }}</p>
                                <p class="text-emerald-700 mt-1">✔ {!! e($it['suggestion']) !!}</p>
                                <p class="text-slate-400 mt-1">{{ $it['explanation'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($pr['corrected_text']))
                    <button type="button" @click="open=!open" class="text-xs text-brand hover:underline mb-2" x-text="open ? '▲ Sembunyikan teks terkoreksi' : '▼ Lihat teks terkoreksi otomatis'"></button>
                    <div x-show="open" x-cloak class="rt-content text-sm border border-slate-200 rounded-lg p-3 mb-3 max-h-80 overflow-y-auto bg-slate-50">{!! $pr['corrected_text'] !!}</div>
                @endif
            @endif

            <form method="POST" action="{{ route('admin.logbook-review.proofread', $logbook) }}">
                @csrf
                <button class="w-full rounded-lg bg-slate-800 text-white px-4 py-2 text-sm font-medium hover:bg-slate-900">
                    {{ $logbook->proofread_checked_at ? '🔄 Periksa Ulang Tata Tulis' : '📝 Periksa Tata Tulis' }}
                </button>
            </form>
            <p class="text-[11px] text-slate-400 mt-2">Auto-fix hanya untuk perbaikan aman (ejaan &amp; istilah asing); rujukan/struktur perlu penilaian manusia.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <h2 class="font-semibold text-slate-800 mb-3">Keputusan Review</h2>
            <form method="POST" action="{{ route('admin.logbook-review.review', $logbook) }}" class="space-y-3">
                @csrf @method('PUT')
                <select name="status_approval" class="w-full rounded-lg border-slate-300 border px-3 py-2">
                    <option value="Approved" @selected($logbook->status_approval==='Approved')>Approved / Pass</option>
                    <option value="Revision Needed" @selected($logbook->status_approval==='Revision Needed')>Revision Needed</option>
                </select>
                <textarea name="feedback" rows="5" placeholder="Feedback / catatan revisi..." class="w-full rounded-lg border-slate-300 border px-3 py-2">{{ $logbook->feedback }}</textarea>
                <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm font-medium hover:bg-brand-dark">Simpan Review</button>
            </form>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <p class="text-sm text-slate-500 mb-1">Status saat ini</p>
            <x-status-badge :status="$logbook->status_approval" />
            <dl class="text-xs text-slate-400 mt-2 space-y-0.5">
                @if($logbook->submitted_at)<div>Submit terakhir: {{ $logbook->submitted_at->format('d M Y H:i') }}</div>@endif
                @if($logbook->reviewed_at)<div>Review terakhir: {{ $logbook->reviewed_at->format('d M Y H:i') }}</div>@endif
                <div>Jumlah revisi: {{ $logbook->revision_count }}</div>
            </dl>
        </div>

        {{-- Riwayat revisi (snapshot tersimpan) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
            <h2 class="font-semibold text-slate-800 mb-3">🕓 Riwayat Revisi</h2>
            @forelse($logbook->versions as $v)
                <div class="border-l-2 border-rose-200 pl-3 pb-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-slate-700">v{{ $v->version_number }} · <x-status-badge :status="$v->status_snapshot ?? 'Pending'" /></span>
                        <span class="text-xs text-slate-400">{{ $v->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($v->feedback_snapshot)<p class="text-xs text-slate-500 mt-1">Feedback: {{ \Illuminate\Support\Str::limit($v->feedback_snapshot, 100) }}</p>@endif
                    @if($v->author)<p class="text-[11px] text-slate-400">oleh {{ $v->author->name }}</p>@endif
                </div>
            @empty
                <p class="text-sm text-slate-400">Belum ada revisi tersimpan.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
