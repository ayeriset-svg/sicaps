<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AssessmentScore;
use App\Models\AssessmentStage;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScoreController extends Controller
{
    /**
     * Input nilai rubrik kelompok per stage (nilai sama utk seluruh anggota tim).
     */
    public function index(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Aktifkan tahun ajaran terlebih dahulu.');

        $stages = $ay->stages()->with('criteria')->get();
        $stage = $request->filled('stage')
            ? $stages->firstWhere('id', (int) $request->stage)
            : $stages->first();

        $teams = Team::with('members.student', 'leader')
            ->where('academic_year_id', $ay->id)
            ->when($request->filled('class'), fn ($q) => $q->whereHas('leader', fn ($l) => $l->where('class_name', $request->class)))
            ->when($request->filled('team'), fn ($q) => $q->where('id', (int) $request->team))
            ->orderBy('team_name')->get();

        $classes = User::where('role', 'mahasiswa')->whereNotNull('class_name')->distinct()->orderBy('class_name')->pluck('class_name');
        $allTeams = Team::where('academic_year_id', $ay->id)->orderBy('team_name')->get(['id', 'team_name']);

        // Nilai eksisting: [team_id][criterion_id] = score
        $existing = [];
        if ($stage) {
            $rows = AssessmentScore::whereIn('criterion_id', $stage->criteria->pluck('id'))
                ->whereIn('team_id', $teams->pluck('id'))->get();
            foreach ($rows as $r) {
                $existing[$r->team_id][$r->criterion_id] = $r->score;
            }
        }

        return view('admin.scores.index', compact('stages', 'stage', 'teams', 'existing', 'ay', 'classes', 'allTeams'));
    }

    public function store(Request $request)
    {
        $ay = AcademicYear::active();
        $stage = AssessmentStage::findOrFail($request->stage_id);
        $team = Team::findOrFail($request->team_id);

        $request->validate([
            'stage_id' => ['required', 'exists:assessment_stages,id'],
            'team_id' => ['required', 'exists:teams,id'],
            'scores' => ['required', 'array'],       // criterion_id => score
            'scores.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $criterionIds = $stage->criteria->pluck('id')->all();

        foreach ($request->input('scores', []) as $criterionId => $score) {
            if (! in_array((int) $criterionId, $criterionIds, true) || $score === null || $score === '') {
                continue;
            }
            AssessmentScore::updateOrCreate(
                ['criterion_id' => $criterionId, 'team_id' => $team->id],
                ['score' => $score, 'evaluator_id' => Auth::id()]
            );
        }

        return back()->with('success', "Nilai {$stage->code} untuk tim {$team->team_name} tersimpan.");
    }
}
