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
 *  PASS → HADIR; tidak mengumpulkan / ditolak → ALPA.
 *  Sudah mengumpulkan tapi belum final direview (Pending / Perlu Revisi) → dibiarkan;
 *  status presensinya ditetapkan saat review (lihat LogbookReviewController).
 *  Izin/Sakit yang dicatat manual koordinator tidak pernah ditimpa menjadi ALPA.
 */
class AssignmentAttendanceService
{
    /** Status presensi manual yang tidak boleh ditimpa proses otomatis. */
    public const PROTECTED_STATUSES = ['permit', 'sick'];

    public function __construct(private GradeCalculationService $grades)
    {
    }

    /** Finalisasi satu modul (menandai present/absent seluruh mahasiswa target). */
    public function finalize(Module $module, bool $recalculate = true): array
    {
        $result = ['present' => 0, 'absent' => 0, 'waiting' => 0, 'kept' => 0];
        if (! ($module->isIndividual() && $module->counts_as_attendance && $module->attendance_week && $module->attendance_session)) {
            return $result;
        }

        $students = User::where('role', 'mahasiswa')
            ->whereHas('memberships.team', fn ($q) => $q->where('academic_year_id', $module->academic_year_id))
            ->get();
        $subs = ModuleLogbook::where('module_id', $module->id)->whereNotNull('user_id')
            ->get(['user_id', 'status_approval', 'submitted_at'])->keyBy('user_id');
        $existing = Attendance::where('academic_year_id', $module->academic_year_id)
            ->where('week_number', $module->attendance_week)
            ->where('session_number', $module->attendance_session)
            ->pluck('status', 'student_id');

        $changed = [];
        foreach ($students as $s) {
            $sub = $subs[$s->id] ?? null;
            $st = $sub?->status_approval;

            if ($st === 'Approved') {
                $status = 'present';
            } elseif ($sub && $sub->submitted_at && in_array($st, ['Pending', 'Revision Needed'], true)) {
                $result['waiting']++; // sudah kumpul, tunggu keputusan review

                continue;
            } else {
                $status = 'absent'; // tidak mengumpulkan / ditolak
            }

            if ($status === 'absent' && in_array($existing[$s->id] ?? null, self::PROTECTED_STATUSES, true)) {
                $result['kept']++; // izin/sakit manual dipertahankan

                continue;
            }

            Attendance::updateOrCreate(
                [
                    'student_id' => $s->id,
                    'academic_year_id' => $module->academic_year_id,
                    'week_number' => $module->attendance_week,
                    'session_number' => $module->attendance_session,
                ],
                ['status' => $status, 'recorded_by' => Auth::id()]
            );
            $result[$status]++;
            if (($existing[$s->id] ?? null) !== $status) {
                $changed[] = $s->id;
            }
        }

        $module->update(['attendance_finalized_at' => now()]);

        if ($recalculate && $changed && $module->academicYear) {
            $this->grades->recalculateStudentIds($changed, $module->academicYear);
        }

        return $result;
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
