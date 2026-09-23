<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AttendancePenaltyRule;
use App\Services\GradeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenaltyRuleController extends Controller
{
    public function index()
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Aktifkan tahun ajaran terlebih dahulu.');

        $rules = $ay->penaltyRules()->get();

        return view('admin.penalty.index', compact('rules', 'ay'));
    }

    public function store(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);

        $data = $this->validated($request);
        $data['academic_year_id'] = $ay->id;
        AttendancePenaltyRule::create($data);
        $this->recalculate($ay);

        return back()->with('success', 'Aturan penalti ditambahkan & nilai akhir dihitung ulang.');
    }

    public function update(Request $request, AttendancePenaltyRule $rule)
    {
        $rule->update($this->validated($request));
        $this->recalculate($rule->academicYear);

        return back()->with('success', 'Aturan penalti diperbarui & nilai akhir dihitung ulang.');
    }

    public function destroy(AttendancePenaltyRule $rule)
    {
        $ay = $rule->academicYear;
        $rule->delete();
        $this->recalculate($ay);

        return back()->with('success', 'Aturan penalti dihapus & nilai akhir dihitung ulang.');
    }

    private function recalculate(?AcademicYear $ay): void
    {
        if ($ay) {
            app(GradeCalculationService::class)->recalculateAll($ay);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'min_days' => ['required', 'integer', 'min:0'],
            'max_days' => ['nullable', 'integer', 'min:0', 'gte:min_days'],
            'penalty_type' => ['required', Rule::in(['none', 'points_deduction', 'fail'])],
            'deduction_points' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
    }
}
