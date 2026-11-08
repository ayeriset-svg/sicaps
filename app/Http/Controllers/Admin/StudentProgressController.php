<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ModuleLogbook;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class StudentProgressController extends Controller
{
    /**
     * Pemantauan PER MAHASISWA: modul/tugas apa saja yang sudah dikerjakan tiap
     * mahasiswa (melengkapi pemantauan per-modul di Review Logbook).
     *
     * - Tugas individu  → submission milik mahasiswa itu sendiri.
     * - Logbook tim      → status tim berlaku untuk seluruh anggotanya.
     * Modul yang disembunyikan (is_hidden) & modul tanpa pengerjaan dikecualikan.
     */
    public function index(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Aktifkan tahun ajaran terlebih dahulu.');

        // Modul yang bisa dikerjakan & terlihat mahasiswa.
        $modules = $ay->modules()
            ->where('requires_submission', true)
            ->where('is_hidden', false)
            ->orderBy('order_index')->get();
        $moduleIds = $modules->pluck('id');

        // Peta mahasiswa → tim (via anggota tim di tahun ajaran aktif).
        $teams = Team::with('members')->where('academic_year_id', $ay->id)->get();
        $studentTeam = [];
        foreach ($teams as $team) {
            foreach ($team->members as $m) {
                $studentTeam[$m->student_id] = $team;
            }
        }

        // Semua submission relevan (sekali query), diindeks untuk lookup cepat.
        $indiv = [];      // [module_id][user_id] = status
        $teamSub = [];    // [module_id][team_id] = status
        if ($moduleIds->isNotEmpty() && $teams->isNotEmpty()) {
            $logs = ModuleLogbook::whereIn('module_id', $moduleIds)
                ->whereIn('team_id', $teams->pluck('id'))
                ->get(['module_id', 'team_id', 'user_id', 'status_approval']);
            foreach ($logs as $lb) {
                if ($lb->user_id) {
                    $indiv[$lb->module_id][$lb->user_id] = $lb->status_approval;
                } else {
                    $teamSub[$lb->module_id][$lb->team_id] = $lb->status_approval;
                }
            }
        }

        // Mahasiswa (hanya yang punya tim; difilter kelas & pencarian).
        $students = User::where('role', 'mahasiswa')
            ->whereIn('id', array_keys($studentTeam))
            ->when($request->filled('class'), fn ($q) => $q->where('class_name', $request->class))
            ->when($request->filled('team'), fn ($q) => $q->whereIn('id',
                collect($studentTeam)->filter(fn ($t) => $t->id === (int) $request->team)->keys()->all() ?: [0]))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%' . $request->q . '%')
                ->orWhere('identity_number', 'like', '%' . $request->q . '%')))
            ->orderBy('class_name')->orderBy('name')->get();

        // Susun baris progres per mahasiswa.
        $rows = [];
        foreach ($students as $student) {
            $team = $studentTeam[$student->id];
            $items = [];
            $done = 0;
            $submitted = 0;
            foreach ($modules as $mod) {
                $status = $mod->isIndividual()
                    ? ($indiv[$mod->id][$student->id] ?? 'Not Started')
                    : ($teamSub[$mod->id][$team->id] ?? 'Not Started');
                if ($status === 'Approved') {
                    $done++;
                }
                if ($status !== 'Not Started') {
                    $submitted++;
                }
                $items[] = ['module' => $mod, 'status' => $status];
            }
            $rows[] = [
                'student' => $student,
                'team' => $team,
                'items' => $items,
                'done' => $done,
                'submitted' => $submitted,
                'total' => $modules->count(),
            ];
        }

        $classes = User::where('role', 'mahasiswa')->whereNotNull('class_name')->distinct()->orderBy('class_name')->pluck('class_name');
        $allTeams = $teams->sortBy('team_name')->values();

        return view('admin.student-progress.index', compact('rows', 'modules', 'ay', 'classes', 'allTeams'));
    }
}
