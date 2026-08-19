<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FinalGrade;
use App\Models\ModuleLogbook;
use App\Models\Team;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Summary report: rekap nilai per kelas, sebaran indeks, rekap topik & HKI.
     */
    public function index(Request $request)
    {
        $years = AcademicYear::orderByDesc('year')->orderByDesc('semester')->get();
        $ay = $request->filled('year')
            ? $years->firstWhere('id', (int) $request->year)
            : (AcademicYear::active() ?? $years->first());

        $indices = config('capstone.grade_index');

        $recap = [];
        $distribution = array_fill_keys($indices, 0);
        $overallCount = 0;
        $overallSum = 0;

        // Rekap topik & HKI (dari tim tahun ajaran tsb).
        $topicMitra = 0;
        $topicMandiri = 0;
        $hkiCount = 0;

        // Rekap indikasi AI.
        $aiRecap = [];
        $aiCheckedTotal = 0;
        $aiSumTotal = 0.0;
        $aiHighTotal = 0;

        if ($ay) {
            $grades = FinalGrade::with('student')->where('academic_year_id', $ay->id)->get()->filter(fn ($g) => $g->student);
            foreach ($grades as $g) {
                $class = $g->student->class_name ?: '(Tanpa Kelas)';
                $letter = $g->grade_letter ?: 'E';
                $recap[$class] ??= array_merge(array_fill_keys($indices, 0), ['total' => 0, 'sum' => 0]);
                $recap[$class][$letter] = ($recap[$class][$letter] ?? 0) + 1;
                $recap[$class]['total']++;
                $recap[$class]['sum'] += $g->effective_score;
                $distribution[$letter] = ($distribution[$letter] ?? 0) + 1;
                $overallCount++;
                $overallSum += $g->effective_score;
            }
            ksort($recap);

            $teams = Team::with('topic', 'leader')->where('academic_year_id', $ay->id)->get();
            foreach ($teams as $t) {
                if ($t->hki_eligible) {
                    $hkiCount++;
                }
                if ($t->topic) {
                    if ($t->topic->origin === 'mandiri') {
                        $topicMandiri++;
                    } else {
                        $topicMitra++;
                    }
                }
            }

            // ---- Rekap indikasi AI per kelas (logbook yang sudah diperiksa) ----
            $teamClass = $teams->mapWithKeys(fn ($t) => [$t->id => ($t->leader?->class_name ?: '(Tanpa Kelas)')]);
            $checked = ModuleLogbook::whereIn('team_id', $teams->pluck('id'))
                ->whereNotNull('ai_checked_at')
                ->get(['team_id', 'ai_percentage']);
            foreach ($checked as $lb) {
                $class = $teamClass[$lb->team_id] ?? '(Tanpa Kelas)';
                $aiRecap[$class] ??= ['checked' => 0, 'sum' => 0.0, 'high' => 0];
                $aiRecap[$class]['checked']++;
                $aiRecap[$class]['sum'] += (float) $lb->ai_percentage;
                if ((float) $lb->ai_percentage >= 60) {
                    $aiRecap[$class]['high']++;
                }
                $aiCheckedTotal++;
                $aiSumTotal += (float) $lb->ai_percentage;
                if ((float) $lb->ai_percentage >= 60) {
                    $aiHighTotal++;
                }
            }
            ksort($aiRecap);
        }

        $overallAvg = $overallCount ? round($overallSum / $overallCount, 2) : 0;
        $teamCount = $topicMitra + $topicMandiri;
        $aiAvgTotal = $aiCheckedTotal ? round($aiSumTotal / $aiCheckedTotal, 1) : 0;

        return view('admin.reports.index', compact(
            'years', 'ay', 'indices', 'recap', 'distribution',
            'overallCount', 'overallAvg', 'topicMitra', 'topicMandiri', 'hkiCount', 'teamCount',
            'aiRecap', 'aiCheckedTotal', 'aiAvgTotal', 'aiHighTotal'
        ));
    }
}
