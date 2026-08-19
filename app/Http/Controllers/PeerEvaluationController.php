<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AssessmentStage;
use App\Models\PeerEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PeerEvaluationController extends Controller
{
    /**
     * Peer 180 per stage; hanya stage yang dibuka superadmin yang dapat diisi.
     */
    public function index()
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);
        $user = Auth::user();
        $team = $user->activeTeam($ay->id);
        abort_unless($team, 403, 'Anda harus tergabung dalam tim.');

        $team->load('members.student');
        $members = $team->members->pluck('student')->filter()->values();

        $stages = $ay->stages()->get();

        // existing[stage_id][evaluatee_id] = evaluation
        $existing = [];
        foreach (PeerEvaluation::where('team_id', $team->id)->where('evaluator_id', $user->id)->get() as $e) {
            $existing[$e->stage_id][$e->evaluatee_id] = $e;
        }

        $criteria = config('capstone.peer_criteria');

        return view('peer.index', compact('team', 'members', 'stages', 'existing', 'criteria', 'user'));
    }

    public function store(Request $request)
    {
        $ay = AcademicYear::active();
        $user = Auth::user();
        $team = $user->activeTeam($ay?->id);
        abort_unless($team, 403);

        $data = $request->validate([
            'stage_id' => ['required', 'exists:assessment_stages,id'],
            'evaluatee_id' => ['required', 'exists:users,id'],
            'score_communication' => ['required', 'numeric', 'min:0', 'max:100'],
            'score_contribution' => ['required', 'numeric', 'min:0', 'max:100'],
            'score_responsibility' => ['required', 'numeric', 'min:0', 'max:100'],
            'score_attendance' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string'],
        ]);

        $stage = AssessmentStage::findOrFail($data['stage_id']);
        abort_unless($stage->peer_open, 403, 'Peer 180° untuk tahap ini belum dibuka.');
        abort_unless(
            $team->members()->where('student_id', $data['evaluatee_id'])->exists(),
            403, 'Hanya dapat menilai anggota tim Anda.'
        );

        $final = round(($data['score_communication'] + $data['score_contribution']
            + $data['score_responsibility'] + $data['score_attendance']) / 4, 2);

        PeerEvaluation::updateOrCreate(
            ['stage_id' => $stage->id, 'evaluator_id' => $user->id, 'evaluatee_id' => $data['evaluatee_id']],
            array_merge($data, ['team_id' => $team->id, 'final_peer_score' => $final])
        );

        return back()->with('success', 'Penilaian peer tersimpan.');
    }
}
