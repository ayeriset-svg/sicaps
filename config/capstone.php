<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Peran Anggota Tim (Modul 0)
    |--------------------------------------------------------------------------
    | Peran bersifat bebas (input teks) — satu orang boleh > 1 peran (pisah koma).
    | Daftar berikut hanya saran/autocomplete.
    */
    'team_roles' => [
        'Project Manager',
        'System Analyst',
        'Database Engineer',
        'UI/UX Designer',
        'Lead Developer',
        'Backend Developer',
        'Frontend Developer',
        'Technical Writing',
        'QA Tester',
        'Business Analyst',
    ],

    'team_max_members' => 6,

    'case_types' => [
        'jasa'       => 'Perusahaan Jasa',
        'dagang'     => 'Perusahaan Dagang',
        'manufaktur' => 'Perusahaan Manufaktur',
    ],

    'partner_types' => [
        'industri'        => 'Industri',
        'masyarakat_desa' => 'Masyarakat Desa',
        'internal'        => 'Internal',
    ],

    /*
    |--------------------------------------------------------------------------
    | Presensi
    |--------------------------------------------------------------------------
    | 16 minggu, 2 sesi/pertemuan per minggu = 32 sesi.
    */
    'total_weeks' => 16,
    'sessions_per_week' => 2,

    /*
    |--------------------------------------------------------------------------
    | Tipe field logbook (dipakai saat superadmin mengelola modul)
    |--------------------------------------------------------------------------
    */
    'field_types' => [
        'richtext' => 'Teks + Gambar (Rich Text)',
        'link'     => 'Tautan / Link',
        'file'     => 'Unggah Berkas (PDF/Word)',
    ],

    /*
    | Unggah berkas logbook (tipe field 'file'). Batasan MIME & ukuran.
    */
    'file_field' => [
        'mimes' => ['pdf', 'doc', 'docx'],
        'max_kb' => 10240, // 10 MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Skala Lima Level Batasan Penggunaan AI (Tabel V.5)
    |--------------------------------------------------------------------------
    | Ditetapkan per modul/tugas oleh dosen/koordinator saat kelola modul.
    */
    'ai_levels' => [
        1 => [
            'name' => 'NO AI',
            'desc' => 'Tidak boleh ada bantuan AI dalam bentuk apa pun.',
            'guide' => 'Cocok untuk menilai penguasaan kognitif dasar dan pemahaman personal.',
        ],
        2 => [
            'name' => 'AI-Assisted Idea Generation and Structuring',
            'desc' => 'AI boleh digunakan untuk brainstorming, tetapi konten akhir harus murni buatan mahasiswa.',
            'guide' => 'Cocok untuk tugas awal (drafting), tapi hasil akhir harus tanpa AI.',
        ],
        3 => [
            'name' => 'AI-Assisted Editing',
            'desc' => 'AI digunakan untuk memperbaiki kejelasan/kualitas karya, namun tidak menambahkan konten baru.',
            'guide' => 'Mahasiswa wajib melampirkan versi asli sebelum diedit AI.',
        ],
        4 => [
            'name' => 'AI Task Completion with Human Evaluation',
            'desc' => 'AI digunakan dalam sebagian tugas, tetapi mahasiswa harus melakukan evaluasi kritis terhadap hasil AI.',
            'guide' => 'Cocok untuk tugas yang mengasah refleksi, analisis, atau validasi konten.',
        ],
        5 => [
            'name' => 'Full AI',
            'desc' => 'AI digunakan secara kolaboratif untuk seluruh proses, tanpa kewajiban menyebut bagian mana yang dibantu AI.',
            'guide' => 'Cocok untuk tugas eksploratif, atau dalam konteks pengembangan produk digital berbasis AI.',
        ],
    ],

    /*
    | Frasa penanda gaya AI (indikator heuristik, EN + ID). Bukan vonis.
    */
    'ai_phrases' => [
        // English
        'furthermore', 'moreover', 'in addition', 'additionally', 'consequently', 'therefore',
        'in conclusion', 'to conclude', 'overall', 'in summary', 'it is important to note',
        'it is worth noting', 'delve into', 'leverage', 'utilize', 'in today', 'ever-evolving',
        'landscape', 'realm', 'navigating', 'firstly', 'secondly', 'thirdly', 'notably',
        'as an ai', 'plays a crucial role', 'a testament to', 'seamless', 'holistic',
        // Indonesia — konektor & register formal (khas tulisan AI)
        'selain itu', 'di samping itu', 'lebih lanjut', 'lebih jauh', 'oleh karena itu', 'oleh sebab itu',
        'dengan demikian', 'maka dari itu', 'sehubungan dengan', 'sebagai kesimpulan', 'dapat disimpulkan',
        'kesimpulannya', 'secara keseluruhan', 'pada dasarnya', 'pada hakikatnya', 'perlu dicatat',
        'penting untuk dicatat', 'penting untuk diperhatikan', 'perlu diperhatikan', 'hal ini menunjukkan',
        'hal tersebut menunjukkan', 'dalam hal ini', 'dalam konteks ini', 'dalam era', 'di era',
        'seiring dengan', 'seiring perkembangan', 'tidak dapat dipungkiri', 'tak dapat dipungkiri',
        'memainkan peran penting', 'berperan penting', 'merupakan salah satu', 'dalam rangka',
        'sebagaimana diketahui', 'sebagaimana', 'adapun', 'selanjutnya', 'pertama-tama', 'di sisi lain',
        'namun demikian', 'meskipun demikian', 'dengan kata lain', 'sebagai contoh', 'antara lain',
        'dengan adanya', 'keberadaan', 'implementasi', 'optimalisasi', 'efektivitas', 'efisiensi',
        'signifikan', 'komprehensif', 'holistik', 'krusial', 'esensial', 'fundamental', 'integral',
        'inovatif', 'solutif', 'dinamis', 'transformasi digital', 'di tengah', 'guna meningkatkan',
        'dalam upaya', 'berkontribusi', 'berdampak', 'berbasis', 'berlandaskan', 'mengoptimalkan',
    ],

    /*
    | Penanda gaya INFORMAL / gaul (sinyal kuat tulisan MANUSIA/mahasiswa).
    | Kemunculannya menurunkan estimasi indikasi AI.
    */
    'ai_colloquial' => [
        ' nih', ' sih', 'banget', ' bgt', ' gak', ' ga ', 'nggak', ' ngga', 'enggak', ' gk ',
        'udah', ' udh', ' yg ', ' dgn ', ' dr ', ' krn', ' tp ', ' jd ', ' blm', ' trs', ' cm ',
        'kayak', 'kaya ', ' gitu', ' gini', ' aja', ' ajah', ' dong', ' deh', ' kok', ' lho', ' loh',
        ' klo', ' kalo', ' gua', ' gue', ' gw ', ' lu ', ' lo ', 'emang', 'doang', 'bikin', 'ngerjain',
        'ngoding', 'nyari', ' tau ', 'gimana', 'kenapa sih', ' asik', ' asyik', 'mager', 'baper',
        'nanya', 'ngasih', 'ngeliat', ' liat', 'pengen', 'pengin', 'yaudah', 'ya udah', ' oke', ' okey',
        'wkwk', 'haha', 'hehe', 'hmm', ' btw', ' sm ', ' pas ', ' banget', ' seru', ' capek', ' ribet',
    ],

    /*
    | Tanda-tangan (signature) generator gambar AI pada metadata/byte.
    */
    'ai_image_signatures' => [
        'midjourney', 'stable diffusion', 'stablediffusion', 'stability.ai', 'dall-e', 'dall e',
        'dalle', 'openai', 'adobe firefly', 'firefly', 'craiyon', 'novelai', 'leonardo.ai',
        'leonardo ai', 'imagen', 'made with google ai', 'gemini', 'c2pa', 'contentauthenticity',
        'content credentials', 'ai generated', 'generated by ai', 'sref', 'nijijourney',
        'automatic1111', 'comfyui', 'invokeai', 'parameters\x00',
    ],

    /*
    |--------------------------------------------------------------------------
    | Proofreader / Technical Editor (pemeriksa TATA TULIS)
    |--------------------------------------------------------------------------
    | Kamus & daftar untuk 7 checklist pemeriksaan tata tulis ilmiah.
    | Semua pemeriksaan bersifat rule-based (deterministik).
    */
    'proofreader' => [

        // (1) EJAAN — kata tidak baku => bentuk baku (KBBI). Kunci = salah, nilai = benar.
        'nonbaku' => [
            'praktek' => 'praktik', 'analisa' => 'analisis', 'menganalisa' => 'menganalisis',
            'sistim' => 'sistem', 'resiko' => 'risiko', 'aktifitas' => 'aktivitas',
            'efektifitas' => 'efektivitas', 'kreatifitas' => 'kreativitas', 'produktifitas' => 'produktivitas',
            'sportifitas' => 'sportivitas', 'apotik' => 'apotek', 'jadual' => 'jadwal',
            'nasehat' => 'nasihat', 'obyek' => 'objek', 'subyek' => 'subjek', 'propinsi' => 'provinsi',
            'standarisasi' => 'standardisasi', 'frekwensi' => 'frekuensi', 'kwalitas' => 'kualitas',
            'kwantitas' => 'kuantitas', 'kwitansi' => 'kuitansi', 'kwesioner' => 'kuesioner',
            'hakekat' => 'hakikat', 'ijin' => 'izin', 'karir' => 'karier', 'komplek' => 'kompleks',
            'kongkrit' => 'konkret', 'konkrit' => 'konkret', 'silahkan' => 'silakan', 'seksama' => 'saksama',
            'sekedar' => 'sekadar', 'himbau' => 'imbau', 'menghimbau' => 'mengimbau', 'trampil' => 'terampil',
            'kordinasi' => 'koordinasi', 'menejemen' => 'manajemen', 'managemen' => 'manajemen',
            'difinisi' => 'definisi', 'katagori' => 'kategori', 'hirarki' => 'hierarki',
            'atlit' => 'atlet', 'antri' => 'antre', 'nafas' => 'napas', 'faham' => 'paham',
            'memfaham' => 'memahami', 'hutang' => 'utang', 'lembab' => 'lembap', 'syaraf' => 'saraf',
            'isteri' => 'istri', 'pebruari' => 'februari', 'nopember' => 'november',
            'perduli' => 'peduli', 'rejeki' => 'rezeki', 'jaman' => 'zaman', 'ekstrim' => 'ekstrem',
            'aktifis' => 'aktivis', 'creatif' => 'kreatif', 'aktifas' => 'aktivitas',
            'teoritis' => 'teoretis', 'metoda' => 'metode', 'diagnosa' => 'diagnosis',
            'hipotesa' => 'hipotesis', 'sintesa' => 'sintesis', 'aktual' => 'aktual',
            'formil' => 'formal', 'komersil' => 'komersial', 'detil' => 'detail',
            'prosentase' => 'persentase', 'prosen' => 'persen', 'himbauan' => 'imbauan',
            'infra struktur' => 'infrastruktur', 'terlanjur' => 'telanjur', 'dikarenakan' => 'karena',
            'gimana' => 'bagaimana', 'kenapa' => 'mengapa', 'bikin' => 'membuat',
            'ngga' => 'tidak', 'nggak' => 'tidak', 'gak' => 'tidak', 'kalo' => 'kalau',
            'entar' => 'nanti', 'udah' => 'sudah', 'tapi' => 'tetapi', 'ngasih' => 'memberi',
        ],

        // (1b) Awalan "di-" yang HARUS dipisah (di + kata tempat/keterangan).
        'di_should_separate' => [
            'disini', 'disana', 'disitu', 'dimana',
            'dirumah', 'dikelas', 'dikampus', 'dikantor', 'diatas', 'dibawah', 'didalam',
            'diluar', 'didepan', 'dibelakang', 'disamping', 'disekitar', 'ditengah',
            'diantara', 'disebelah', 'dipinggir', 'diseluruh', 'diseberang',
        ],

        // (1d) Awalan "ke-" yang HARUS dipisah (ke + tempat/arah). Hati-hati: hanya bentuk aman
        //      (bukan kata utuh seperti "kedua", "keluar", "kembali", "kepala").
        'ke_should_separate' => [
            'kesana', 'kesini', 'kesitu', 'kemana', 'kerumah', 'kekampus', 'kesekolah',
            'kekantor', 'kedalam', 'keatas', 'kebawah', 'kedepan', 'kebelakang', 'kesamping',
            'keseluruh', 'kepihak', 'kearah', 'kekiri', 'kekanan',
        ],

        // (1c) Kata kerja pasif yang HARUS digabung dengan awalan "di-" (kesalahan "di lakukan").
        'di_verbs' => [
            'lakukan', 'buat', 'gunakan', 'kembangkan', 'rancang', 'analisis', 'uji', 'simpan',
            'proses', 'tampilkan', 'hasilkan', 'terapkan', 'implementasikan', 'sajikan', 'jelaskan',
            'bangun', 'olah', 'hitung', 'tentukan', 'tetapkan', 'peroleh', 'dapatkan', 'sesuaikan',
            'perbaiki', 'desain', 'ambil', 'pilih', 'tambahkan', 'kurangi', 'hapus', 'ubah',
            'kelola', 'atur', 'buatkan', 'gunakanlah', 'perlukan', 'butuhkan', 'harapkan',
            'gunakan', 'jalankan', 'pasang', 'unggah', 'unduh', 'input', 'inputkan',
        ],

        // (2) ISTILAH ASING — istilah Inggris/teknis yang sebaiknya dicetak miring (italic)
        //     bila belum di-<em>/<i>. Frasa multi-kata & kata tunggal yang MASIH asing di ragam
        //     ilmiah Indonesia (kata serapan baku seperti "digital/visual/modern" TIDAK dimasukkan).
        //     Dicocokkan case-insensitive; frasa terpanjang diprioritaskan.
        'foreign_terms' => [
            // frasa multi-kata (diprioritaskan)
            'virtual reality', 'augmented reality', 'mixed reality', 'extended reality',
            'machine learning', 'deep learning', 'artificial intelligence', 'natural language processing',
            'data science', 'big data', 'cloud computing', 'internet of things', 'computer vision',
            'user interface', 'user experience', 'use case', 'sequence diagram', 'class diagram',
            'activity diagram', 'entity relationship diagram', 'business process', 'source code',
            'open source', 'real time', 'real-time', 'front end', 'front-end', 'back end', 'back-end',
            'black box', 'white box', 'unit testing', 'landing page', 'error handling', 'soft launch',
            // kata tunggal
            'user', 'interface', 'database', 'input', 'output', 'framework', 'reality', 'virtual',
            'augmented', 'immersive', 'rendering', 'spatial', 'testing', 'deployment', 'deploy',
            'hosting', 'website', 'web', 'software', 'hardware', 'update', 'upgrade', 'backend',
            'frontend', 'login', 'logout', 'dashboard', 'report', 'online', 'offline', 'username',
            'password', 'browser', 'server', 'client', 'tools', 'tool', 'link', 'file', 'folder',
            'upload', 'download', 'default', 'setting', 'settings', 'feature', 'features', 'bug',
            'error', 'debug', 'debugging', 'commit', 'repository', 'repo', 'branch', 'merge', 'query',
            'record', 'field', 'form', 'button', 'layout', 'mockup', 'wireframe', 'prototype',
            'prototyping', 'flowchart', 'workflow', 'requirement', 'stakeholder', 'scope', 'timeline',
            'mapping', 'library', 'plugin', 'endpoint', 'request', 'response', 'session', 'cookie',
            'cache', 'runtime', 'compile', 'build', 'dataset', 'prompt', 'prompting', 'marketplace',
            'responsive', 'usability', 'coding', 'setup', 'install', 'export', 'import', 'preview',
            'view', 'controller', 'developer', 'frontend', 'framework', 'deployment', 'e-commerce',
            'gateway', 'payment', 'dropdown', 'checkbox', 'sidebar', 'navbar', 'header', 'footer',
            'template', 'render', 'scalable', 'insight', 'tracking', 'engagement', 'benchmark',
        ],

        // (2b) AKRONIM teknis asing (dicocokkan CASE-SENSITIVE huruf besar agar tidak menabrak
        //      kata Indonesia). "AI" sengaja DIKECUALIKAN karena istilah domain yang sangat sering.
        'foreign_acronyms' => [
            'VR', 'XR', 'AR', 'UI', 'UX', 'API', 'ERD', 'BPMN', 'UML', 'CRUD', 'SQL', 'NoSQL',
            'HTML', 'CSS', 'JS', 'JSON', 'XML', 'REST', 'MVC', 'ORM', 'OOP', 'SDK', 'IDE', 'CLI',
            'GUI', 'URL', 'HTTP', 'HTTPS', 'DBMS', 'CDN', 'DOM', 'SaaS', 'IoT', 'ML', 'NLP',
        ],

        // (4) KONSISTENSI — pasangan istilah (Inggris vs padanan Indonesia). Bila KEDUANYA
        //     dipakai dalam satu dokumen => tidak konsisten.
        'consistency_pairs' => [
            ['user', 'pengguna'],
            ['database', 'basis data'],
            ['input', 'masukan'],
            ['output', 'keluaran'],
            ['software', 'perangkat lunak'],
            ['hardware', 'perangkat keras'],
            ['report', 'laporan'],
            ['file', 'berkas'],
            ['update', 'pemutakhiran'],
            ['website', 'situs web'],
            ['download', 'unduh'],
            ['upload', 'unggah'],
            ['feature', 'fitur'],
            ['button', 'tombol'],
            ['interface', 'antarmuka'],
        ],

        // (7) KAPITALISASI JUDUL — kata hubung/kata depan yang HARUS huruf kecil di dalam
        //     judul/subjudul (kecuali kata pertama).
        'title_lowercase' => [
            'dan', 'atau', 'yang', 'di', 'ke', 'dari', 'untuk', 'pada', 'dengan', 'dalam',
            'oleh', 'serta', 'tanpa', 'sebagai', 'agar', 'atas', 'bagi', 'demi', 'guna',
            'hingga', 'jika', 'karena', 'kecuali', 'lewat', 'maka', 'melalui', 'meski',
            'per', 'sampai', 'sejak', 'selain', 'sementara', 'tentang', 'terhadap', 'tetapi',
            'via', 'a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in', 'of', 'on', 'or',
            'the', 'to', 'up', 'with', 'nor',
        ],

        // Ambang batas struktur.
        'ideal_paragraph_sentences' => 5, // paragraf ideal: 1 kalimat utama + 4 penjelas
        'min_paragraph_sentences' => 3,   // < ini => "paragraf sebatang kara"
        'min_paragraph_words' => 15,      // hanya periksa paragraf substansial (>= ini kata)
        'main_paragraph_words' => 12,     // ambang "paragraf utama" utk cek alignment justify
    ],

    /*
    |--------------------------------------------------------------------------
    | Rencana Modul & Pertemuan (RPS Capstone Project - default seed)
    |--------------------------------------------------------------------------
    | Dikelola dinamis oleh superadmin via menu Kelola Modul. Nilai di sini
    | hanya seed awal per tahun ajaran.
    | type: module (punya logbook) | assessment (milestone nilai) | other (logbook)
    */
    'default_modules' => [
        ['order' => 1,  'week_label' => 'W1',   'code' => 'M0',  'type' => 'module',     'stage' => null, 'title' => 'Modul 0: Pengenalan & Pembentukan Tim', 'description' => 'Pembentukan tim, pembagian peran (PM, UI/UX, Dev, QA), penetapan ranah usaha (Jasa/Dagang/Manufaktur).'],
        ['order' => 2,  'week_label' => 'W2',   'code' => 'M1',  'type' => 'module',     'stage' => null, 'title' => 'Modul 1: Analisis Studi Kasus',        'description' => 'Analisis kebutuhan, business pain points, dan scope modul SIA yang dibangun.'],
        ['order' => 3,  'week_label' => 'W3.1', 'code' => 'M2',  'type' => 'module',     'stage' => null, 'title' => 'Modul 2: Rich Picture',                'description' => 'Rich picture kondisi AS-IS & TO-BE beserta aktor utama.'],
        ['order' => 4,  'week_label' => 'W3.2', 'code' => 'A1',  'type' => 'assessment', 'stage' => 'A1', 'title' => 'Assessment 1: Development Plan',       'description' => 'Slide rencana penawaran topik proyek (pitching) untuk menarik investor.'],
        ['order' => 5,  'week_label' => 'W4',   'code' => 'M3',  'type' => 'module',     'stage' => null, 'title' => 'Modul 3: BPMN',                        'description' => 'Business Process Model & Notation per siklus akuntansi.'],
        ['order' => 6,  'week_label' => 'W5',   'code' => 'M4',  'type' => 'module',     'stage' => null, 'title' => 'Modul 4: Use Case Diagram',            'description' => 'Use case diagram, matriks aktor, dan skenario use case.'],
        ['order' => 7,  'week_label' => 'W6',   'code' => 'M5',  'type' => 'module',     'stage' => null, 'title' => 'Modul 5: ERD & Class Diagram',         'description' => 'ERD (PK, FK, kardinalitas), class diagram, dan kamus data.'],
        ['order' => 8,  'week_label' => 'W7',   'code' => 'M6',  'type' => 'module',     'stage' => null, 'title' => 'Modul 6: Sequence Diagram',            'description' => 'Sequence diagram master, transaksi, dan laporan.'],
        ['order' => 9,  'week_label' => 'W8',   'code' => 'M7',  'type' => 'module',     'stage' => null, 'title' => 'Modul 7: UI / Mockup / Wireframe',     'description' => 'Prototype UI (Figma/XD) master, transaksi, dan laporan.'],
        ['order' => 10, 'week_label' => 'W9',   'code' => 'A2',  'type' => 'assessment', 'stage' => 'A2', 'title' => 'Assessment 2: Video Prototipe Produk', 'description' => 'Video prototipe produk (logo, identitas sistem, fitur, kreatifitas).'],
        ['order' => 11, 'week_label' => 'W10',  'code' => 'M8',  'type' => 'module',     'stage' => null, 'title' => 'Modul 8: Prompting',                   'description' => 'Pemanfaatan AI/prompting untuk percepatan pengembangan.'],
        ['order' => 12, 'week_label' => 'W11',  'code' => 'UP1', 'type' => 'other',      'stage' => null, 'title' => 'Update Progress Coding (1)',           'description' => 'Laporan progres coding master & transaksi.'],
        ['order' => 13, 'week_label' => 'W12',  'code' => 'M9',  'type' => 'module',     'stage' => null, 'title' => 'Modul 9: Hosting',                     'description' => 'Deployment aplikasi ke hosting / URL live.'],
        ['order' => 14, 'week_label' => 'W13',  'code' => 'UP2', 'type' => 'other',      'stage' => null, 'title' => 'Update Progress Coding (2)',           'description' => 'Laporan progres coding laporan keuangan & testing.'],
        ['order' => 15, 'week_label' => 'W14',  'code' => 'A3',  'type' => 'assessment', 'stage' => 'A3', 'title' => 'Assessment 3: Expo Final',             'description' => 'Pameran karya (expo), live demo, dan voting.'],
        ['order' => 16, 'week_label' => 'W15',  'code' => 'DPPL','type' => 'other',      'stage' => null, 'title' => 'DPPL',                                 'description' => 'Dokumen Perancangan Perangkat Lunak (final).'],
        ['order' => 17, 'week_label' => 'W16',  'code' => 'FIN', 'type' => 'other',      'stage' => null, 'title' => 'Finalisasi Akhir Keseluruhan',         'description' => 'Finalisasi seluruh berkas: manual book, source code + database, dokumentasi.'],
    ],

    /*
    | Field default untuk modul logbook (dipakai saat seed & saat modul baru dibuat).
    | Superadmin dapat menambah/mengubah field per modul.
    */
    'default_logbook_fields' => [
        ['key' => 'uraian', 'label' => 'Uraian Pengerjaan / Dokumentasi', 'type' => 'richtext', 'required' => true],
        ['key' => 'tautan', 'label' => 'Tautan (Repository / Figma / Video / Hosting)', 'type' => 'link', 'required' => false],
    ],

    /*
    |--------------------------------------------------------------------------
    | Assessment Stages & Bobot (default seed, dikelola superadmin)
    |--------------------------------------------------------------------------
    | Tiap stage: bobot terhadap nilai akhir, porsi peer 180 di dalam stage,
    | serta status buka/tutup peer. Nilai stage = rubrik kelompok*(1-peer) + peer*peer.
    */
    'default_stages' => [
        [
            'code' => 'A1', 'name' => 'Assessment 1 (Development Plan)', 'weight' => 30.00, 'peer_weight' => 10.00, 'order' => 1,
            'criteria' => ['Ruang lingkup', 'Fitur', 'Fitur AI', 'Proses bisnis', 'Luaran sistem', 'Metode pengembangan', 'Pembagian jobdesc', 'Timeline', 'Penguasaan'],
        ],
        [
            'code' => 'A2', 'name' => 'Assessment 2 (Video Prototipe)', 'weight' => 30.00, 'peer_weight' => 10.00, 'order' => 2,
            'criteria' => ['Logo', 'Identitas sistem', 'Fitur', 'Kreatifitas'],
        ],
        [
            'code' => 'A3', 'name' => 'Assessment 3 (Expo Final)', 'weight' => 40.00, 'peer_weight' => 10.00, 'order' => 3,
            'criteria' => ['Performansi pada expo', 'Jumlah votes'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kriteria Peer Evaluation 180°
    |--------------------------------------------------------------------------
    */
    'peer_criteria' => [
        'score_communication'  => 'Komunikasi & Kerjasama Tim',
        'score_contribution'   => 'Kontribusi Pengerjaan Kode / Dokumen',
        'score_responsibility' => 'Tanggung Jawab & Ketepatan Waktu',
        'score_attendance'     => 'Kehadiran Diskusi Internal Tim',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attendance Penalty Rules (default seed, berbasis jumlah HARI tidak hadir)
    |--------------------------------------------------------------------------
    | type: none | points_deduction (kurangi poin dari NA) | fail (Grade E)
    | max_days null = tak terhingga.
    */
    'default_penalty_rules' => [
        ['label' => 'Ringan (4-6 hari tidak hadir)', 'min_days' => 4,  'max_days' => 6,    'type' => 'points_deduction', 'points' => 7],
        ['label' => 'Sedang (7-10 hari tidak hadir)', 'min_days' => 7, 'max_days' => 10,   'type' => 'points_deduction', 'points' => 15],
        ['label' => 'Berat (>10 hari tidak hadir)',   'min_days' => 11, 'max_days' => null, 'type' => 'fail',             'points' => 0],
    ],

    /*
    |--------------------------------------------------------------------------
    | Skala Indeks Nilai Akhir (NA)
    |--------------------------------------------------------------------------
    | NA > 85 = A ; 75 < NA <= 85 = AB ; 65 < NA <= 75 = B ; 60 < NA <= 65 = BC ;
    | 50 < NA <= 60 = C ; 40 < NA <= 50 = D ; NA <= 40 = E
    | Dievaluasi menurun dgn operator '>' (strictly greater than).
    */
    'grade_scale' => [
        ['gt' => 85, 'letter' => 'A'],
        ['gt' => 75, 'letter' => 'AB'],
        ['gt' => 65, 'letter' => 'B'],
        ['gt' => 60, 'letter' => 'BC'],
        ['gt' => 50, 'letter' => 'C'],
        ['gt' => 40, 'letter' => 'D'],
        ['gt' => -1, 'letter' => 'E'],
    ],

    'grade_index' => ['A', 'AB', 'B', 'BC', 'C', 'D', 'E'],
];
