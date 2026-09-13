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
use Illuminate\Validation\Rule;

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

    /** Tambah satu mahasiswa manual. Email dibuat otomatis (tak ditampilkan). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'identity_number' => ['required', 'string', 'max:30', 'unique:users,identity_number'],
            'name' => ['required', 'string', 'max:255'],
            'angkatan' => ['nullable', 'string', 'max:10'],
            'class_name' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $nim = $data['identity_number'];
        User::create([
            'identity_number' => $nim,
            'name' => $data['name'],
            'email' => $this->autoEmail($nim),
            'role' => 'mahasiswa',
            'angkatan' => $data['angkatan'] ?? null,
            'class_name' => $data['class_name'] ?? null,
            'password' => Hash::make($data['password'] ?? $nim),
            // Sandi default = NIM → wajib diganti saat login pertama (aktivasi).
            'must_change_password' => true,
        ]);

        return back()->with('success', 'Mahasiswa ditambahkan.');
    }

    /** Edit satu data mahasiswa (perbaikan setelah import). */
    public function update(Request $request, User $student)
    {
        abort_unless($student->role === 'mahasiswa', 404);

        $data = $request->validate([
            'identity_number' => ['required', 'string', 'max:30', Rule::unique('users', 'identity_number')->ignore($student->id)],
            'name' => ['required', 'string', 'max:255'],
            'angkatan' => ['nullable', 'string', 'max:10'],
            'class_name' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $update = [
            'identity_number' => $data['identity_number'],
            'name' => $data['name'],
            'angkatan' => $data['angkatan'] ?? null,
            'class_name' => $data['class_name'] ?? null,
        ];
        // Bila sandi diisi = reset → wajib ganti lagi saat login berikutnya.
        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
            $update['must_change_password'] = true;
        }
        $student->update($update);

        return back()->with('success', 'Data mahasiswa diperbarui.');
    }

    /** Hapus satu mahasiswa. Diblokir bila masih terkait tim (agar data tim tak ikut terhapus). */
    public function destroy(User $student)
    {
        abort_unless($student->role === 'mahasiswa', 404);

        if ($student->ledTeams()->exists() || $student->memberships()->exists()) {
            return back()->with('error', 'Mahasiswa masih memimpin/tergabung dalam tim. Lepaskan dari tim terlebih dahulu sebelum menghapus.');
        }

        $student->delete();

        return back()->with('success', 'Mahasiswa dihapus.');
    }

    /** Email internal otomatis (kolom email tidak dipakai di master mahasiswa). */
    private function autoEmail(string $nim): string
    {
        return $nim . '@student.sicaps.local';
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
