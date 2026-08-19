<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Aktifkan tahun ajaran terlebih dahulu.');

        // Filter kelompok (tim) — bila dipilih, hanya anggota tim tsb.
        $memberIds = null;
        if ($request->filled('team')) {
            $team = Team::with('members')->find($request->team);
            $memberIds = $team ? $team->members->pluck('student_id')->all() : [];
        }

        $students = User::where('role', 'mahasiswa')
            ->whereHas('memberships.team', fn ($q) => $q->where('academic_year_id', $ay->id))
            ->when($request->filled('class'), fn ($q) => $q->where('class_name', $request->class))
            ->when($memberIds !== null, fn ($q) => $q->whereIn('id', $memberIds ?: [0]))
            ->orderBy('class_name')->orderBy('name')->get();

        $classes = User::where('role', 'mahasiswa')->whereNotNull('class_name')->distinct()->orderBy('class_name')->pluck('class_name');
        $allTeams = Team::where('academic_year_id', $ay->id)->orderBy('team_name')->get(['id', 'team_name']);

        $totalWeeks = config('capstone.total_weeks');
        $sessions = config('capstone.sessions_per_week');

        // matrix[student_id]["w-s"] = status (hanya record yang benar-benar ada; sisanya null)
        $matrix = [];
        $absentDays = [];
        $rows = Attendance::where('academic_year_id', $ay->id)
            ->whereIn('student_id', $students->pluck('id'))->get();
        foreach ($rows as $r) {
            $matrix[$r->student_id][$r->week_number . '-' . $r->session_number] = $r->status;
        }
        foreach ($students as $s) {
            $absentDays[$s->id] = $rows->where('student_id', $s->id)->where('status', 'absent')->count();
        }

        return view('admin.attendance.index', compact('students', 'classes', 'allTeams', 'ay', 'totalWeeks', 'sessions', 'matrix', 'absentDays'));
    }

    public function store(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);

        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'week_number' => ['required', 'integer', 'min:1', 'max:16'],
            'session_number' => ['required', 'integer', 'min:1', 'max:2'],
            'status' => ['required', 'in:present,permit,sick,absent,clear'],
        ]);

        // 'clear' → hapus record (kembali ke null/belum diisi).
        if ($data['status'] === 'clear') {
            Attendance::where([
                'student_id' => $data['student_id'],
                'academic_year_id' => $ay->id,
                'week_number' => $data['week_number'],
                'session_number' => $data['session_number'],
            ])->delete();

            return back()->with('success', 'Presensi dikosongkan.');
        }

        Attendance::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'academic_year_id' => $ay->id,
                'week_number' => $data['week_number'],
                'session_number' => $data['session_number'],
            ],
            ['status' => $data['status'], 'recorded_by' => Auth::id()]
        );

        return back()->with('success', 'Presensi disimpan.');
    }
}
