<?php

/*
|--------------------------------------------------------------------------
| Konten Manual Book (Panduan Penggunaan) per role
|--------------------------------------------------------------------------
| Dipakai oleh ManualBookController@guide + view manual/guide.blade.php untuk
| menghasilkan panduan step-by-step yang dapat disimpan sebagai PDF.
|
| Tiap section: title, intro (opsional), steps[] (langkah bernomor),
| figures[] ([img => nama file, cap => keterangan]). Gambar diambil dari
| public/img/manual/<role>/<img>; bila belum ada, tampil placeholder.
*/

return [

    'superadmin' => [
        'title' => 'Manual Book — Koordinator (Superadmin)',
        'subtitle' => 'Panduan lengkap pengelolaan SIM-CAPSTONE untuk Koordinator Capstone Project.',
        'sections' => [
            [
                'title' => 'Masuk & Aktivasi Akun',
                'intro' => 'Koordinator masuk memakai email dan kata sandi yang diberikan.',
                'steps' => [
                    'Buka alamat sistem, mis. https://domain-anda/login.',
                    'Isi kolom login dengan email koordinator, lalu isi kata sandi.',
                    'Klik tombol "Masuk". Anda diarahkan ke Dashboard Koordinator.',
                    'Ganti kata sandi lewat menu Profil (pojok kanan atas) untuk keamanan.',
                ],
                'figures' => [['img' => '01-login.png', 'cap' => 'Halaman login']],
            ],
            [
                'title' => 'Dashboard Koordinator',
                'intro' => 'Ringkasan sistem: jumlah mahasiswa, tim, topik pending, dan logbook yang perlu direview.',
                'steps' => [
                    'Lihat kartu statistik di bagian atas untuk gambaran cepat.',
                    'Panel "Tim Terbaru" menampilkan tim yang baru dibuat beserta status topik.',
                    'Gunakan "Aksi Cepat" untuk lompat ke menu yang sering dipakai.',
                ],
                'figures' => [['img' => '02-dashboard.png', 'cap' => 'Dashboard koordinator']],
            ],
            [
                'title' => 'Tahun Ajaran',
                'intro' => 'Semua data (modul, tim, nilai) terikat pada tahun ajaran yang AKTIF.',
                'steps' => [
                    'Buka menu Master Data ▸ Tahun Ajaran.',
                    'Klik "Tambah", isi tahun (mis. 2025/2026) dan semester, lalu simpan.',
                    'Klik "Aktifkan" pada tahun ajaran yang ingin dipakai. Membuat tahun ajaran otomatis menyemai modul RPS, tahap A1/A2/A3, kriteria, dan aturan penalti default.',
                    'Tahun ajaran lama dapat "Arsipkan" agar tidak mengganggu data aktif.',
                ],
                'figures' => [['img' => '03-tahun-ajaran.png', 'cap' => 'Kelola tahun ajaran']],
            ],
            [
                'title' => 'Master User',
                'intro' => 'Kelola seluruh akun (koordinator & mahasiswa).',
                'steps' => [
                    'Buka Master Data ▸ Master User.',
                    'Klik "+ Tambah User" untuk membuat akun baru (isi NIM/NIP, nama, email, role, sandi).',
                    'Gunakan ikon edit/hapus pada tiap baris untuk mengubah atau menghapus akun.',
                    'Untuk import massal: klik "Import CSV", klik "Unduh template Excel/CSV", isi, lalu unggah.',
                    'Akun mahasiswa yang dibuat/diimport otomatis berstatus "wajib ganti sandi" saat login pertama.',
                ],
                'figures' => [
                    ['img' => '04-master-user.png', 'cap' => 'Daftar & tambah user'],
                    ['img' => '04b-import-user.png', 'cap' => 'Import + tombol unduh template'],
                ],
            ],
            [
                'title' => 'Master Mahasiswa',
                'intro' => 'Data mahasiswa per kelas & angkatan, termasuk import data + nilai historis. (Kolom email tidak ditampilkan — cukup NIM.)',
                'steps' => [
                    'Buka Master Data ▸ Master Mahasiswa.',
                    'Gunakan filter pencarian, angkatan, kelas, status aktivasi, dan jumlah baris per halaman.',
                    'Indikator "Belum Aktivasi" menandai mahasiswa yang masih memakai sandi default (= NIM).',
                    'Klik "Import CSV" ▸ "Unduh template Excel/CSV", isi data (kolom nilai historis opsional), lalu unggah.',
                ],
                'figures' => [['img' => '05-master-mahasiswa.png', 'cap' => 'Master mahasiswa + import']],
            ],
            [
                'title' => 'Kelola Mitra',
                'intro' => 'Master mitra studi kasus: Industri, Masyarakat Desa, atau Internal.',
                'steps' => [
                    'Buka Master Data ▸ Kelola Mitra.',
                    'Isi nama mitra (wajib), jenis, logo (opsional), dan alamat, lalu simpan.',
                    'Mitra dapat dipilih tim saat mengajukan topik.',
                ],
                'figures' => [['img' => '06-mitra.png', 'cap' => 'Kelola mitra']],
            ],
            [
                'title' => 'Kelola Topik',
                'intro' => 'Katalog topik yang ditawarkan + review topik yang diajukan tim.',
                'steps' => [
                    'Buka Master Data ▸ Kelola Topik.',
                    'Tambah topik katalog: pilih mitra, judul, fitur umum, fitur AI, deskripsi, dan tandai tersedia.',
                    'Untuk topik yang diajukan tim (mandiri/katalog), gunakan tombol review untuk Approve/Reject beserta catatan.',
                    'Gunakan filter kelas & status untuk memudahkan review.',
                ],
                'figures' => [['img' => '07-topik.png', 'cap' => 'Kelola & review topik']],
            ],
            [
                'title' => 'Manajemen Tim',
                'intro' => 'Pantau seluruh tim serta tandai kelayakan HKI.',
                'steps' => [
                    'Buka Master Data ▸ Manajemen Tim.',
                    'Gunakan filter kelas/kelompok untuk menyaring.',
                    'Aktifkan penanda "Berhak diajukan HKI" pada tim yang memenuhi syarat (masuk rekap Summary Report).',
                ],
                'figures' => [['img' => '08-tim.png', 'cap' => 'Manajemen tim']],
            ],
            [
                'title' => 'Kelola Modul, Tugas & Assessment',
                'intro' => 'Atur modul dinamis: identitas, materi, field logbook, batasan AI, jadwal, serta tugas individu.',
                'steps' => [
                    'Buka Master Data ▸ Kelola Modul.',
                    'Klik "+ Tambah Modul" atau ikon edit. Isi identitas (urutan, minggu, kode, tipe: modul/assessment/lainnya, judul).',
                    'Pilih Level Batasan AI (1–5) sesuai kebijakan tugas.',
                    'Pada kartu "Pengerjaan & Akses": centang "Buka untuk dikerjakan" agar mahasiswa dapat mengisi.',
                    'Centang "Tugas Individu" bila tugas wajib dikerjakan tiap mahasiswa; isi slot Presensi (Minggu/Sesi) agar mahasiswa otomatis HADIR saat tugas di-PASS.',
                    'Isi "Jadwal" — Dibuka mulai & Ditutup/Deadline (opsional). Setelah deadline, mahasiswa tidak bisa submit lagi.',
                    'Isi Materi Modul (Tujuan, Alat & Bahan, Aturan AI, Referensi, Deskripsi, Tugas) memakai editor teks+gambar.',
                    'Atur Field Logbook (label + tipe: Teks+Gambar / Link / Unggah Berkas PDF-Word), lalu simpan.',
                    'Di daftar modul, kolom "Akses" memuat tombol cepat Buka/Tutup dan info jadwal (Terjadwal/Berlangsung/Berakhir).',
                ],
                'figures' => [
                    ['img' => '09-kelola-modul-list.png', 'cap' => 'Daftar modul + kolom Akses & jadwal'],
                    ['img' => '09b-modul-form.png', 'cap' => 'Form modul: Pengerjaan & Akses + Jadwal'],
                ],
            ],
            [
                'title' => 'Review Logbook & Tugas',
                'intro' => 'Periksa hasil kerja tim/mahasiswa, beri keputusan, cek indikasi AI & tata tulis, cetak PDF.',
                'steps' => [
                    'Buka Penilaian ▸ Review Logbook. Gunakan filter status bila perlu.',
                    'Klik ikon mata untuk membuka detail submission (baris tugas individu menampilkan nama mahasiswa).',
                    'Klik "Periksa Indikasi AI" untuk estimasi porsi tulisan/gambar berindikasi AI.',
                    'Klik "Periksa Tata Tulis" untuk skor + daftar catatan ejaan, istilah asing, sitasi, struktur, dll.',
                    'Pilih keputusan "Approved/Pass" atau "Revision Needed", tulis feedback, lalu "Simpan Review".',
                    'Untuk tugas individu yang di-PASS, mahasiswa otomatis ditandai HADIR pada slot presensi modul.',
                    'Klik "Generate PDF" untuk mencetak logbook lengkap beserta Riwayat Revisi.',
                ],
                'figures' => [
                    ['img' => '10-review-list.png', 'cap' => 'Antrian review'],
                    ['img' => '10b-review-detail.png', 'cap' => 'Detail review + Periksa AI & Tata Tulis'],
                ],
            ],
            [
                'title' => 'Input Penilaian Assessment',
                'intro' => 'Nilai rubrik kelompok per kriteria untuk tiap tahap (A1/A2/A3).',
                'steps' => [
                    'Buka Penilaian ▸ Input Penilaian.',
                    'Pilih tahap dan tim/kelas, isi skor tiap kriteria (0–100), lalu simpan.',
                    'Nilai kelompok berlaku sama untuk seluruh anggota tim.',
                ],
                'figures' => [['img' => '11-input-nilai.png', 'cap' => 'Input penilaian']],
            ],
            [
                'title' => 'Peer 180° & Bobot/Stage',
                'intro' => 'Buka/tutup pengisian Peer 180° per tahap dan atur bobot penilaian.',
                'steps' => [
                    'Buka Penilaian ▸ Bobot & Stage untuk mengatur bobot tiap tahap, porsi peer, dan kriteria.',
                    'Aktifkan "Buka Peer" pada tahap tertentu agar mahasiswa dapat saling menilai.',
                    'Lihat hasil di Penilaian ▸ Hasil Peer 180°.',
                ],
                'figures' => [['img' => '12-stage-peer.png', 'cap' => 'Bobot, stage & peer']],
            ],
            [
                'title' => 'Penalty Absen & Presensi',
                'intro' => 'Rekam kehadiran per sesi dan atur aturan penalti berbasis jumlah hari tidak hadir.',
                'steps' => [
                    'Buka Penilaian ▸ Presensi. Pilih kelas/kelompok, klik sel untuk memutar status (kosong→H→I→S→A).',
                    'Buka Penilaian ▸ Penalty Absen untuk mengubah ambang & besaran pengurangan (mis. 4–6 hari −7, 7–10 hari −15, >10 hari = E).',
                    'Presensi tugas individu terisi otomatis saat tugas di-PASS (bila slot presensi diatur di modul).',
                ],
                'figures' => [['img' => '13-presensi.png', 'cap' => 'Presensi & penalty']],
            ],
            [
                'title' => 'Rekap Nilai & Summary Report',
                'intro' => 'Kalkulasi nilai akhir (NA), override bila perlu, dan laporan sebaran nilai per kelas.',
                'steps' => [
                    'Buka Penilaian ▸ Rekap Nilai. Klik "Hitung Ulang" untuk mengkalkulasi NA (rubrik + peer − penalti).',
                    'Gunakan "Override" untuk menyetel nilai akhir tertentu bila diperlukan (data mentah tetap tersimpan).',
                    'Buka Laporan ▸ Summary Report untuk rekap indeks nilai (A–E), rata-rata kelas, donut chart, rekap topik mitra/mandiri, tim HKI, dan rekap indikasi AI per kelas.',
                ],
                'figures' => [
                    ['img' => '14-rekap-nilai.png', 'cap' => 'Rekap & override nilai'],
                    ['img' => '14b-summary.png', 'cap' => 'Summary report'],
                ],
            ],
            [
                'title' => 'Manual Book & Mode Observasi',
                'intro' => 'Kelola panduan sistem dan lihat aplikasi sebagai mahasiswa.',
                'steps' => [
                    'Buka Panduan ▸ Manual Book untuk menamb/mengubah panduan (judul, isi teks+gambar, urutan, status terbit/draf).',
                    'Manual book yang berstatus "Terbit" dapat dibaca seluruh pengguna.',
                    'Mode Observasi: dari Manajemen Tim/Profil, gunakan "Lihat sebagai" untuk menelusuri tampilan mahasiswa tanpa logout, lalu "Kembali ke Superadmin".',
                ],
                'figures' => [['img' => '15-manual-observasi.png', 'cap' => 'Kelola manual book']],
            ],
        ],
    ],

    'mahasiswa' => [
        'title' => 'Manual Book — Mahasiswa',
        'subtitle' => 'Panduan penggunaan SIM-CAPSTONE untuk mahasiswa peserta Capstone Project.',
        'sections' => [
            [
                'title' => 'Masuk & Aktivasi Akun',
                'intro' => 'Login pertama memakai NIM sebagai username dan sandi default (= NIM).',
                'steps' => [
                    'Buka alamat sistem lalu halaman login.',
                    'Isi kolom login dengan NIM Anda, dan sandi awal = NIM.',
                    'Karena login pertama, Anda WAJIB mengganti sandi. Isi sandi baru (min. 8 karakter, tidak sama dengan NIM), lalu simpan.',
                    'Setelah itu Anda masuk ke Dashboard Mahasiswa.',
                ],
                'figures' => [
                    ['img' => '01-login.png', 'cap' => 'Halaman login'],
                    ['img' => '01b-ganti-sandi.png', 'cap' => 'Wajib ganti sandi saat pertama login'],
                ],
            ],
            [
                'title' => 'Dashboard & Notifikasi Deadline',
                'intro' => 'Ringkasan progres serta pengingat modul/tugas/assessment yang menuju batas waktu.',
                'steps' => [
                    'Lihat kartu "Menuju Deadline" di bagian atas: menampilkan item yang sedang berlangsung, sisa hari, dan status Anda.',
                    'Item yang tinggal ≤ 3 hari ditandai merah — segera kerjakan.',
                    'Klik "Kerjakan →" untuk langsung membuka logbook/tugas terkait.',
                    'Panel "Progres Modul" menampilkan persentase modul yang telah disetujui.',
                ],
                'figures' => [['img' => '02-dashboard-deadline.png', 'cap' => 'Dashboard + kartu Menuju Deadline']],
            ],
            [
                'title' => 'Tim Saya',
                'intro' => 'Ketua membentuk tim; anggota bergabung lewat ketua. Maksimal 6 orang.',
                'steps' => [
                    'Buka menu Tim Saya.',
                    'Jika Anda KETUA: buat tim, tetapkan ranah usaha (Jasa/Dagang/Manufaktur), lalu tambah anggota via NIM.',
                    'Isi peran tiap anggota (boleh lebih dari satu peran; tersedia daftar saran).',
                    'Jika Anda ANGGOTA: minta ketua menambahkan Anda ke tim.',
                ],
                'figures' => [['img' => '03-tim.png', 'cap' => 'Kelola tim & anggota']],
            ],
            [
                'title' => 'Topik & Mitra',
                'intro' => 'Pilih topik dari katalog atau ajukan topik mandiri (opsional bermitra).',
                'steps' => [
                    'Buka menu Topik & Mitra.',
                    'Pilih salah satu topik katalog, ATAU ajukan topik mandiri (isi judul, mitra sebagai teks bebas, deskripsi).',
                    'Anda dapat menamb/menghapus fitur yang ditawarkan pada topik katalog tanpa mengubah master.',
                    'Tunggu koordinator me-review (Approve/Reject) — status tampil di dashboard.',
                ],
                'figures' => [['img' => '04-topik.png', 'cap' => 'Pilih / ajukan topik']],
            ],
            [
                'title' => 'Mengisi Logbook (Tim)',
                'intro' => 'Logbook tim boleh diisi/diwakilkan oleh KETUA. Hanya bisa dikerjakan bila modul sudah dibuka & dalam jendela waktu.',
                'steps' => [
                    'Buka menu Logbook, lalu pilih modul. Perhatikan badge: 🔓 Dibuka / 🗓️ Terjadwal / ⛔ Berakhir, serta info deadline.',
                    'Pelajari Materi Modul di panel atas.',
                    'Isi tiap field: Teks+Gambar memakai editor (bisa sisip gambar), Link berupa URL, atau Unggah Berkas (PDF/Word maks 10 MB).',
                    'Klik "Submit Logbook". Status berubah menjadi "Menunggu Review".',
                    'Bila hasil "Revision Needed", perbaiki lalu submit ulang. Bila "Approved/Pass", logbook TERKUNCI (tidak bisa diubah).',
                    'Gunakan "Simpan PDF" untuk mengunduh logbook (termasuk hasil review & riwayat revisi).',
                ],
                'figures' => [
                    ['img' => '05-logbook-list.png', 'cap' => 'Daftar modul + badge status/jadwal'],
                    ['img' => '05b-logbook-form.png', 'cap' => 'Form pengisian logbook'],
                ],
            ],
            [
                'title' => 'Mengerjakan Tugas Individu',
                'intro' => 'Tugas bertanda "Tugas Individu" WAJIB dikerjakan tiap mahasiswa (tidak diwakilkan).',
                'steps' => [
                    'Buka menu Logbook, pilih item bertanda 👤 Tugas Individu.',
                    'Isi jawaban Anda sendiri pada field yang tersedia, lalu Submit.',
                    'Bila koordinator menyetujui (PASS), Anda otomatis tercatat HADIR pada presensi terkait.',
                ],
                'figures' => [['img' => '06-tugas.png', 'cap' => 'Tugas individu']],
            ],
            [
                'title' => 'Peer Evaluation 180°',
                'intro' => 'Penilaian antar anggota tim, dilakukan tiap tahap bila dibuka koordinator.',
                'steps' => [
                    'Buka menu Peer 180°. Bila tahap dibuka, isi skor tiap anggota (termasuk diri sendiri) sesuai kriteria.',
                    'Simpan penilaian. Hasil peer memengaruhi porsi nilai tahap.',
                ],
                'figures' => [['img' => '07-peer.png', 'cap' => 'Pengisian peer 180°']],
            ],
            [
                'title' => 'Nilai Saya & Manual Book',
                'intro' => 'Pantau nilai dan baca panduan kapan saja.',
                'steps' => [
                    'Buka menu Nilai Saya untuk melihat rincian nilai & indeks Anda (bila sudah dihitung koordinator).',
                    'Buka menu Manual Book untuk membaca panduan yang diterbitkan koordinator.',
                    'Ganti sandi/profil melalui menu Profil di pojok kanan atas.',
                ],
                'figures' => [['img' => '08-nilai.png', 'cap' => 'Nilai saya']],
            ],
        ],
    ],
];
