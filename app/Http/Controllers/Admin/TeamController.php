<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $ay = AcademicYear::active();
        $teams = collect();
        if ($ay) {
            $teams = Team::with(['leader', 'topic.partner', 'members.student'])
                ->where('academic_year_id', $ay->id)
                ->when($request->filled('class'), fn ($q) => $q->whereHas('leader', fn ($l) => $l->where('class_name', $request->class)))
                ->orderBy('team_name')->get();
        }

        $classes = User::where('role', 'mahasiswa')->whereNotNull('class_name')
            ->distinct()->orderBy('class_name')->pluck('class_name');

        return view('admin.teams.index', compact('teams', 'ay', 'classes'));
    }

    public function toggleHki(Team $team)
    {
        $team->update(['hki_eligible' => ! $team->hki_eligible]);

        return back()->with('success', "Tim {$team->team_name} " . ($team->hki_eligible ? 'ditandai BERHAK diajukan HKI.' : 'dibatalkan dari HKI.'));
    }
}
