<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\GradeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    private const LOCKED_MESSAGE = 'Susunan anggota tim sudah terkunci karena Assessment 1 tim ini sudah mulai dinilai. Hubungi koordinator untuk perubahan anggota.';

    public function index()
    {
        $user = Auth::user();
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Tidak ada tahun ajaran aktif.');

        $team = $user->activeTeam($ay->id);
        $team?->load(['members.student', 'leader']);

        $available = collect();
        // Hanya ketua yang menambah anggota, dan pilihan dibatasi ke KELAS yang sama dengan ketua.
        if ($team && $team->leader_id === $user->id && $user->class_name) {
            $usedIds = TeamMember::whereHas('team', fn ($q) => $q->where('academic_year_id', $ay->id))->pluck('student_id')->toArray();
            $available = User::where('role', 'mahasiswa')
                ->where('class_name', $user->class_name)
                ->whereNotIn('id', $usedIds)
                ->orderBy('name')->get();
        }

        $locked = $team?->isMembershipLocked() ?? false;

        return view('team.index', compact('team', 'ay', 'user', 'available', 'locked'));
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
        if ($team->isMembershipLocked()) {
            return back()->with('error', self::LOCKED_MESSAGE);
        }
        $max = config('capstone.team_max_members');
        abort_if($team->members()->count() >= $max, 422, "Kapasitas tim maksimal {$max} orang.");

        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'assigned_role' => ['required', 'string', 'max:255'],
        ]);

        // Anggota wajib mahasiswa & dari KELAS yang sama dengan ketua tim.
        $candidate = User::where('id', $data['student_id'])->where('role', 'mahasiswa')->first();
        if (! $candidate) {
            return back()->with('error', 'Data mahasiswa tidak ditemukan.');
        }
        if ($candidate->class_name !== $team->class_name) {
            return back()->with('error', 'Hanya mahasiswa dari kelas yang sama (' . ($team->class_name ?? '-') . ') yang dapat ditambahkan ke tim.');
        }

        $already = TeamMember::where('student_id', $data['student_id'])
            ->whereHas('team', fn ($q) => $q->where('academic_year_id', $team->academic_year_id))->exists();
        if ($already) {
            return back()->with('error', 'Mahasiswa tersebut sudah tergabung di tim lain.');
        }

        $team->members()->create($data);

        return back()->with('success', 'Anggota ditambahkan.');
    }

    public function removeMember(Team $team, TeamMember $member, GradeCalculationService $grades)
    {
        $this->authorizeLeader($team);
        abort_if($member->team_id !== $team->id, 404);
        abort_if($member->student_id === $team->leader_id, 422, 'Ketua tim tidak dapat dihapus.');
        if ($team->isMembershipLocked()) {
            return back()->with('error', self::LOCKED_MESSAGE);
        }
        $member->delete();
        // Nilai hasil hitung dari tim ini tidak lagi berlaku bagi mahasiswa yang dikeluarkan.
        $grades->forgetStudents([$member->student_id], $team->academic_year_id);

        return back()->with('success', 'Anggota dihapus dari tim.');
    }

    private function authorizeLeader(Team $team): void
    {
        abort_unless(Auth::id() === $team->leader_id, 403, 'Hanya ketua tim yang dapat mengubah data ini.');
    }
}
