<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FinalGrade;
use App\Services\GradeCalculationService;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Aktifkan tahun ajaran terlebih dahulu.');

        $grades = FinalGrade::with('student')
            ->where('academic_year_id', $ay->id)
            ->when($request->filled('class'), fn ($q) => $q->whereHas('student', fn ($s) => $s->where('class_name', $request->class)))
            ->get()
            ->sortByDesc(fn ($g) => $g->effective_score)
            ->values();

        $classes = \App\Models\User::where('role', 'mahasiswa')->whereNotNull('class_name')
            ->distinct()->orderBy('class_name')->pluck('class_name');

        return view('admin.grades.index', compact('grades', 'ay', 'classes'));
    }

    public function recalculate(GradeCalculationService $service)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);

        $count = $service->recalculateAll($ay);

        return back()->with('success', "Rekalkulasi selesai untuk {$count} mahasiswa.");
    }

    public function override(Request $request, FinalGrade $grade)
    {
        $data = $request->validate([
            'override_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'override_note' => ['nullable', 'string'],
        ]);

        $grade->update([
            'override_score' => $data['override_score'] ?? null,
            'override_note' => $data['override_note'] ?? null,
        ]);

        return back()->with('success', 'Override nilai disimpan.');
    }
}
