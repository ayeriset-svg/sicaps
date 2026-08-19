# SIM-CAPSTONE — Sistem Informasi Manajemen Capstone Project

Platform LMS & *Digital Progress Tracker* untuk mata kuliah Capstone Project D3 Sistem Informasi Akuntansi. Dibangun sesuai [PRD_SIM_CAPSTONE.md](PRD_SIM_CAPSTONE.md) v2.0 (revisi RPS).

## Stack
- Laravel 10 (PHP 8.1) · Blade · Controller + Service Layer
- MySQL · Tailwind CSS (CDN) · Alpine.js (CDN)

## Ringkasan Fitur
- **2 role:** Superadmin (Koordinator) & Mahasiswa.
- **Rencana 16 minggu (2 sesi/minggu)** dengan **modul dinamis** — superadmin mengatur jumlah modul, materi, dan field logbook (rich text base64 siap-PDF + link).
- **Topik campur:** pilih dari katalog superadmin **atau** ajukan mandiri; approval oleh superadmin.
- **Master Mitra** (industri / masyarakat desa / internal + logo) & **Kelola Topik** (fitur umum + fitur AI).
- **Penilaian:** Assessment 1 (30%) + 2 (30%) + 3 (40%), nilai rubrik **kelompok**; **Peer 180° 10% per tahap** (dibuka superadmin per assessment); **penalti kehadiran** berbasis hari alpa (4–6→−7, 7–10→−15, >10→E).
- **Master Mahasiswa** per kelas/angkatan + **import CSV** (termasuk nilai historis).
- **Summary Report:** rekap indeks nilai per kelas + donut chart, lintas tahun ajaran.

## Skala Indeks Nilai
`NA>85=A · 75–85=AB · 65–75=B · 60–65=BC · 50–60=C · 40–50=D · ≤40=E`

## Menjalankan
```bash
php artisan migrate:fresh --seed
php artisan serve
```
Buka http://127.0.0.1:8000

### Akun demo (password: `password`)
| Role | Login |
| --- | --- |
| Superadmin | `admin@sicaps.test` |
| Mahasiswa (Ketua) | NIM `2101001` |
| Mahasiswa (Anggota) | NIM `2101002`–`2101005` |

Seed menyertakan mitra, katalog topik, presensi contoh, dan **data histori 2 tahun ajaran** untuk Summary Report.

## Arsitektur kunci
- `app/Services/GradeCalculationService.php` — nilai per-stage (rubrik + peer 10%), NA berbobot, penalti poin berbasis hari absen, indeks nilai (raw NA tetap utuh).
- `app/Services/LogbookWorkflowService.php` — status logbook + versioning payload dinamis.
- `app/Services/CapstoneDefaultsService.php` — semai modul RPS, stage+kriteria, penalti per tahun ajaran.
- `config/capstone.php` — rencana modul, field default, stage & bobot, kriteria peer, penalti, skala indeks nilai.
- Middleware `role:` — gating superadmin/mahasiswa.

## Format CSV import
- **Master User:** `identity_number,name,email,role,angkatan,class_name,password`
- **Master Mahasiswa (+histori):** `identity_number,name,email,angkatan,class_name,password,year,semester,final_score,grade_letter` (kolom `year..grade_letter` opsional).
