<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\PeerEvaluation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class PeerResultController extends Controller
{
    public function index(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Aktifkan tahun ajaran terlebih dahulu.');

        $stages = $ay->stages()->get();
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

        $results = [];
        if ($stage) {
            foreach ($teams as $team) {
                foreach ($team->members->pluck('student')->filter() as $student) {
                    $q = PeerEvaluation::where('stage_id', $stage->id)
                        ->where('team_id', $team->id)
                        ->where('evaluatee_id', $student->id);
                    $results[$team->id][] = [
                        'student' => $student,
                        'avg' => ($a = $q->avg('final_peer_score')) !== null ? round($a, 2) : null,
                        'received' => (clone $q)->count(),
                    ];
                }
            }
        }

        return view('admin.peer-result.index', compact('stages', 'stage', 'teams', 'results', 'ay', 'classes', 'allTeams'));
    }
}
