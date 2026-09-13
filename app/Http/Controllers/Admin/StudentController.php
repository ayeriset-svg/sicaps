<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReadsCsv;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FinalGrade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    use ReadsCsv;

    /**
     * Master data mahasiswa lengkap (per kelas & angkatan).
     */
    public function index(Request $request)
    {
        $base = User::where('role', 'mahasiswa');

        // Jumlah baris per halaman (10/20/50/all).
        $perPage = $request->input('per_page', 20);
        $perPage = $perPage === 'all'
            ? max(1, (clone $base)->count())
            : (in_array((int) $perPage, [10, 20, 50], true) ? (int) $perPage : 20);

        $students = (clone $base)
            ->when($request->filled('angkatan'), fn ($q) => $q->where('angkatan', $request->angkatan))
            ->when($request->filled('class'), fn ($q) => $q->where('class_name', $request->class))
            ->when($request->filled('activation'), function ($q) use ($request) {
                // 'pending' = belum aktivasi (wajib ganti sandi); 'active' = sudah aktivasi.
                $q->where('must_change_password', $request->activation === 'pending');
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $s = $request->q;
                $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('identity_number', 'like', "%$s%"));
            })
            ->orderBy('angkatan')->orderBy('class_name')->orderBy('name')
            ->paginate($perPage)->withQueryString();

        $angkatans = (clone $base)->whereNotNull('angkatan')->distinct()->orderBy('angkatan')->pluck('angkatan');
        $classes = (clone $base)->whereNotNull('class_name')->distinct()->orderBy('class_name')->pluck('class_name');
        $pendingCount = (clone $base)->where('must_change_password', true)->count();

        return view('admin.students.index', compact('students', 'angkatans', 'classes', 'pendingCount'));
    }

    /**
     * Import master data mahasiswa + (opsional) nilai akhir historis.
     * Header CSV: identity_number,name,angkatan,class_name,password,
     *             year,semester,final_score,grade_letter
     * Email tidak lagi diperlukan (dibuat otomatis internal). Kolom year..grade_letter
     * opsional; bila year+semester diisi, dibuatkan/dilinkkan ke tahun ajaran
     * (diarsipkan) beserta final_grade historis.
     */
    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'max:10240']]);
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
            return back()->with('error', 'Format berkas harus Excel (.xlsx) atau CSV.');
        }

        $rows = $this->readImportRows($file);
        if (empty($rows)) {
            return back()->with('error', 'Berkas kosong atau tidak terbaca. Pastikan ada baris header + data.');
        }
        if (! array_key_exists('identity_number', $rows[0])) {
            return back()->with('error', 'Kolom "identity_number" tidak ditemukan. Gunakan template yang disediakan (header baris pertama).');
        }

        $created = 0;
        $grades = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $d) {
                $identity = trim((string) ($d['identity_number'] ?? ''));
                if ($identity === '') {
                    $skipped++;
                    continue;
                }
                // Email tidak dipakai di master mahasiswa; buat otomatis (unik) bila kolom kosong.
                $email = trim($d['email'] ?? '') ?: $identity . '@student.sicaps.local';

                $student = User::where('identity_number', $identity)->first();
                if (! $student) {
                    $student = User::create([
                        'identity_number' => $identity,
                        'name' => trim($d['name'] ?? $identity),
                        'email' => $email,
                        'role' => 'mahasiswa',
                        'angkatan' => trim($d['angkatan'] ?? '') ?: null,
                        'class_name' => trim($d['class_name'] ?? '') ?: null,
                        'password' => Hash::make(trim($d['password'] ?? '') ?: $identity),
                        // Sandi default = NIM → wajib diganti saat login pertama (aktivasi).
                        'must_change_password' => true,
                    ]);
                    $created++;
                }

                // Nilai historis opsional.
                $year = trim($d['year'] ?? '');
                $semester = strtolower(trim($d['semester'] ?? ''));
                $finalScore = $d['final_score'] ?? null;
                if ($year !== '' && in_array($semester, ['ganjil', 'genap'], true) && $finalScore !== null && $finalScore !== '') {
                    $ay = AcademicYear::firstOrCreate(
                        ['year' => $year, 'semester' => $semester],
                        ['is_active' => false, 'is_archived' => true]
                    );
                    $letter = trim($d['grade_letter'] ?? '') ?: $this->letter((float) $finalScore);
                    FinalGrade::updateOrCreate(
                        ['student_id' => $student->id, 'academic_year_id' => $ay->id],
                        [
                            'raw_score' => (float) $finalScore,
                            'final_score' => (float) $finalScore,
                            'grade_letter' => $letter,
                            'calculated_at' => now(),
                        ]
                    );
                    $grades++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }

        return back()->with('success', "Import selesai: {$created} mahasiswa baru, {$grades} nilai historis, {$skipped} dilewati.");
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
