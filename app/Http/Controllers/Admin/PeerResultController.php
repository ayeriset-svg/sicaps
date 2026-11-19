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
        $progress = [];      // [team_id] => daftar status pengisian tiap penilai
        $notDoneCount = 0;   // total penilai yang belum selesai mengisi (stage terpilih)
        if ($stage && $teams->isNotEmpty()) {
            // Dua query agregat untuk seluruh tim (bukan 3 query per mahasiswa).
            $base = PeerEvaluation::where('stage_id', $stage->id)->whereIn('team_id', $teams->pluck('id'));

            // Nilai yang DITERIMA tiap mahasiswa: [team_id][evaluatee_id] => {avg, received}
            $received = (clone $base)
                ->selectRaw('team_id, evaluatee_id, AVG(final_peer_score) AS avg_score, COUNT(*) AS received')
                ->groupBy('team_id', 'evaluatee_id')->get()
                ->groupBy('team_id')->map(fn ($rows) => $rows->keyBy('evaluatee_id'));

            // Progres pengisian: jumlah anggota yang sudah DINILAI oleh tiap mahasiswa.
            $given = (clone $base)
                ->selectRaw('team_id, evaluator_id, COUNT(DISTINCT evaluatee_id) AS submitted')
                ->groupBy('team_id', 'evaluator_id')->get()
                ->groupBy('team_id')->map(fn ($rows) => $rows->keyBy('evaluator_id'));

            foreach ($teams as $team) {
                $members = $team->members->pluck('student')->filter()->values();
                $expected = $members->count(); // tiap penilai menilai seluruh anggota (termasuk diri sendiri)

                foreach ($members as $student) {
                    $row = $received[$team->id][$student->id] ?? null;
                    $results[$team->id][] = [
                        'student' => $student,
                        'avg' => $row && $row->avg_score !== null ? round((float) $row->avg_score, 2) : null,
                        'received' => (int) ($row->received ?? 0),
                    ];

                    $submitted = (int) ($given[$team->id][$student->id]->submitted ?? 0);
                    $done = $expected > 0 && $submitted >= $expected;
                    if (! $done) {
                        $notDoneCount++;
                    }
                    $progress[$team->id][] = [
                        'student' => $student,
                        'submitted' => $submitted,
                        'expected' => $expected,
                        'done' => $done,
                    ];
                }
            }
        }

        return view('admin.peer-result.index', compact('stages', 'stage', 'teams', 'results', 'progress', 'notDoneCount', 'ay', 'classes', 'allTeams'));
    }
}
