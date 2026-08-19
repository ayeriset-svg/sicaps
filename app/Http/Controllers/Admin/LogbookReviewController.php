<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ModuleLogbook;
use App\Services\AiDetectionService;
use App\Services\LogbookWorkflowService;
use App\Services\ProofreaderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LogbookReviewController extends Controller
{
    public function index(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);

        $teamIds = $ay->teams()->pluck('id');

        $logbooks = ModuleLogbook::with('team.leader', 'module')
            ->whereIn('team_id', $teamIds)
            ->when($request->filled('status'), fn ($q) => $q->where('status_approval', $request->status))
            ->where('status_approval', '!=', 'Not Started')
            ->orderByRaw("FIELD(status_approval,'Pending','Revision Needed','Approved')")
            ->orderByDesc('submitted_at')
            ->get();

        return view('admin.logbook-review.index', compact('logbooks', 'ay'));
    }

    public function show(ModuleLogbook $logbook)
    {
        $logbook->load('team.members.student', 'module', 'versions.author');

        return view('admin.logbook-review.show', compact('logbook'));
    }

    public function review(Request $request, ModuleLogbook $logbook, LogbookWorkflowService $workflow)
    {
        $data = $request->validate([
            'status_approval' => ['required', Rule::in(['Approved', 'Revision Needed'])],
            'feedback' => ['nullable', 'string'],
        ]);

        $workflow->review($logbook, $data['status_approval'], $data['feedback'] ?? null, Auth::user());

        return back()->with('success', 'Review logbook disimpan.');
    }

    /**
     * Periksa indikasi penggunaan AI (teks + gambar) pada isi logbook.
     * Hasil disimpan & akan tampil ke mahasiswa bersama feedback.
     */
    public function checkAi(ModuleLogbook $logbook, AiDetectionService $ai)
    {
        $logbook->load('module');
        $result = $ai->analyze($logbook);

        $logbook->update([
            'ai_percentage' => $result['overall'],
            'ai_text_percentage' => $result['text'],
            'ai_image_percentage' => $result['image'],
            'ai_detail_json' => $result['detail'],
            'ai_checked_at' => now(),
        ]);

        $pct = $result['overall'] !== null ? number_format($result['overall'], 1) . '%' : 'N/A (data kurang)';

        return back()->with('success', "Pemeriksaan AI selesai — estimasi indikasi AI: {$pct}. Hasil akan tampil ke mahasiswa.");
    }

    /**
     * Periksa tata tulis (proofreader / technical editor) pada isi logbook.
     * Rule-based: ejaan, istilah asing, kata ganti, konsistensi, rujukan, struktur, kapitalisasi.
     */
    public function proofread(ModuleLogbook $logbook, ProofreaderService $proofreader)
    {
        $logbook->load('module');
        $result = $proofreader->check($logbook);

        $logbook->update([
            'proofread_score' => $result['score'],
            'proofread_json' => $result,
            'proofread_checked_at' => now(),
        ]);

        $score = $result['score'] !== null ? $result['score'] . '/100' : 'N/A (data kurang)';

        return back()->with('success', "Pemeriksaan tata tulis selesai — skor {$score}, {$result['total_issues']} catatan.");
    }

    /**
     * Cetak / generate PDF logbook (sisi superadmin) — mengikuti template dokumen.
     */
    public function print(ModuleLogbook $logbook)
    {
        $logbook->load('team.members.student', 'team.leader', 'team.topic.partner', 'module', 'team.academicYear');

        return view('logbook.print', [
            'team' => $logbook->team,
            'module' => $logbook->module,
            'logbook' => $logbook,
            'ay' => $logbook->team->academicYear,
        ]);
    }
}
