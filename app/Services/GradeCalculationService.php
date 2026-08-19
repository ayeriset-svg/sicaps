<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AssessmentScore;
use App\Models\AssessmentStage;
use App\Models\Attendance;
use App\Models\FinalGrade;
use App\Models\PeerEvaluation;
use App\Models\Team;
use App\Models\User;

/**
 * Grading engine (revisi RPS):
 *  - Nilai stage = rubrik kelompok*(1 - peer%) + rerata peer 180 stage*peer%.
 *  - NA = Σ (nilai stage × bobot stage).
 *  - Penalti individu berbasis jumlah HARI tidak hadir (pengurangan poin / fail E).
 *  - Grade letter mengikuti skala: NA>85 A; 75<NA<=85 AB; 65<NA<=75 B;
 *    60<NA<=65 BC; 50<NA<=60 C; 40<NA<=50 D; NA<=40 E.
 * Raw score (NA) tetap disimpan; override tidak menghapus data mentah.
 */
class GradeCalculationService
{
    public function recalculateAll(AcademicYear $ay): int
    {
        $stages = $ay->stages()->with('criteria')->get();
        $rules = $ay->penaltyRules()->get();

        $students = User::where('role', 'mahasiswa')
            ->whereHas('memberships.team', fn ($q) => $q->where('academic_year_id', $ay->id))
            ->get();

        foreach ($students as $student) {
            $this->recalculateStudent($student, $ay, $stages, $rules);
        }

        return $students->count();
    }

    public function recalculateStudent(User $student, AcademicYear $ay, $stages = null, $rules = null): FinalGrade
    {
        $stages ??= $ay->stages()->with('criteria')->get();
        $rules ??= $ay->penaltyRules()->get();

        $team = $student->activeTeam($ay->id);

        $breakdown = [];
        $na = 0.0;

        foreach ($stages as $stage) {
            $groupScore = $this->groupRubricScore($stage, $team);
            $peerWeight = (float) $stage->peer_weight_percentage / 100;
            $peerScore = $this->peerAverage($stage, $student, $team);
            $hasPeer = $peerScore !== null;

            // Blend hanya bila peer sudah ada nilainya; jika belum, pakai rubrik penuh.
            $stageScore = $hasPeer
                ? $groupScore * (1 - $peerWeight) + $peerScore * $peerWeight
                : $groupScore;
            $stageScore = round($stageScore, 2);

            $weighted = round($stageScore * (float) $stage->weight_percentage / 100, 2);
            $na += $weighted;

            $breakdown[] = [
                'code' => $stage->code,
                'name' => $stage->name,
                'weight' => (float) $stage->weight_percentage,
                'group_score' => round($groupScore, 2),
                'peer_weight' => (float) $stage->peer_weight_percentage,
                'peer_score' => $hasPeer ? round($peerScore, 2) : null,
                'stage_score' => $stageScore,
                'weighted' => $weighted,
            ];
        }
        $na = round($na, 2);

        // Penalti kehadiran (berbasis hari tidak hadir).
        $absentDays = $this->absentDays($student, $ay);
        $penaltyPoints = 0.0;
        $penaltyLevel = null;
        $forceFail = false;

        foreach ($rules as $rule) {
            if ($rule->matches($absentDays)) {
                $penaltyLevel = $rule->label;
                if ($rule->penalty_type === 'fail') {
                    $forceFail = true;
                } elseif ($rule->penalty_type === 'points_deduction') {
                    $penaltyPoints = (float) $rule->deduction_points;
                }
                break;
            }
        }

        $finalScore = $forceFail ? 0.0 : max(0, min(100, $na - $penaltyPoints));

        return FinalGrade::updateOrCreate(
            ['student_id' => $student->id, 'academic_year_id' => $ay->id],
            [
                'breakdown_json' => $breakdown,
                'raw_score' => $na,
                'absent_days' => $absentDays,
                'penalty_points' => $penaltyPoints,
                'penalty_level' => $penaltyLevel,
                'final_score' => $finalScore,
                'grade_letter' => $forceFail ? 'E' : $this->gradeLetter($finalScore),
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Rerata nilai rubrik kelompok (0-100) untuk seluruh kriteria pada stage.
     */
    public function groupRubricScore(AssessmentStage $stage, ?Team $team): float
    {
        if (! $team) {
            return 0.0;
        }

        $criterionIds = $stage->criteria->pluck('id');
        if ($criterionIds->isEmpty()) {
            return 0.0;
        }

        $scores = AssessmentScore::whereIn('criterion_id', $criterionIds)
            ->where('team_id', $team->id)
            ->pluck('score');

        if ($scores->isEmpty()) {
            return 0.0;
        }

        // Rata-rata terhadap seluruh kriteria (kriteria tanpa nilai dihitung 0).
        return round($scores->sum() / $criterionIds->count(), 2);
    }

    /**
     * Rerata final_peer_score yang diterima mahasiswa pada stage tsb.
     * Null jika belum ada penilaian peer.
     */
    public function peerAverage(AssessmentStage $stage, User $student, ?Team $team): ?float
    {
        if (! $team) {
            return null;
        }

        $avg = PeerEvaluation::where('stage_id', $stage->id)
            ->where('team_id', $team->id)
            ->where('evaluatee_id', $student->id)
            ->avg('final_peer_score');

        return $avg === null ? null : round((float) $avg, 2);
    }

    /**
     * Jumlah hari (sesi) tidak hadir (status 'absent').
     */
    public function absentDays(User $student, AcademicYear $ay): int
    {
        return Attendance::where('student_id', $student->id)
            ->where('academic_year_id', $ay->id)
            ->where('status', 'absent')
            ->count();
    }

    public function gradeLetter(float $score): string
    {
        foreach (config('capstone.grade_scale') as $tier) {
            if ($score > $tier['gt']) {
                return $tier['letter'];
            }
        }

        return 'E';
    }
}
