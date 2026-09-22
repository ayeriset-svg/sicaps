<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Module;
use App\Models\ModuleLogbook;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Finalisasi presensi untuk tugas individu yang "dihitung sebagai presensi kelas":
 *  PASS → HADIR; belum kumpul / lewat deadline / ditolak → ALPA.
 */
class AssignmentAttendanceService
{
    /** Finalisasi satu modul (menandai present/absent seluruh mahasiswa target). */
    public function finalize(Module $module): array
    {
        if (! ($module->isIndividual() && $module->counts_as_attendance && $module->attendance_week && $module->attendance_session)) {
            return ['present' => 0, 'absent' => 0];
        }

        $students = User::where('role', 'mahasiswa')
            ->whereHas('memberships.team', fn ($q) => $q->where('academic_year_id', $module->academic_year_id))
            ->get();
        $approvedIds = ModuleLogbook::where('module_id', $module->id)
            ->where('status_approval', 'Approved')->whereNotNull('user_id')->pluck('user_id')->all();

        $present = 0;
        $absent = 0;
        foreach ($students as $s) {
            $status = in_array($s->id, $approvedIds, true) ? 'present' : 'absent';
            Attendance::updateOrCreate(
                [
                    'student_id' => $s->id,
                    'academic_year_id' => $module->academic_year_id,
                    'week_number' => $module->attendance_week,
                    'session_number' => $module->attendance_session,
                ],
                ['status' => $status, 'recorded_by' => Auth::id()]
            );
            $status === 'present' ? $present++ : $absent++;
        }

        $module->update(['attendance_finalized_at' => now()]);

        return ['present' => $present, 'absent' => $absent];
    }

    /**
     * Finalisasi otomatis SEMUA tugas-presensi yang deadline-nya sudah lewat &
     * belum difinalisasi (dipanggil lazily saat buka Presensi / rekalkulasi nilai).
     */
    public function finalizeDue(AcademicYear $ay): int
    {
        $modules = Module::where('academic_year_id', $ay->id)
            ->where('is_individual', true)
            ->where('counts_as_attendance', true)
            ->whereNotNull('attendance_week')->whereNotNull('attendance_session')
            ->whereNotNull('closes_at')->where('closes_at', '<', now())
            ->whereNull('attendance_finalized_at')
            ->get();

        foreach ($modules as $m) {
            $this->finalize($m);
        }

        return $modules->count();
    }
}
