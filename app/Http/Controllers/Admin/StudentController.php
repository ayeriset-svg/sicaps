<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FinalGrade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
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
     * Header CSV: identity_number,name,email,angkatan,class_name,password,
     *             year,semester,final_score,grade_letter
     * Kolom year..grade_letter opsional; bila year+semester diisi, dibuatkan/dilinkkan
     * ke tahun ajaran (diarsipkan) beserta final_grade historis.
     */
    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = null;
        $created = 0;
        $grades = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if ($header === null) {
                    $header = array_map(fn ($h) => strtolower(trim($h)), $row);
                    continue;
                }
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }
                $d = @array_combine($header, array_pad($row, count($header), null));
                $identity = trim($d['identity_number'] ?? '');
                $email = trim($d['email'] ?? '');
                if ($identity === '' || $email === '') {
                    $skipped++;
                    continue;
                }

                $student = User::where('identity_number', $identity)->orWhere('email', $email)->first();
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
            fclose($handle);

            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }
        fclose($handle);

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
