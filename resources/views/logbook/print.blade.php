<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $module->code }} — {{ $module->title }}</title>
    <style>
        @page { size: A4; margin: 16mm 16mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; font-size: 11.5px; line-height: 1.55; margin: 0; background: #f1f3f5; }
        .sheet { background: #fff; max-width: 820px; margin: 20px auto; padding: 36px 44px; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
        .head { display: flex; align-items: center; gap: 14px; border-bottom: 3px solid #A61010; padding-bottom: 12px; margin-bottom: 16px; }
        .head img { height: 54px; }
        .head .t1 { font-weight: 800; font-size: 15px; color: #7E0B0B; }
        .head .t2 { font-size: 11px; color: #374151; }
        h2.section { background: #A61010; color: #fff; font-size: 12px; letter-spacing: .3px; padding: 5px 10px; margin: 18px 0 8px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        td, th { border: 1px solid #cbd5e1; padding: 5px 8px; vertical-align: top; }
        .ident th { background: #fbeaea; width: 190px; text-align: left; color: #7E0B0B; }
        .team th { background: #fbeaea; color: #7E0B0B; text-align: left; }
        .team td.no { width: 34px; text-align: center; }
        .team td.nim { width: 110px; }
        .field-label { font-weight: 700; color: #7E0B0B; margin: 12px 0 4px; }
        .box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 12px; min-height: 26px; }
        .rt img { max-width: 100%; height: auto; }
        .rt table { margin: 6px 0; } .rt table td, .rt table th { border: 1px solid #cbd5e1; padding: 5px 7px; }
        .rt ul { padding-left: 1.3rem; } .rt ol { padding-left: 1.3rem; }
        .rt blockquote { border-left: 3px solid #e5a3a3; margin-left: 0; padding-left: 10px; color: #6b7280; }
        .empty { color: #9ca3af; }
        .review { display: flex; gap: 16px; flex-wrap: wrap; align-items: center; }
        .badge { display: inline-block; padding: 3px 12px; border-radius: 999px; font-weight: 700; font-size: 11px; }
        .b-pass { background: #dcfce7; color: #15803d; }
        .b-rev { background: #fce7f3; color: #be185d; }
        .b-pending { background: #fef3c7; color: #b45309; }
        .b-none { background: #f1f5f9; color: #64748b; }
        .dates { margin-top: 6px; font-size: 10.5px; color: #6b7280; }
        .toolbar { max-width: 820px; margin: 14px auto 0; text-align: right; }
        .btn { background: #A61010; color: #fff; border: 0; border-radius: 8px; padding: 9px 18px; font-size: 13px; cursor: pointer; }
        .btn.secondary { background: #e5e7eb; color: #374151; margin-right: 6px; }
        @media print { body { background: #fff; } .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; } .toolbar { display: none; } }
    </style>
</head>
<body>
@php
    $statusMap = [
        'Approved' => ['b-pass', 'PASS'],
        'Revision Needed' => ['b-rev', 'REVISION NEEDED'],
        'Pending' => ['b-pending', 'MENUNGGU REVIEW'],
        'Not Started' => ['b-none', 'BELUM DIKERJAKAN'],
    ];
    $st = $logbook->status_approval ?? 'Not Started';
    [$stCls, $stLabel] = $statusMap[$st] ?? ['b-none', $st];
    $payload = $logbook->payload_json ?? [];
@endphp

    <div class="toolbar">
        <button class="btn secondary" onclick="window.close()">Tutup</button>
        <button class="btn" onclick="window.print()">🖨️ Simpan / Cetak PDF</button>
    </div>

    <div class="sheet">
        <div class="head">
            <img src="{{ asset('img/modul-header-logo.png') }}" alt="Logo">
            <div>
                <div class="t1">MODUL PROYEK TERAPAN (CAPSTONE PROJECT)</div>
                <div class="t2">PRODI D3 SISTEM INFORMASI AKUNTANSI</div>
                <div class="t2">FAKULTAS ILMU TERAPAN – UNIVERSITAS TELKOM</div>
            </div>
        </div>

        <h2 class="section">IDENTITAS UMUM</h2>
        <table class="ident">
            <tr><th>Matakuliah</th><td>Proyek Terapan (Capstone Project)</td></tr>
            <tr><th>Periode</th><td>{{ $ay?->label }}</td></tr>
            <tr><th>Kelas</th><td>{{ $team->class_name ?? '-' }}</td></tr>
            <tr><th>Nama Tim</th><td>{{ $team->team_name }}</td></tr>
            <tr><th>Nama Mitra (Studi Kasus)</th><td>{{ optional($team->topic)->partner_label ?? '-' }}</td></tr>
            @if($module->isIndividual() && ($logbook->user_id ?? null))
                <tr><th>Mahasiswa (Tugas Individu)</th><td>{{ optional($logbook->user)->identity_number }} — {{ optional($logbook->user)->name }}</td></tr>
            @endif
        </table>

        <h2 class="section">IDENTITAS TIM</h2>
        <table class="team">
            <tr><th class="no">No</th><th class="nim">NIM</th><th>Nama</th><th>Peran</th></tr>
            @foreach($team->members as $i => $m)
                <tr>
                    <td class="no">{{ $i + 1 }}</td>
                    <td class="nim">{{ $m->student->identity_number }}</td>
                    <td>{{ $m->student->name }}@if($m->student_id === $team->leader_id) <em>(Ketua)</em>@endif</td>
                    <td>{{ $m->assigned_role }}</td>
                </tr>
            @endforeach
        </table>

        <h2 class="section">{{ strtoupper($module->code ? $module->code.' : ' : '') }}{{ $module->title }}</h2>

        {{-- Materi modul (mengikuti template) --}}
        @foreach(\App\Models\Module::MATERIAL_FIELDS as $key => $label)
            <div class="field-label">{{ strtoupper($label) }}</div>
            <div class="box rt">{!! filled($module->$key) ? $module->$key : '<span class="empty">—</span>' !!}</div>
        @endforeach

        {{-- Isian mahasiswa (field dinamis) --}}
        @foreach($module->fields() as $field)
            @php $val = $payload[$field['key']] ?? null; @endphp
            <div class="field-label">{{ strtoupper($field['label']) }}</div>
            @if(($field['type'] ?? 'richtext') === 'link')
                <div class="box">@if($val)<a href="{{ $val }}">{{ $val }}</a>@else<span class="empty">—</span>@endif</div>
            @elseif(($field['type'] ?? 'richtext') === 'file')
                @php $fname = $payload[$field['key'].'__name'] ?? ($val ? basename($val) : null); @endphp
                <div class="box">@if($val)📎 Berkas terlampir: {{ $fname }}@else<span class="empty">— belum ada berkas —</span>@endif</div>
            @else
                <div class="box rt">{!! filled($val) ? $val : '<span class="empty">— belum diisi —</span>' !!}</div>
            @endif
        @endforeach

        {{-- Hasil review --}}
        <div class="field-label">HASIL REVIEW : PASS / REVISION NEEDED</div>
        <div class="box">
            <div class="review">
                <span class="badge {{ $stCls }}">{{ $stLabel }}</span>
                @if($logbook && $logbook->feedback)<span>Catatan: {{ $logbook->feedback }}</span>@endif
            </div>
            <div class="dates">
                @if($logbook && $logbook->submitted_at)Tanggal submit: {{ $logbook->submitted_at->format('d F Y, H:i') }} &nbsp;·&nbsp; @endif
                @if($logbook && $logbook->reviewed_at)Tanggal revisi/review: {{ $logbook->reviewed_at->format('d F Y, H:i') }} &nbsp;·&nbsp; @endif
                Jumlah revisi: {{ $logbook->revision_count ?? 0 }}
            </div>
        </div>

        {{-- Riwayat revisi (snapshot tiap submit/review) --}}
        @if($logbook && $logbook->relationLoaded('versions') && $logbook->versions->isNotEmpty())
            <div class="field-label">RIWAYAT REVISI</div>
            <table class="team">
                <tr><th class="no">Versi</th><th style="width:130px">Status</th><th>Catatan Review</th><th style="width:150px">Oleh</th><th style="width:120px">Tanggal</th></tr>
                @foreach($logbook->versions as $v)
                    <tr>
                        <td class="no">v{{ $v->version_number }}</td>
                        <td>{{ $v->status_snapshot ?? '—' }}</td>
                        <td>{{ $v->feedback_snapshot ? \Illuminate\Support\Str::limit($v->feedback_snapshot, 140) : '—' }}</td>
                        <td>{{ optional($v->author)->name ?? '—' }}</td>
                        <td>{{ $v->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        {{-- Indikasi penggunaan AI --}}
        @php $lvl = config('capstone.ai_levels.' . ($module->ai_policy_level ?: 1)); @endphp
        <div class="field-label">INDIKASI PENGGUNAAN AI</div>
        <div class="box">
            <div style="margin-bottom:5px;">Batasan tugas: <strong>Level {{ $module->ai_policy_level }} — {{ $lvl['name'] }}</strong> ({{ $lvl['desc'] }})</div>
            @if($logbook && $logbook->ai_checked_at)
                @php $ai = $logbook->ai_percentage; $col = $ai===null ? '#64748b' : ($ai>=60?'#be123c':($ai>=30?'#b45309':'#15803d')); @endphp
                <div class="review">
                    <span class="badge" style="background:{{ $col }}1a;color:{{ $col }};">Estimasi Indikasi AI: {{ $ai!==null ? number_format($ai,1).'%' : 'N/A' }}</span>
                    <span>Teks {{ $logbook->ai_text_percentage!==null ? number_format($logbook->ai_text_percentage,1).'%' : '—' }} · Gambar {{ $logbook->ai_image_percentage!==null ? number_format($logbook->ai_image_percentage,1).'%' : '—' }}</span>
                </div>
                <div class="dates">Diperiksa: {{ $logbook->ai_checked_at->format('d F Y, H:i') }} · <em>Estimasi indikatif (heuristik), bukan vonis.</em></div>
            @else
                <span class="empty">Belum diperiksa.</span>
            @endif
        </div>
    </div>
</body>
</html>
