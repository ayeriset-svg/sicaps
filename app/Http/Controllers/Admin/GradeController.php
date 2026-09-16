<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FinalGrade;
use App\Services\GradeCalculationService;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Aktifkan tahun ajaran terlebih dahulu.');

        $grades = $this->grades($ay, $request->input('class'));
        $stages = $ay->stages()->orderBy('order_index')->get(['id', 'code', 'name']);

        $classes = \App\Models\User::where('role', 'mahasiswa')->whereNotNull('class_name')
            ->distinct()->orderBy('class_name')->pluck('class_name');

        return view('admin.grades.index', compact('grades', 'stages', 'ay', 'classes'));
    }

    /** Export rekap nilai (termasuk A1/A2/A3) ke Excel. */
    public function export(Request $request): BinaryFileResponse
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);

        $grades = $this->grades($ay, $request->input('class'));
        $stages = $ay->stages()->orderBy('order_index')->get(['code']);

        $header = array_merge(
            ['NIM', 'Nama', 'Kelas'],
            $stages->pluck('code')->all(),
            ['NA', 'Hari Alpa', 'Penalti', 'Nilai Akhir', 'Indeks']
        );

        $rows = [];
        foreach ($grades as $g) {
            $bd = collect($g->breakdown_json ?? [])->keyBy('code');
            $row = [$g->student->identity_number, $g->student->name, $g->student->class_name ?? ''];
            foreach ($stages as $s) {
                $row[] = isset($bd[$s->code]) ? number_format((float) $bd[$s->code]['stage_score'], 2) : '';
            }
            $row[] = number_format((float) $g->raw_score, 2);
            $row[] = (string) $g->absent_days;
            $row[] = number_format((float) $g->penalty_points, 0);
            $row[] = number_format((float) $g->effective_score, 2);
            $row[] = $g->grade_letter;
            $rows[] = $row;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'grd') . '.xlsx';
        Xlsx::write($tmp, $header, $rows);
        $filename = 'rekap-nilai-' . str_replace(['/', ' '], ['-', ''], $ay->label) . '.xlsx';

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function grades(AcademicYear $ay, ?string $class)
    {
        return FinalGrade::with('student')
            ->where('academic_year_id', $ay->id)
            ->when($class, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('class_name', $class)))
            ->get()
            ->sortByDesc(fn ($g) => $g->effective_score)
            ->values();
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
