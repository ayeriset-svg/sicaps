<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\CapstoneDefaultsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    public function index()
    {
        $years = AcademicYear::withCount('teams')->orderByDesc('year')->orderByDesc('semester')->get();

        return view('admin.academic-years.index', compact('years'));
    }

    public function store(Request $request, CapstoneDefaultsService $defaults)
    {
        $data = $request->validate([
            'year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', Rule::in(['ganjil', 'genap'])],
        ], ['year.regex' => 'Format tahun harus seperti 2025/2026.']);

        if (AcademicYear::where('year', $data['year'])->where('semester', $data['semester'])->exists()) {
            return back()->with('error', 'Tahun ajaran & semester tersebut sudah ada.');
        }

        DB::transaction(function () use ($data, $defaults) {
            $ay = AcademicYear::create($data);
            $defaults->seed($ay); // modul RPS, stage A1/A2/A3 + kriteria, penalty rules
        });

        return back()->with('success', 'Tahun ajaran dibuat beserta modul, stage penilaian, & penalty default.');
    }

    public function activate(AcademicYear $academicYear)
    {
        DB::transaction(function () use ($academicYear) {
            AcademicYear::query()->update(['is_active' => false]);
            $academicYear->update(['is_active' => true, 'is_archived' => false]);
        });

        return back()->with('success', "Tahun ajaran {$academicYear->label} diaktifkan.");
    }

    public function archive(AcademicYear $academicYear)
    {
        $academicYear->update(['is_active' => false, 'is_archived' => true]);

        return back()->with('success', "Tahun ajaran {$academicYear->label} diarsipkan.");
    }
}
