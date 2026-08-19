<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
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
        $logbooks = collect();
        $progress = 0;

        if ($ay && $team) {
            $modules = $ay->modules()->get();
            $logbooks = $team->logbooks()->get()->keyBy('module_id');
            $logbookModules = $modules->filter(fn ($m) => $m->isLogbook());
            $approved = $logbookModules->filter(fn ($m) => optional($logbooks[$m->id] ?? null)->status_approval === 'Approved')->count();
            $progress = $logbookModules->count() ? round($approved / $logbookModules->count() * 100) : 0;
        }

        return view('dashboard.mahasiswa', compact('ay', 'user', 'team', 'modules', 'logbooks', 'progress'));
    }
}
