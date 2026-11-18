<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $module->code }} — {{ $module->title }}</title>
    <style>
        /* Cetak: tiap .page = satu lembar A4 (margin via padding), @page margin:0 agar browser
           tidak menambah header/footer sendiri (tanpa tanggal/URL). Nomor halaman ditaruh sendiri
           di kanan bawah. Font Arial agar glyph l/I tidak tampak tebal di hasil cetak. */
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #1f2937; font-size: 11.5px; line-height: 1.55; margin: 0; background: #eef0f2; }
        .sheet { background: #fff; max-width: 210mm; margin: 16px auto; padding: 15mm 16mm; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
        .page { width: 210mm; min-height: 297mm; padding: 15mm 16mm; background: #fff; margin: 0 auto 10px; position: relative; }
        .page .page-num { position: absolute; right: 16mm; bottom: 7mm; font-size: 9.5px; color: #6b7280; }
        .head { display: flex; align-items: center; gap: 14px; border-bottom: 3px solid #A61010; padding-bottom: 12px; margin-bottom: 16px; }
        .head img { height: 54px; }
        .head .t1 { font-weight: 800; font-size: 15px; color: #7E0B0B; }
        .head .t2 { font-size: 11px; color: #374151; }
        h2.section { background: #A61010; color: #fff; font-size: 12px; letter-spacing: .3px; padding: 5px 10px; margin: 18px 0 8px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        td, th { border: 1px solid #cbd5e1; padding: 5px 8px; vertical-align: top; }
        .ident th { background: #fbeaea; width: 190px; text-align: left; color: #7E0B0B; }
        .field-label { font-weight: 700; color: #7E0B0B; margin: 12px 0 4px; }
        .box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 12px; min-height: 26px; }
        .rt img { max-width: 100%; height: auto; }
        .rt table td, .rt table th { border: 1px solid #cbd5e1; padding: 5px 7px; }
        .rt ul, .rt ol { padding-left: 1.3rem; }
        .empty { color: #9ca3af; }
        .clo-row th { background: #fbeaea; color: #7E0B0B; text-align: left; }
        .toolbar { position: fixed; top: 12px; right: 12px; z-index: 9999; }
        .btn { background: #A61010; color: #fff; border: 0; border-radius: 8px; padding: 9px 18px; font-size: 13px; cursor: pointer; }
        .btn.secondary { background: #e5e7eb; color: #374151; margin-right: 6px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { margin: 0; box-shadow: none; page-break-after: always; }
            .page:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn secondary" onclick="window.close()">Tutup</button>
        <button class="btn" onclick="window.print()">Simpan / Cetak PDF</button>
    </div>

    <div class="sheet">
        <div class="head">
            <img src="{{ asset('img/modul-header-logo.png') }}" alt="Logo" onerror="this.style.display='none'">
            <div>
                <div class="t1">MODUL PROYEK TERAPAN (CAPSTONE PROJECT)</div>
                <div class="t2">PRODI D3 SISTEM INFORMASI AKUNTANSI</div>
                <div class="t2">FAKULTAS ILMU TERAPAN – UNIVERSITAS TELKOM</div>
            </div>
        </div>

        <h2 class="section">IDENTITAS MODUL</h2>
        <table class="ident">
            <tr><th>Matakuliah</th><td>Proyek Terapan (Capstone Project)</td></tr>
            <tr><th>Periode</th><td>{{ $ay?->label ?? '-' }}</td></tr>
            <tr><th>Minggu / Kode</th><td>{{ $module->week_label }}{{ $module->code ? ' · '.$module->code : '' }}</td></tr>
            <tr><th>Judul</th><td>{{ $module->title }}</td></tr>
            <tr><th>Jenis</th><td>{{ $module->workLabel() }}{{ $module->type==='assessment' ? ' (Assessment '.$module->assessment_stage.')' : '' }}</td></tr>
            <tr><th>Batasan AI</th><td>Level {{ $module->ai_policy_level }} — {{ $module->aiLevel()['name'] }}</td></tr>
        </table>

        @if($module->subClos->isNotEmpty())
            <h2 class="section">CAPAIAN (SUB-CLO)</h2>
            <table>
                <tr class="clo-row"><th style="width:90px">Sub-CLO</th><th style="width:80px">CLO</th><th style="width:80px">PLO</th><th>Deskripsi</th></tr>
                @foreach($module->subClos as $sc)
                    <tr>
                        <td>{{ $sc->code }}</td>
                        <td>{{ $sc->clo?->code ?? '-' }}</td>
                        <td>{{ $sc->clo?->plo?->code ?? '-' }}</td>
                        <td>{{ $sc->description }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <h2 class="section">{{ strtoupper($module->code ? $module->code.' : ' : '') }}{{ strtoupper($module->title) }}</h2>
        @foreach(\App\Models\Module::MATERIAL_FIELDS as $key => $label)
            <div class="field-label">{{ strtoupper($label) }}</div>
            <div class="box rt">{!! filled($module->$key) ? $module->$key : '<span class="empty">—</span>' !!}</div>
        @endforeach

        @if($module->requiresSubmission())
            <h2 class="section">FIELD PENGERJAAN (LOGBOOK)</h2>
            @foreach($module->fields() as $field)
                <div class="field-label">{{ strtoupper($field['label']) }} @if($field['required'] ?? false)*@endif
                    <span class="empty" style="font-weight:400">({{ ['richtext'=>'teks + gambar','link'=>'tautan','file'=>'unggah berkas'][$field['type'] ?? 'richtext'] ?? $field['type'] }})</span>
                </div>
                <div class="box"><span class="empty">— diisi mahasiswa —</span></div>
            @endforeach
        @endif
    </div>

    @include('logbook._paginator')
</body>
</html>
