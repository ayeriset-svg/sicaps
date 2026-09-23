<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AssessmentScore;
use App\Models\FinalGrade;
use App\Services\GradeCalculationService;
use Illuminate\Support\Facades\Auth;

class GradeController extends Controller
{
    public function me(GradeCalculationService $service)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);
        $user = Auth::user();
        $team = $user->activeTeam($ay->id);

        // Nilai tersimpan selalu diperbarui otomatis saat nilai/presensi/peer berubah,
        // sehingga angka di sini sama dengan Rekap Nilai koordinator.
        $grade = FinalGrade::where('student_id', $user->id)->where('academic_year_id', $ay->id)->first();
        if (! $grade && $team) {
            $grade = $service->recalculateStudent($user, $ay);
        }
        if (! $grade) {
            return redirect()->route('team.index')->with('error', 'Anda belum tergabung dalam tim, sehingga belum memiliki nilai pada tahun ajaran ini.');
        }

        // Rincian rubrik per kriteria (nilai kelompok) + aturan penalti untuk transparansi.
        $stages = $ay->stages()->with('criteria')->get();
        $criterionScores = $team
            ? AssessmentScore::where('team_id', $team->id)
                ->whereIn('criterion_id', $stages->pluck('criteria')->flatten()->pluck('id'))
                ->pluck('score', 'criterion_id')
            : collect();
        $rules = $ay->penaltyRules()->get();

        return view('grade.me', compact('grade', 'ay', 'user', 'stages', 'criterionScores', 'rules'));
    }
}
