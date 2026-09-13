<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    /** Edit data tim oleh superadmin (nama, ranah, ketua). */
    public function update(Request $request, Team $team)
    {
        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:100'],
            'case_type' => ['nullable', Rule::in(array_keys(config('capstone.case_types')))],
            'leader_id' => ['required', 'exists:users,id'],
        ]);

        // Ketua harus salah satu anggota tim ini.
        $memberIds = $team->members()->pluck('student_id')->map(fn ($v) => (int) $v)->all();
        if (! in_array((int) $data['leader_id'], $memberIds, true)) {
            return back()->with('error', 'Ketua harus salah satu anggota tim.');
        }

        $team->update($data);

        return back()->with('success', 'Data tim diperbarui.');
    }

    /** Hapus tim beserta seluruh data terkait (logbook, nilai, dll — cascade). */
    public function destroy(Team $team)
    {
        $name = $team->team_name;
        $team->delete();

        return back()->with('success', "Tim {$name} dihapus beserta seluruh data terkaitnya.");
    }
}
