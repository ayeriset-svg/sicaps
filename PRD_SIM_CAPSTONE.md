# Product Requirement Document (PRD)
## SIM-CAPSTONE: Sistem Informasi Manajemen Capstone Project

**Program Studi:** D3 Sistem Informasi Akuntansi
**Versi:** 2.0 (Revisi mengikuti RPS)
**Tanggal:** 16 Agustus 2026
**Status:** Implemented
**Tech Stack Terpasang:** Laravel 10 (PHP 8.1), Blade + Controller/Service Layer, MySQL, Tailwind CSS (CDN), Alpine.js (CDN)
**Model Integrasi System:** LMS & Digital Progress Tracker (16 Minggu, 2 sesi/minggu) — modul praktikum dinamis + 3 Assessment Milestones.

> **Catatan versi 2.0:** Dokumen ini merevisi PRD v1.0 mengikuti RPS mata kuliah. Perubahan utama: (1) role dosen dihapus — hanya **Superadmin** & **Mahasiswa**; (2) rencana 16 minggu (2 pertemuan/minggu) dengan **modul dinamis yang dikelola superadmin**; (3) model penilaian baru berbasis **3 Assessment (30/30/40%)** + **Peer 180° 10% per assessment** + **penalti kehadiran berbasis hari**; (4) penambahan Master Mitra, Kelola Topik (katalog+mandiri), Master Mahasiswa + import histori, dan Summary Report; (5) skala indeks nilai baru.
>
> Target awal (Laravel 12 + Livewire 3) tidak dipakai karena lingkungan PHP 8.1/Laravel 10; arsitektur Blade + Service Layer setara secara fungsional.

---

## 1. Executive Summary

SIM-CAPSTONE adalah platform tersentralisasi untuk mata kuliah Capstone Project D3 SIA, berperan sebagai *Digital Progress Tracker* dan *LMS*. Sistem memfasilitasi pembentukan tim, pengajuan topik (katalog/mandiri) dengan mitra, digital logbook mingguan yang dinamis, 3 tahap assessment, penilaian 180° per tahap, dan mesin kalkulasi nilai fleksibel dengan penalti kehadiran serta pelaporan lintas angkatan.

### Tujuan Sistem
1. Pendaftaran tim, pembagian peran, dan pengajuan topik (katalog superadmin **atau** mandiri) dengan mitra.
2. Digital Logbook mingguan yang **dinamis** — jumlah modul, materi, dan field input dikelola superadmin.
3. Mengelola 3 Assessment Milestones (Development Plan, Video Prototipe, Expo Final).
4. *Flexible Assessment Engine*: bobot per assessment, Peer 180° per tahap, penalti kehadiran berbasis hari, kalkulasi ulang real-time.
5. Master data (mahasiswa/mitra/topik) lintas angkatan + import data histori + Summary Report per kelas.

---

## 2. User Roles & Access Control

Hanya **2 role**: Superadmin dan Mahasiswa (role Dosen dihapus; seluruh fungsi review/penilaian dosen dialihkan ke Superadmin/Koordinator).

* **Mahasiswa (Anggota Tim):** logbook (view), evaluasi peer 180° per tahap (bila dibuka), memantau status & nilai.
* **Mahasiswa (Ketua Tim):** seluruh hak anggota + membuat/mengedit tim, memilih/mengajukan topik, submit Digital Logbook tim.
* **Superadmin (Koordinator Capstone):** kontrol penuh — Master Data (User, Mahasiswa, Mitra, Topik), Manajemen Tahun Ajaran, **Kelola Modul dinamis**, review & feedback logbook, input nilai assessment, buka/tutup Peer 180° per tahap, pengaturan bobot & penalti, presensi, rekap & override nilai, Summary Report.

### Matrix Hak Akses

| Fitur | Mahasiswa (Member) | Mahasiswa (Leader) | Superadmin |
| :--- | :---: | :---: | :---: |
| Kelola Tim (Modul 0) | View | Create/Edit | Full |
| Pilih/Ajukan Topik & Mitra | View | Choose/Submit | Katalog + Approve |
| Submit Logbook Mingguan | View | Submit/Edit | Review + Feedback |
| Kelola Modul (jumlah, materi, field) | — | — | Full |
| Input Nilai Assessment | View hasil | View hasil | Full |
| Peer 180° per tahap | Isi (bila dibuka) | Isi (bila dibuka) | Buka/Tutup + Lihat hasil |
| Bobot, Penalti, Presensi | — | — | Full |
| Rekap & Override Nilai | View milik sendiri | View milik sendiri | Full |
| Master Mahasiswa/Mitra/Topik + Import | — | — | Full |
| Summary Report | — | — | Full |

---

## 3. Rencana 16 Minggu (RPS)

Setiap minggu terdiri dari **2 pertemuan/sesi**. Modul bersifat **dinamis** — superadmin dapat mengubah urutan, judul, deskripsi, dan definisi field logbook. Konfigurasi default (seed):

| Minggu | Kode | Jenis | Judul |
| :--- | :--- | :--- | :--- |
| W1 | M0 | module | Modul 0: Pengenalan & Pembentukan Tim |
| W2 | M1 | module | Modul 1: Analisis Studi Kasus |
| W3.1 | M2 | module | Modul 2: Rich Picture |
| W3.2 | A1 | assessment | **Assessment 1: Development Plan** |
| W4 | M3 | module | Modul 3: BPMN |
| W5 | M4 | module | Modul 4: Use Case Diagram |
| W6 | M5 | module | Modul 5: ERD & Class Diagram |
| W7 | M6 | module | Modul 6: Sequence Diagram |
| W8 | M7 | module | Modul 7: UI / Mockup / Wireframe |
| W9 | A2 | assessment | **Assessment 2: Video Prototipe Produk** |
| W10 | M8 | module | Modul 8: Prompting |
| W11 | UP1 | other | Update Progress Coding (1) |
| W12 | M9 | module | Modul 9: Hosting |
| W13 | UP2 | other | Update Progress Coding (2) |
| W14 | A3 | assessment | **Assessment 3: Expo Final** |
| W15 | DPPL | other | DPPL |
| W16 | FIN | other | Finalisasi Akhir Keseluruhan |

**Field logbook** (dinamis per modul, dikelola superadmin). Tipe field:
* `richtext` — **Teks + Gambar** (rich text; gambar disisipkan inline sebagai base64 sehingga logbook dapat langsung dicetak PDF).
* `link` — input URL (repository, Figma, video, hosting, dll).
* `file` — **Unggah Berkas (PDF/Word)** — mahasiswa mengunggah dokumen `.pdf`/`.doc`/`.docx` (maks 10 MB); berkas disimpan pada private disk (`logbooks/{team}/…`) dan diunduh via route terotorisasi. Diaktifkan superadmin di Kelola Modul.

Default tiap modul: 1 field richtext ("Uraian Pengerjaan / Dokumentasi") + 1 field link.

---

## 4. Epics & Functional Requirements

### EPIC 1 — Master Data & Academic Year
* **Tahun Ajaran & Angkatan:** buat/aktifkan/arsipkan. Membuat tahun ajaran otomatis menyemai modul RPS, stage A1/A2/A3 + kriteria, dan aturan penalti default.
* **Master User & Master Mahasiswa:** CRUD + **import CSV**. Master Mahasiswa lengkap dengan **kelas & angkatan** untuk pemantauan per kelas. Import mendukung **data + nilai historis** (kolom `year,semester,final_score,grade_letter` opsional → membuat tahun ajaran arsip + final grade).

### EPIC 2 — Master Mitra & Kelola Topik
* **Master Mitra (FR-2.1):** jenis **Industri / Masyarakat Desa / Internal**; data: nama (wajib), logo (opsional), alamat (opsional), contact person.
* **Kelola Topik (FR-2.2):** katalog topik yang **ditawarkan** superadmin (mitra, judul, **fitur umum**, **fitur AI**, deskripsi). Katalog dapat ditandai tersedia/tidak.
* **Pemilihan Topik oleh Tim (Modul 1):** ketua tim boleh **memilih dari katalog** ATAU **mengajukan topik mandiri** (opsional bermitra). Superadmin melakukan **approve/reject** dengan catatan.

### EPIC 3 — Dynamic Digital Logbook & Module Manager
* **Kelola Modul (FR-3.1):** superadmin mengatur jumlah modul, urutan, judul, deskripsi/materi, jenis (module/assessment/other), dan **daftar field** logbook (label, tipe **richtext/link/file**, wajib/opsional). Tipe `file` mengaktifkan **unggah dokumen PDF/Word** oleh mahasiswa. Tiap modul/tugas juga punya penanda **Buka/Tutup** (`is_open`) dan **Tugas Individu** (`is_individual`) beserta slot presensi opsional.
* **Digital Logbook (FR-3.2):** form logbook di-render dinamis sesuai definisi field modul. Ketua tim submit; status `Not Started → Pending → (Revision Needed | Approved)`; versioning payload otomatis pada tiap submit ulang. **Setelah `Approved` (PASS), logbook TERKUNCI** — editor tidak lagi ditampilkan ke mahasiswa dan submit ditolak server (read-only).
* **Gate Buka/Tutup (FR-3.6):** modul/logbook **dan** tugas hanya dapat dikerjakan mahasiswa setelah **dibuka superadmin** (`is_open`). Saat tertutup, mahasiswa tetap dapat melihat materi tetapi form pengerjaan disembunyikan; submit ditolak server (403). Toggle cepat tersedia di daftar Kelola Modul (kolom Akses).
* **Tugas Individu / Assignment (FR-3.7):** selain logbook tim, superadmin dapat menandai modul sebagai **Tugas Individu** (`is_individual`). Fitur & tools sama persis dengan logbook (field dinamis, richtext/link/file, cetak PDF, cek AI, proofreader, riwayat revisi) — **bedanya hanya model pengerjaan**: logbook tim boleh **diwakilkan ketua**, sedangkan tugas **wajib dikerjakan tiap mahasiswa secara individu** (submission per-user; `module_logbooks.user_id`). Bila mahasiswa submit tugas lalu superadmin men-**PASS**, mahasiswa **otomatis ditandai HADIR** pada slot presensi (Minggu/Sesi) yang diset di modul; bila status dibatalkan dari PASS, penanda hadir otomatis tersebut dicabut.
* **Review & Feedback (FR-3.3):** dilakukan **Superadmin** (menggantikan peran dosen) — memberi status + feedback per modul/tugas. PDF logbook/tugas menyertakan **Riwayat Revisi** (snapshot tiap submit/review: versi, status, catatan, oleh, tanggal).
* **Pemeriksaan Indikasi AI (FR-3.4):** pada halaman review, superadmin menekan tombol **"Periksa Indikasi AI"** untuk mengestimasi porsi tulisan/gambar berindikasi AI pada isi logbook. Estimasi **heuristik** (densitas frasa/konektor formal, keseragaman kalimat, rasio kata panjang, struktur enumerasi; dikurangi densitas bahasa gaul sebagai sinyal manusia) untuk teks, dan **tanda-tangan metadata generator** (Midjourney/SD/DALL·E/C2PA, dll) untuk gambar; overall = 0,7·teks + 0,3·gambar. Bersifat **indikatif (bukan vonis)**; mendukung **API detektor eksternal** opsional (GPTZero/Sapling/Originality/Winston) via konfigurasi — bila aktif dipakai otomatis, jika gagal fallback ke heuristik. Tiap modul memiliki **Level Batasan AI (1–5, Tabel V.5)** yang ditetapkan superadmin di Kelola Modul. Hasil % + level tampil ke superadmin **dan** ke mahasiswa bersama feedback, dicantumkan pula pada PDF logbook & rekap per kelas di Summary Report.
* **Pemeriksaan Tata Tulis / Proofreader (FR-3.5):** pada halaman review, superadmin menekan tombol **"Periksa Tata Tulis"** — engine **proofreader & technical editor** berbasis aturan (deterministik) memeriksa isi logbook terhadap **8 checklist**:
  1. **Panjang & Struktur Paragraf** — paragraf ideal ≥ 5 kalimat (1 kalimat utama + penjelas); tandai **paragraf sebatang kara** (< 3 kalimat).
  2. **Format Sitasi IEEE** — sitasi wajib gaya IEEE dengan kurung siku angka ([1], [2], [1-3]); peringatkan bila memakai gaya APA/Harvard (mis. "(Pratama, 2023)").
  3. **Ejaan & PUEBI** — kata tidak baku (mis. 'analisa'→'analisis', 'prosentase'→'persentase') & awalan **"di-"/"ke-"** (dipisah untuk tempat, disambung untuk kata kerja pasif).
  4. **Istilah Asing & Italic** — semua istilah Inggris/teknis (termasuk frasa seperti *Virtual Reality* & akronim *VR/XR/API*) wajib dicetak miring; leksikon diperluas + deteksi akronim.
  5. **Caption Gambar/Tabel** — pastikan setiap Gambar/Tabel memiliki **caption bernomor** ("Gambar 3.1 …"); relevansi isi caption terhadap narasi ditandai untuk tinjauan manual.
  6. **Gaya Tulis & Kata Ganti** — kata ganti orang pertama (saya/kami/penulis) → sarankan bentuk **pasif ilmiah/impersonal**.
  7. **Alignment & Spasi (Layout)** — paragraf utama wajib **rata kanan-kiri (justify)** dengan *line-height* 1,5; caption wajib **rata tengah (center)**.
  8. **Rujukan Ambigu** — "gambar di bawah"/"tabel di atas" harus diganti menjadi sebutan nomor/label spesifik (mis. "Gambar 3.1").
  Keluaran: **skor 0–100**, ringkasan, daftar *issues* (kategori, kutipan asli, saran perbaikan, penjelasan), dan **teks terkoreksi otomatis** (auto-fix aman: pembakuan ejaan/"di-"/"ke-", pemiringan istilah asing, penerapan justify/line-height & center caption; sitasi/caption/struktur tetap butuh penilaian manusia). Hasil tersimpan & dapat diperiksa ulang. Kamus & daftar aturan dikelola di `config/capstone.php` (`proofreader.*`).

### EPIC 4 — Assessment & Penilaian
Nilai bersifat **kelompok** (sama untuk seluruh anggota tim) melalui rubrik per kriteria, dengan dua kebijakan pembeda individu (Peer 180° & penalti kehadiran).

* **FR-4.1 Bobot Assessment (default, configurable):**
  * **Assessment 1 — Development Plan = 30%** (slide rencana penawaran topik untuk menarik investor).
  * **Assessment 2 — Video Prototipe Produk = 30%**.
  * **Assessment 3 — Expo Final = 40%** (voting; berkas: logo aplikasi, video final, DPPL final Word, manual book, source code+DB).
* **FR-4.2 Kriteria Rubrik (dikelola superadmin):**
  * A1: Ruang lingkup, Fitur, Fitur AI, Proses bisnis, Luaran sistem, Metode pengembangan, Pembagian jobdesc, Timeline, Penguasaan.
  * A2: Logo, Identitas sistem, Fitur, Kreatifitas.
  * A3: Performansi pada expo, Jumlah votes.
  * Nilai kelompok stage = rata-rata skor seluruh kriteria (0–100).

### EPIC 5 — Peer 180°, Penalti Kehadiran, Grading Engine
* **FR-5.1 Peer 180° per Tahap:** dilakukan **setelah tiap assessment**, menjadi **10% bagian di dalam nilai assessment** tersebut. Hanya dapat diisi bila **dibuka superadmin** per tahap. Kriteria: Komunikasi & Kerjasama, Kontribusi Kode/Dokumen, Tanggung Jawab & Ketepatan Waktu, Kehadiran Diskusi Internal. Tampilan mahasiswa disusun **vertikal (ke bawah)**.
  * Nilai stage = `rubrik_kelompok × (1 − peer%) + rerata_peer_stage × peer%` (bila peer belum ada, dipakai rubrik penuh).
  * **NA = Σ (nilai_stage × bobot_stage)**.
* **FR-5.2 Penalti Kehadiran (berbasis HARI tidak hadir, configurable):** presensi 16 minggu × 2 sesi; "hari tidak hadir" = jumlah sesi berstatus *alpa*.
  * **Ringan (4–6 hari):** NA individu −**7 poin** dari total nilai kelompok.
  * **Sedang (7–10 hari):** NA individu −**15 poin**.
  * **Berat (>10 hari):** ditinjau khusus — sanksi tertinggi **Nilai E (Tidak Lulus)**.
  * Ambang & besaran poin **dapat diubah superadmin**.
* **FR-5.3 Real-time Recalculation:** perubahan bobot/kriteria/penalti memicu kalkulasi ulang tanpa menghapus data mentah (raw NA tetap tersimpan). Tersedia **override** nilai akhir oleh koordinator.

### EPIC 6 — Summary Report
* **Rekapitulasi per Kelas:** kolom indeks nilai (A, AB, B, BC, C, D, E), jumlah mahasiswa, dan rata-rata kelas.
* **Grafik sebaran** indeks nilai (donut chart).
* Dapat difilter per tahun ajaran (termasuk data histori 2 tahun sebelumnya).

---

## 5. Skala Indeks Nilai Akhir (NA)

| Rentang | Indeks |
| :--- | :---: |
| NA > 85 | A |
| 75 < NA ≤ 85 | AB |
| 65 < NA ≤ 75 | B |
| 60 < NA ≤ 65 | BC |
| 50 < NA ≤ 60 | C |
| 40 < NA ≤ 50 | D |
| NA ≤ 40 | E |

---

## 6. Skema Database (MySQL) — Ringkas

| Tabel | Keterangan |
| :--- | :--- |
| `academic_years` | Tahun ajaran (aktif/arsip). |
| `users` | role ∈ {superadmin, mahasiswa}; +angkatan, class_name, is_active. |
| `partners` | Master mitra (type: industri/masyarakat_desa/internal, logo, address). |
| `topics` | Katalog + mandiri (partner_id, title, general_features, ai_features, origin, is_available). |
| `teams` | +topic_id, topic_status (none/pending/approved/rejected), case_type; **tanpa** dosen_pembimbing. |
| `team_members` | Peran anggota (PM_Analyst, UIUX_Designer, Lead_Developer, QA_Tester). |
| `modules` | Modul dinamis per tahun ajaran (order, week_label, code, type, assessment_stage, description, fields_json); **`is_open`** (gate buka/tutup), **`is_individual`** (tugas per-mahasiswa), **`attendance_week`/`attendance_session`** (slot presensi otomatis saat tugas PASS). |
| `module_logbooks` | payload_json per field, status_approval, feedback, submitted_at; **`user_id`** (NULL = logbook tim; terisi = pemilik tugas individu) — unique (team, module, user); indikasi AI (`ai_percentage`, `ai_text_percentage`, `ai_image_percentage`, `ai_detail_json`, `ai_checked_at`); tata tulis (`proofread_score`, `proofread_json`, `proofread_checked_at`). |
| `module_logbook_versions` | Snapshot versi payload. |
| `assessment_stages` | A1/A2/A3: weight_percentage, peer_weight_percentage, peer_open. |
| `assessment_criteria` | Kriteria rubrik per stage. |
| `assessment_scores` | Nilai rubrik kelompok per (criterion, team). |
| `peer_evaluations` | Peer 180° per (stage, evaluator, evaluatee). |
| `attendances` | Presensi per (student, week, session); status present/permit/sick/absent. |
| `attendance_penalty_rules` | Aturan penalti berbasis min_days/max_days → points_deduction/fail. |
| `final_grades` | breakdown_json, raw_score (NA), absent_days, penalty_points/level, final_score, grade_letter, override. |

---

## 7. Arsitektur

* **Service Layer:**
  * `App\Services\GradeCalculationService` — nilai stage (rubrik + peer per tahap), NA berbobot, penalti poin berbasis hari absen, indeks nilai; raw NA tetap utuh.
  * `App\Services\LogbookWorkflowService` — status logbook + snapshot versi payload dinamis.
  * `App\Services\CapstoneDefaultsService` — menyemai modul RPS, stage+kriteria, penalti default per tahun ajaran.
  * `App\Services\AiDetectionService` — estimasi indikasi AI teks (heuristik) + gambar (tanda-tangan metadata), dengan slot API detektor eksternal.
  * `App\Services\ProofreaderService` — pemeriksa tata tulis 7-checklist (rule-based) + auto-fix aman; kamus di `config/capstone.php` (`proofreader.*`).
  * `App\Services\HtmlSanitizer` — allowlist DOM anti stored-XSS untuk seluruh rich-text tersimpan.
* **Config domain:** `config/capstone.php` (rencana modul, field default, stage & bobot, kriteria peer, penalti default, skala indeks nilai, level batasan AI, daftar heuristik AI & kamus proofreader).
* **Rich text logbook:** editor ringan berbasis `contenteditable` (bold/italic/list + sisip gambar base64), disimpan sebagai HTML pada `payload_json` — siap cetak PDF.
* **Keamanan berkas:** logo mitra pada private disk, diakses via route terotorisasi.
* **Role gating:** middleware alias `role:` (`role:superadmin`, `role:mahasiswa`).

---

## 8. Akun Demo (seed)

| Role | Login | Password |
| :--- | :--- | :--- |
| Superadmin (Koordinator) | `admin@sicaps.test` | `password` |
| Mahasiswa (Ketua) | NIM `2101001` | `password` |
| Mahasiswa (Anggota) | NIM `2101002`–`2101005` | `password` |

Seed juga menyertakan mitra, katalog topik, presensi contoh (Dewi >10 hari alpa → E), serta **data histori 2 tahun ajaran** (2023/2024, 2024/2025) untuk Summary Report.
