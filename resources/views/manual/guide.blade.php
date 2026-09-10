<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $guide['title'] }}</title>
    <style>
        @page { size: A4; margin: 18mm 16mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; font-size: 11.5px; line-height: 1.6; margin: 0; background: #f1f3f5; }
        .sheet { background: #fff; max-width: 820px; margin: 20px auto; padding: 40px 46px; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
        .cover { text-align: center; border-bottom: 4px solid #A61010; padding-bottom: 22px; margin-bottom: 8px; }
        .cover img { height: 60px; margin-bottom: 12px; }
        .cover h1 { color: #7E0B0B; font-size: 22px; margin: 6px 0 4px; }
        .cover .sub { color: #6b7280; font-size: 12px; max-width: 560px; margin: 0 auto; }
        .cover .prodi { margin-top: 10px; font-size: 11px; color: #374151; font-weight: 600; }
        .toc { background: #fbeaea; border: 1px solid #f3c9c9; border-radius: 8px; padding: 14px 18px; margin: 22px 0; }
        .toc h2 { color: #7E0B0B; font-size: 13px; margin: 0 0 8px; }
        .toc ol { margin: 0; padding-left: 20px; } .toc li { margin: 2px 0; }
        .toc a { color: #A61010; text-decoration: none; }
        h2.sec { background: #A61010; color: #fff; font-size: 13px; padding: 6px 12px; border-radius: 5px; margin: 26px 0 10px; page-break-after: avoid; }
        .intro { color: #4b5563; font-style: italic; margin: 0 0 10px; }
        ol.steps { margin: 6px 0 12px; padding-left: 20px; }
        ol.steps li { margin: 5px 0; }
        .figs { display: block; margin: 10px 0 4px; }
        figure.shot { margin: 0 0 12px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; page-break-inside: avoid; }
        figure.shot img { display: block; width: 100%; }
        figure.shot .ph { display: none; align-items: center; justify-content: center; flex-direction: column; gap: 4px; min-height: 130px; background: repeating-linear-gradient(45deg,#faf5f5,#faf5f5 10px,#f5eaea 10px,#f5eaea 20px); color: #b08; text-align: center; padding: 16px; font-size: 11px; }
        figure.shot .ph small { color: #9ca3af; }
        figure.shot figcaption { font-size: 10.5px; color: #6b7280; padding: 6px 10px; background: #fafafa; border-top: 1px solid #f0f0f0; }
        .note { font-size: 10.5px; color: #9ca3af; margin-top: 26px; border-top: 1px dashed #e5e7eb; padding-top: 10px; }
        .toolbar { max-width: 820px; margin: 14px auto 0; text-align: right; }
        .btn { background: #A61010; color: #fff; border: 0; border-radius: 8px; padding: 9px 18px; font-size: 13px; cursor: pointer; }
        .btn.secondary { background: #e5e7eb; color: #374151; margin-right: 6px; text-decoration: none; padding: 9px 14px; border-radius: 8px; }
        @media print { body { background: #fff; } .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; } .toolbar { display: none; } h2.sec { page-break-before: auto; } }
    </style>
</head>
<body>
    @php $isPdf = $pdf ?? false; $logo = $logoSrc ?? asset('img/modul-header-logo.png'); @endphp
    @unless($isPdf)
    <div class="toolbar">
        <a href="{{ route('manual.index') }}" class="btn secondary">← Manual Book</a>
        <button class="btn" onclick="window.print()">🖨️ Simpan sebagai PDF</button>
    </div>
    @endunless

    <div class="sheet">
        <div class="cover">
            <img src="{{ $logo }}" alt="Logo"
                 onerror="this.style.display='none'">
            <h1>{{ $guide['title'] }}</h1>
            <p class="sub">{{ $guide['subtitle'] }}</p>
            <p class="prodi">SIM-CAPSTONE · PRODI D3 SISTEM INFORMASI AKUNTANSI · FAKULTAS ILMU TERAPAN – UNIVERSITAS TELKOM</p>
        </div>

        <div class="toc">
            <h2>Daftar Isi</h2>
            <ol>
                @foreach($guide['sections'] as $i => $s)
                    <li><a href="#sec{{ $i }}">{{ $s['title'] }}</a></li>
                @endforeach
            </ol>
        </div>

        @foreach($guide['sections'] as $i => $s)
            <section>
                <h2 class="sec" id="sec{{ $i }}">{{ $i + 1 }}. {{ $s['title'] }}</h2>
                @if(!empty($s['intro']))<p class="intro">{{ $s['intro'] }}</p>@endif
                @if(!empty($s['steps']))
                    <ol class="steps">
                        @foreach($s['steps'] as $step)<li>{{ $step }}</li>@endforeach
                    </ol>
                @endif
                @if(!empty($s['figures']))
                    <div class="figs">
                        @foreach($s['figures'] as $fig)
                            @php $src = asset('img/manual/'.$role.'/'.$fig['img']); @endphp
                            <figure class="shot">
                                @if($isPdf)
                                    <div class="ph" style="display:flex">📷 {{ $fig['cap'] }}<small>Slot tangkapan layar — silakan sisipkan screenshot layar ini</small></div>
                                @else
                                    <img src="{{ $src }}" alt="{{ $fig['cap'] }}"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="ph">🖼️ {{ $fig['cap'] }}<small>Letakkan tangkapan layar di: public/img/manual/{{ $role }}/{{ $fig['img'] }}</small></div>
                                @endif
                                <figcaption>Gambar {{ $i + 1 }}. {{ $fig['cap'] }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach

        <p class="note">Dokumen ini dihasilkan otomatis dari SIM-CAPSTONE. Untuk menyimpan sebagai PDF: klik "Simpan sebagai PDF" lalu pilih tujuan "Save as PDF". Tangkapan layar dapat ditambahkan dengan meletakkan berkas gambar pada folder yang tertera di tiap slot.</p>
    </div>
</body>
</html>
