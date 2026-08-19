<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Tidak ada tahun ajaran aktif.');

        $team = $user->activeTeam($ay->id);
        $team?->load(['members.student', 'leader']);

        $available = collect();
        if ($team && $team->leader_id === $user->id) {
            $usedIds = TeamMember::whereHas('team', fn ($q) => $q->where('academic_year_id', $ay->id))->pluck('student_id')->toArray();
            $available = User::where('role', 'mahasiswa')->whereNotIn('id', $usedIds)->orderBy('name')->get();
        }

        return view('team.index', compact('team', 'ay', 'user', 'available'));
    }

    public function store(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);
        $user = Auth::user();
        abort_if($user->activeTeam($ay->id), 403, 'Anda sudah tergabung dalam sebuah tim.');

        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:100'],
            'case_type' => ['required', Rule::in(array_keys(config('capstone.case_types')))],
            'leader_role' => ['required', 'string', 'max:255'],
        ]);

        $team = Team::create([
            'academic_year_id' => $ay->id,
            'team_name' => $data['team_name'],
            'leader_id' => $user->id,
            'case_type' => $data['case_type'],
        ]);
        $team->members()->create(['student_id' => $user->id, 'assigned_role' => $data['leader_role']]);

        return redirect()->route('team.index')->with('success', 'Tim berhasil dibuat. Tambahkan anggota (maks ' . config('capstone.team_max_members') . ' orang).');
    }

    public function update(Request $request, Team $team)
    {
        $this->authorizeLeader($team);
        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:100'],
            'case_type' => ['required', Rule::in(array_keys(config('capstone.case_types')))],
        ]);
        $team->update($data);

        return back()->with('success', 'Data tim diperbarui.');
    }

    public function addMember(Request $request, Team $team)
    {
        $this->authorizeLeader($team);
        $max = config('capstone.team_max_members');
        abort_if($team->members()->count() >= $max, 422, "Kapasitas tim maksimal {$max} orang.");

        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'assigned_role' => ['required', 'string', 'max:255'],
        ]);

        $already = TeamMember::where('student_id', $data['student_id'])
            ->whereHas('team', fn ($q) => $q->where('academic_year_id', $team->academic_year_id))->exists();
        if ($already) {
            return back()->with('error', 'Mahasiswa tersebut sudah tergabung di tim lain.');
        }

        $team->members()->create($data);

        return back()->with('success', 'Anggota ditambahkan.');
    }

    public function removeMember(Team $team, TeamMember $member)
    {
        $this->authorizeLeader($team);
        abort_if($member->team_id !== $team->id, 404);
        abort_if($member->student_id === $team->leader_id, 422, 'Ketua tim tidak dapat dihapus.');
        $member->delete();

        return back()->with('success', 'Anggota dihapus dari tim.');
    }

    private function authorizeLeader(Team $team): void
    {
        abort_unless(Auth::id() === $team->leader_id, 403, 'Hanya ketua tim yang dapat mengubah data ini.');
    }
}
