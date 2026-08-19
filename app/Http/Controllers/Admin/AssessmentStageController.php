<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AssessmentCriterion;
use App\Models\AssessmentStage;
use Illuminate\Http\Request;

class AssessmentStageController extends Controller
{
    public function index()
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Aktifkan tahun ajaran terlebih dahulu.');

        $stages = $ay->stages()->with('criteria')->get();
        $total = $stages->sum('weight_percentage');

        return view('admin.stages.index', compact('stages', 'ay', 'total'));
    }

    public function update(Request $request, AssessmentStage $stage)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'weight_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'peer_weight_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $stage->update($data);

        return back()->with('success', 'Bobot stage diperbarui. Jalankan rekalkulasi nilai.');
    }

    /**
     * Buka/tutup peer 180 untuk stage ini.
     */
    public function togglePeer(AssessmentStage $stage)
    {
        $stage->update(['peer_open' => ! $stage->peer_open]);

        return back()->with('success', 'Peer 180° stage ' . $stage->code . ' ' . ($stage->peer_open ? 'DIBUKA' : 'DITUTUP') . '.');
    }

    public function addCriterion(Request $request, AssessmentStage $stage)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150']]);
        $stage->criteria()->create([
            'name' => $data['name'],
            'order_index' => ($stage->criteria()->max('order_index') ?? 0) + 1,
        ]);

        return back()->with('success', 'Kriteria ditambahkan.');
    }

    public function destroyCriterion(AssessmentCriterion $criterion)
    {
        $criterion->delete();

        return back()->with('success', 'Kriteria dihapus.');
    }
}
