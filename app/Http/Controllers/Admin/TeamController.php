<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\GradeCalculationService;
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

        // Mahasiswa yang belum bertim, dikelompokkan per kelas (untuk tambah anggota oleh koordinator).
        $availableByClass = collect();
        if ($ay) {
            $usedIds = TeamMember::whereHas('team', fn ($q) => $q->where('academic_year_id', $ay->id))->pluck('student_id');
            $availableByClass = User::where('role', 'mahasiswa')->whereNotIn('id', $usedIds)
                ->orderBy('name')->get(['id', 'name', 'identity_number', 'class_name'])->groupBy('class_name');
        }

        return view('admin.teams.index', compact('teams', 'ay', 'classes', 'availableByClass'));
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

    /**
     * Koordinator menambah anggota (juga saat susunan tim sudah terkunci untuk mahasiswa).
     * Aturan tetap sama: mahasiswa sekelas dengan ketua, belum bertim, & kapasitas maksimal.
     */
    public function addMember(Request $request, Team $team, GradeCalculationService $grades)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'assigned_role' => ['required', 'string', 'max:255'],
        ]);

        $max = config('capstone.team_max_members');
        if ($team->members()->count() >= $max) {
            return back()->with('error', "Kapasitas tim maksimal {$max} orang.");
        }
        $candidate = User::where('id', $data['student_id'])->where('role', 'mahasiswa')->first();
        if (! $candidate || $candidate->class_name !== $team->class_name) {
            return back()->with('error', 'Anggota harus mahasiswa dari kelas yang sama dengan ketua (' . ($team->class_name ?? '-') . ').');
        }
        $already = TeamMember::where('student_id', $candidate->id)
            ->whereHas('team', fn ($q) => $q->where('academic_year_id', $team->academic_year_id))->exists();
        if ($already) {
            return back()->with('error', 'Mahasiswa tersebut sudah tergabung di tim lain.');
        }

        $team->members()->create($data);
        $grades->recalculateTeam($team);

        return back()->with('success', "{$candidate->name} ditambahkan ke tim {$team->team_name}.");
    }

    /** Koordinator mengeluarkan anggota (ketua tidak dapat dikeluarkan — ganti ketua dulu). */
    public function removeMember(Team $team, TeamMember $member, GradeCalculationService $grades)
    {
        abort_if($member->team_id !== $team->id, 404);
        if ($member->student_id === $team->leader_id) {
            return back()->with('error', 'Ketua tim tidak dapat dikeluarkan. Ganti ketua terlebih dahulu melalui Edit Tim.');
        }

        $name = $member->student?->name;
        $member->delete();
        $grades->forgetStudents([$member->student_id], $team->academic_year_id);

        return back()->with('success', "{$name} dikeluarkan dari tim {$team->team_name}.");
    }

    /** Hapus tim beserta seluruh data terkait (logbook, nilai, dll — cascade). */
    public function destroy(Team $team, GradeCalculationService $grades)
    {
        $name = $team->team_name;
        // Nilai akhir tidak terhubung ke tim (FK), jadi dibersihkan manual agar tidak jadi nilai "yatim".
        $grades->forgetStudents($team->members()->pluck('student_id'), $team->academic_year_id);
        $team->delete();

        return back()->with('success', "Tim {$name} dihapus beserta seluruh data terkaitnya.");
    }
}
