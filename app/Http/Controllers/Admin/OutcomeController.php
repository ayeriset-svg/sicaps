<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Clo;
use App\Models\Plo;
use App\Models\SubClo;
use Illuminate\Http\Request;

/**
 * Kelola capaian pembelajaran (PLO → CLO → Sub-CLO) per tahun ajaran aktif.
 */
class OutcomeController extends Controller
{
    public function index()
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Aktifkan tahun ajaran terlebih dahulu.');

        $plos = Plo::where('academic_year_id', $ay->id)->orderBy('order_index')->orderBy('id')->get();
        $clos = Clo::with('plo')->where('academic_year_id', $ay->id)->orderBy('order_index')->orderBy('id')->get();
        $subClos = SubClo::with('clo')->where('academic_year_id', $ay->id)->orderBy('order_index')->orderBy('id')->get();

        return view('admin.outcomes.index', compact('ay', 'plos', 'clos', 'subClos'));
    }

    /* ---------------- PLO ---------------- */
    public function storePlo(Request $request)
    {
        $this->savePlo($request, new Plo(['academic_year_id' => $this->ayId()]));

        return back()->with('success', 'PLO ditambahkan.');
    }

    public function updatePlo(Request $request, Plo $plo)
    {
        $this->savePlo($request, $plo);

        return back()->with('success', 'PLO diperbarui.');
    }

    public function destroyPlo(Plo $plo)
    {
        $plo->delete();

        return back()->with('success', 'PLO dihapus.');
    }

    /* ---------------- CLO ---------------- */
    public function storeClo(Request $request)
    {
        $this->saveClo($request, new Clo(['academic_year_id' => $this->ayId()]));

        return back()->with('success', 'CLO ditambahkan.');
    }

    public function updateClo(Request $request, Clo $clo)
    {
        $this->saveClo($request, $clo);

        return back()->with('success', 'CLO diperbarui.');
    }

    public function destroyClo(Clo $clo)
    {
        $clo->delete();

        return back()->with('success', 'CLO dihapus (beserta Sub-CLO di bawahnya).');
    }

    /* ---------------- Sub-CLO ---------------- */
    public function storeSubClo(Request $request)
    {
        $this->saveSubClo($request, new SubClo(['academic_year_id' => $this->ayId()]));

        return back()->with('success', 'Sub-CLO ditambahkan.');
    }

    public function updateSubClo(Request $request, SubClo $subClo)
    {
        $this->saveSubClo($request, $subClo);

        return back()->with('success', 'Sub-CLO diperbarui.');
    }

    public function destroySubClo(SubClo $subClo)
    {
        $subClo->delete();

        return back()->with('success', 'Sub-CLO dihapus.');
    }

    /* ---------------- helpers ---------------- */
    private function ayId(): int
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);

        return $ay->id;
    }

    private function savePlo(Request $request, Plo $plo): void
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'order_index' => ['nullable', 'integer', 'min:0'],
        ]);
        $plo->fill($data + ['order_index' => $data['order_index'] ?? 0])->save();
    }

    private function saveClo(Request $request, Clo $clo): void
    {
        $data = $request->validate([
            'plo_id' => ['nullable', 'exists:plos,id'],
            'code' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'order_index' => ['nullable', 'integer', 'min:0'],
        ]);
        $clo->fill($data + ['order_index' => $data['order_index'] ?? 0])->save();
    }

    private function saveSubClo(Request $request, SubClo $subClo): void
    {
        $data = $request->validate([
            'clo_id' => ['required', 'exists:clos,id'],
            'code' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'order_index' => ['nullable', 'integer', 'min:0'],
        ]);
        $subClo->fill($data + ['order_index' => $data['order_index'] ?? 0])->save();
    }
}
