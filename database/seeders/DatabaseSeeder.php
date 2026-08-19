<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\FinalGrade;
use App\Models\Partner;
use App\Models\Team;
use App\Models\Topic;
use App\Models\User;
use App\Services\CapstoneDefaultsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = app(CapstoneDefaultsService::class);

        // ---------------- Tahun ajaran aktif ----------------
        $ay = AcademicYear::create(['year' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true]);
        $defaults->seed($ay); // modul RPS, stage A1/A2/A3 + kriteria, penalty rules

        // ---------------- Superadmin ----------------
        $admin = User::create([
            'identity_number' => 'ADMIN01', 'name' => 'Koordinator Capstone',
            'email' => 'admin@sicaps.test', 'password' => Hash::make('password'), 'role' => 'superadmin',
        ]);

        // ---------------- Mitra ----------------
        $mitra = Partner::create([
            'academic_year_id' => $ay->id, 'name' => 'UD Sumber Rejeki', 'type' => 'industri',
            'address' => 'Jl. Merdeka No. 12, Surabaya', 'contact_person' => 'Pak Budi (Pemilik)',
        ]);
        Partner::create(['academic_year_id' => $ay->id, 'name' => 'Desa Sukamaju', 'type' => 'masyarakat_desa', 'address' => 'Kec. Sukamaju']);

        // ---------------- Topik katalog ----------------
        Topic::create([
            'academic_year_id' => $ay->id, 'partner_id' => $mitra->id, 'origin' => 'katalog', 'is_available' => true,
            'title' => 'SIA Persediaan Dagang UD Sumber Rejeki', 'created_by' => $admin->id,
            'general_features' => 'Master, Pembelian, Penjualan, Persediaan, Laporan Keuangan.',
            'ai_features' => 'Prediksi stok minimum & rekomendasi reorder berbasis AI.',
        ]);
        Topic::create([
            'academic_year_id' => $ay->id, 'origin' => 'katalog', 'is_available' => true,
            'title' => 'Sistem Kas Desa Digital', 'created_by' => $admin->id,
            'general_features' => 'Pencatatan kas masuk/keluar, laporan APBDes.',
            'ai_features' => 'Klasifikasi otomatis kategori transaksi.',
        ]);

        // ---------------- Mahasiswa + tim aktif ----------------
        $studentData = [
            ['2101001', 'Alit Prasetyo', 'Project Manager, System Analyst'],
            ['2101002', 'Bunga Lestari', 'UI/UX Designer'],
            ['2101003', 'Candra Wijaya', 'Lead Developer, Backend Developer'],
            ['2101004', 'Dewi Anggraini', 'QA Tester'],
        ];
        $students = [];
        foreach ($studentData as [$nim, $name, $role]) {
            $students[] = [
                'user' => User::create([
                    'identity_number' => $nim, 'name' => $name,
                    'email' => strtolower(explode(' ', $name)[0]) . '@student.sicaps.test',
                    'password' => Hash::make('password'), 'role' => 'mahasiswa',
                    'angkatan' => '2021', 'class_name' => 'SIA-3A',
                ]),
                'role' => $role,
            ];
        }
        // Eko: contoh akun hasil import yang BELUM aktivasi — sandi default = NIM, wajib ganti.
        User::create([
            'identity_number' => '2101005', 'name' => 'Eko Nugroho', 'email' => 'eko@student.sicaps.test',
            'password' => Hash::make('2101005'), 'role' => 'mahasiswa', 'angkatan' => '2021', 'class_name' => 'SIA-3A',
            'must_change_password' => true,
        ]);

        $team = Team::create([
            'academic_year_id' => $ay->id, 'team_name' => 'Tim Neraca Digital',
            'leader_id' => $students[0]['user']->id, 'case_type' => 'dagang',
            'topic_id' => Topic::where('academic_year_id', $ay->id)->first()->id, 'topic_status' => 'approved',
            'hki_eligible' => true,
            'custom_general_features' => 'Master, Pembelian, Penjualan, Persediaan, Laporan Keuangan, + Dashboard Analitik.',
            'custom_ai_features' => 'Prediksi stok minimum & rekomendasi reorder berbasis AI.',
        ]);
        foreach ($students as $s) {
            $team->members()->create(['student_id' => $s['user']->id, 'assigned_role' => $s['role']]);
        }

        // Presensi contoh — default NULL (tidak diisi). Hanya seed kasus absent/sakit.
        // Dewi (idx 3): >10 hari alpa untuk uji penalti berat. Candra (idx 2): 2 sesi sakit.
        foreach ($students as $i => $s) {
            for ($w = 1; $w <= 16; $w++) {
                for ($sess = 1; $sess <= 2; $sess++) {
                    $status = null;
                    if ($i === 3 && $w >= 4 && $w <= 10) {
                        $status = 'absent';
                    } elseif ($i === 2 && in_array($w, [5, 11]) && $sess === 1) {
                        $status = 'sick';
                    }
                    if ($status === null) {
                        continue; // biarkan kosong (default null)
                    }
                    Attendance::create([
                        'student_id' => $s['user']->id, 'academic_year_id' => $ay->id,
                        'week_number' => $w, 'session_number' => $sess, 'status' => $status, 'recorded_by' => $admin->id,
                    ]);
                }
            }
        }

        // ---------------- Contoh materi Modul 0 (mengikuti template) ----------------
        \App\Models\Module::where('academic_year_id', $ay->id)->where('code', 'M0')->update([
            'ai_policy_level' => 2,
            'objectives' => '<p>Mahasiswa mampu membentuk tim, menetapkan peran, dan memilih ranah studi kasus.</p>',
            'tools_materials' => '<ul><li>Akun SIM-CAPSTONE</li><li>Template dokumen proyek</li></ul>',
            'ai_rules' => '<p>AI boleh digunakan untuk brainstorming ide tim, wajib dicantumkan penggunaannya.</p>',
            'references' => '<p>Panduan Capstone Project D3 SIA 2026.</p>',
            'description' => '<p>Pembentukan tim (maks 6 orang), pembagian peran, dan penetapan ranah usaha (Jasa/Dagang/Manufaktur).</p>',
            'tasks' => '<ol><li>Bentuk tim & tetapkan peran tiap anggota.</li><li>Tentukan ranah studi kasus.</li></ol>',
        ]);

        // ---------------- Data historis (2 tahun ajaran lalu, diarsipkan) ----------------
        $this->seedHistory('2023/2024', 'ganjil', ['SIA-3A', 'SIA-3B']);
        $this->seedHistory('2024/2025', 'ganjil', ['SIA-3A', 'SIA-3B']);

        $this->command->info('Seed selesai. Login: admin@sicaps.test / mahasiswa NIM 2101001 — password "password".');
    }

    /**
     * Buat tahun ajaran historis + mahasiswa + nilai akhir (untuk summary report).
     */
    private function seedHistory(string $year, string $semester, array $classes): void
    {
        $ay = AcademicYear::create(['year' => $year, 'semester' => $semester, 'is_active' => false, 'is_archived' => true]);
        $angkatan = (string) (((int) substr($year, 0, 4)) - 3);
        $scaleSamples = [92, 88, 82, 78, 72, 68, 63, 58, 45, 90, 85, 80, 76, 70, 55, 38];

        $n = 1;
        foreach ($classes as $class) {
            for ($k = 0; $k < 8; $k++) {
                $nim = substr($year, 2, 2) . '99' . str_pad($n, 3, '0', STR_PAD_LEFT);
                $student = User::create([
                    'identity_number' => $nim, 'name' => "Alumni {$class} " . $n,
                    'email' => "alumni{$nim}@sicaps.test", 'password' => Hash::make('password'),
                    'role' => 'mahasiswa', 'angkatan' => $angkatan, 'class_name' => $class,
                ]);
                $score = $scaleSamples[($n - 1) % count($scaleSamples)];
                FinalGrade::create([
                    'student_id' => $student->id, 'academic_year_id' => $ay->id,
                    'raw_score' => $score, 'final_score' => $score,
                    'grade_letter' => $this->letter($score), 'calculated_at' => now(),
                ]);
                $n++;
            }
        }
    }

    private function letter(float $score): string
    {
        foreach (config('capstone.grade_scale') as $t) {
            if ($score > $t['gt']) {
                return $t['letter'];
            }
        }

        return 'E';
    }
}
