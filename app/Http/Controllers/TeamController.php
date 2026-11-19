<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\GradeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Kunci baris mahasiswa agar klik ganda / dua tab tidak membuat 2 tim sekaligus.
        $created = DB::transaction(function () use ($ay, $user, $data) {
            User::whereKey($user->id)->lockForUpdate()->first();
            if (Team::studentHasTeam($user->id, $ay->id)) {
                return false;
            }
            $team = Team::create([
                'academic_year_id' => $ay->id,
                'team_name' => $data['team_name'],
                'leader_id' => $user->id,
                'case_type' => $data['case_type'],
            ]);
            $team->members()->create(['student_id' => $user->id, 'assigned_role' => $data['leader_role']]);

            return true;
        });
        if (! $created) {
            return redirect()->route('team.index')->with('error', 'Anda sudah tergabung dalam sebuah tim pada tahun ajaran ini.');
        }

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

        if ($error = $team->enrollStudent($candidate->id, $data['assigned_role'])) {
            return back()->with('error', $error);
        }

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

    /**
     * Anggota (bukan ketua) menolak dimasukkan ke tim / keluar dari tim,
     * selama susunan anggota belum terkunci.
     */
    public function leave(Team $team, GradeCalculationService $grades)
    {
        $member = $team->members()->where('student_id', Auth::id())->first();
        abort_unless($member, 403, 'Anda bukan anggota tim ini.');
        if ($team->leader_id === Auth::id()) {
            return back()->with('error', 'Ketua tim tidak dapat keluar dari tim. Hubungi koordinator bila tim perlu dibubarkan atau ganti ketua.');
        }
        if ($team->isMembershipLocked()) {
            return back()->with('error', self::LOCKED_MESSAGE);
        }

        $member->delete();
        $grades->forgetStudents([$member->student_id], $team->academic_year_id);

        return redirect()->route('team.index')->with('success', "Anda sudah keluar dari tim {$team->team_name}. Silakan buat tim baru atau minta ketua tim lain menambahkan Anda.");
    }

    private function authorizeLeader(Team $team): void
    {
        abort_unless(Auth::id() === $team->leader_id, 403, 'Hanya ketua tim yang dapat mengubah data ini.');
    }
}
