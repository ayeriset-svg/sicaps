<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ModuleLogbook;
use App\Models\Team;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $ay = AcademicYear::active();

        return $user->isSuperadmin()
            ? $this->admin($ay)
            : $this->mahasiswa($user, $ay);
    }

    private function admin(?AcademicYear $ay)
    {
        $stats = [
            'mahasiswa' => User::where('role', 'mahasiswa')->count(),
            'teams' => $ay ? Team::where('academic_year_id', $ay->id)->count() : 0,
            'topic_pending' => $ay ? Team::where('academic_year_id', $ay->id)->where('topic_status', 'pending')->count() : 0,
            'logbook_pending' => $ay
                ? ModuleLogbook::whereHas('team', fn ($q) => $q->where('academic_year_id', $ay->id))->where('status_approval', 'Pending')->count()
                : 0,
        ];

        $recentTeams = $ay
            ? Team::with(['leader', 'topic', 'members'])->where('academic_year_id', $ay->id)->latest()->take(6)->get()
            : collect();

        return view('dashboard.admin', compact('ay', 'stats', 'recentTeams'));
    }

    private function mahasiswa(User $user, ?AcademicYear $ay)
    {
        $team = $user->activeTeam($ay?->id);
        $modules = collect();
        $subs = [];
        $progress = 0;
        $deadlines = collect();
        $attendance = null;

        // Rekap presensi mahasiswa (untuk semua mahasiswa, walau belum bertim).
        if ($ay) {
            $rows = Attendance::where('student_id', $user->id)->where('academic_year_id', $ay->id)->get();
            $totalSessions = (int) config('capstone.total_weeks', 16) * (int) config('capstone.sessions_per_week', 2);
            $present = $rows->where('status', 'present')->count();
            $absent = $rows->where('status', 'absent')->count();
            $recorded = $rows->count();

            // Peringatan mengikuti aturan penalti yang diatur koordinator (bukan angka tetap).
            $rules = $ay->penaltyRules()->get();
            $currentRule = $rules->first(fn ($r) => $r->matches($absent) && $r->penalty_type !== 'none');
            $nextRule = $rules->first(fn ($r) => $r->min_days > $absent && $r->penalty_type !== 'none');

            $attendance = [
                'present' => $present,
                'permit' => $rows->where('status', 'permit')->count(),
                'sick' => $rows->where('status', 'sick')->count(),
                'absent' => $absent,
                'recorded' => $recorded,
                'total' => $totalSessions,
                // Persentase dari sesi yang SUDAH tercatat (bukan 32 sesi penuh), agar tidak
                // tampak rendah di awal semester.
                'percent' => $recorded ? round($present / $recorded * 100) : null,
                'current_rule' => $currentRule,
                'next_rule' => $nextRule,
            ];
        }

        if ($ay && $team) {
            $modules = $ay->modules()->get();
            $ids = $modules->pluck('id');

            // Submission relevan per modul: tugas individu = milik user; lainnya = milik tim.
            $teamLogbooks = $team->logbooks()->whereNull('user_id')->get()->keyBy('module_id');
            $myLogbooks = ModuleLogbook::where('user_id', $user->id)->whereIn('module_id', $ids)->get()->keyBy('module_id');
            foreach ($modules as $m) {
                $subs[$m->id] = $m->isIndividual() ? ($myLogbooks[$m->id] ?? null) : ($teamLogbooks[$m->id] ?? null);
            }

            $logbookModules = $modules->filter(fn ($m) => $m->requiresSubmission());
            $approved = $logbookModules->filter(fn ($m) => optional($subs[$m->id] ?? null)->status_approval === 'Approved')->count();
            $progress = $logbookModules->count() ? round($approved / $logbookModules->count() * 100) : 0;

            // Notifikasi deadline: yang sedang berlangsung & punya batas waktu, terurut terdekat.
            $deadlines = $modules
                ->filter(fn ($m) => $m->closes_at && $m->scheduleState() === 'open' && $m->requiresSubmission())
                ->sortBy('closes_at')
                ->map(fn ($m) => (object) [
                    'module' => $m,
                    'status' => optional($subs[$m->id] ?? null)->status_approval ?? 'Not Started',
                    'days' => $m->daysToDeadline(),
                ])
                ->values();
        }

        return view('dashboard.mahasiswa', compact('ay', 'user', 'team', 'modules', 'subs', 'progress', 'deadlines', 'attendance'));
    }
}
