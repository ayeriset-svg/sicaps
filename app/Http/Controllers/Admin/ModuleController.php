<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Module;
use App\Models\ModuleLogbook;
use App\Models\SubClo;
use App\Models\User;
use App\Services\HtmlSanitizer;
use App\Services\SimilarityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ModuleController extends Controller
{
    public function __construct(private HtmlSanitizer $sanitizer)
    {
    }

    public function index()
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Aktifkan tahun ajaran terlebih dahulu.');

        $modules = $ay->modules()->get();

        return view('admin.modules.index', compact('modules', 'ay'));
    }

    public function create()
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Aktifkan tahun ajaran terlebih dahulu.');
        $module = null;

        return view('admin.modules.form', $this->formData($ay, null));
    }

    public function edit(Module $module)
    {
        $ay = AcademicYear::active();
        $module->load('subClos');

        return view('admin.modules.form', $this->formData($ay, $module));
    }

    public function store(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);

        $data = $this->validated($request);
        $data['academic_year_id'] = $ay->id;
        $data['fields_json'] = $this->parseFields($request);

        $module = Module::create($data);
        $module->subClos()->sync($this->validSubCloIds($request, $ay));

        return redirect()->route('admin.modules.index')->with('success', 'Modul ditambahkan.');
    }

    public function update(Request $request, Module $module)
    {
        $ay = AcademicYear::active();
        $data = $this->validated($request);
        $data['fields_json'] = $this->parseFields($request);

        $module->update($data);
        $module->subClos()->sync($this->validSubCloIds($request, $ay));

        return redirect()->route('admin.modules.index')->with('success', 'Modul diperbarui.');
    }

    public function destroy(Module $module)
    {
        // Cegah hilangnya pekerjaan mahasiswa: modul yang sudah punya isian tidak boleh dihapus.
        $submitted = $module->logbooks()->whereNotNull('payload_json')->count();
        if ($submitted > 0) {
            return back()->with('error', "Modul \"{$module->title}\" tidak dapat dihapus karena sudah memiliki {$submitted} isian mahasiswa. Tutup modul (Buka/Tutup) bila tidak ingin dipakai lagi.");
        }

        $module->delete();

        return back()->with('success', 'Modul dihapus.');
    }

    /** Buka/tutup modul-tugas untuk dikerjakan mahasiswa. */
    public function toggleOpen(Module $module)
    {
        $module->update(['is_open' => ! $module->is_open]);

        return back()->with('success', $module->is_open
            ? "\"{$module->title}\" dibuka — mahasiswa dapat mengerjakan."
            : "\"{$module->title}\" ditutup.");
    }

    /** Cek kemiripan jawaban antar mahasiswa pada tugas individu. */
    public function checkSimilarity(Module $module, SimilarityService $svc)
    {
        abort_unless($module->isIndividual(), 422, 'Pengecekan kemiripan hanya untuk tugas individu.');
        $r = $svc->check($module);

        return back()->with('success', "Pengecekan kemiripan selesai: {$r['submissions']} jawaban diperiksa, {$r['flagged']} terindikasi mirip (≥{$r['threshold']}%). Detail muncul di Review Logbook.");
    }

    /** Preview dokumen modul (materi + field) siap disimpan sebagai PDF. */
    public function preview(Module $module)
    {
        $module->load('subClos.clo.plo');
        $ay = $module->academicYear;

        return view('admin.modules.preview', compact('module', 'ay'));
    }

    /**
     * Proses presensi tugas individu (dihitung sebagai presensi kelas):
     * yang PASS → HADIR, sisanya (belum kumpul / lewat deadline / ditolak) → ALPA.
     */
    public function processAttendance(Module $module, \App\Services\AssignmentAttendanceService $svc)
    {
        abort_unless(
            $module->isIndividual() && $module->counts_as_attendance && $module->attendance_week && $module->attendance_session,
            422, 'Modul ini bukan tugas individu yang dihitung sebagai presensi.'
        );

        $r = $svc->finalize($module);

        $msg = "Presensi tugas diproses: {$r['present']} HADIR, {$r['absent']} ALPA (tidak mengumpulkan / ditolak).";
        if ($r['waiting']) {
            $msg .= " {$r['waiting']} menunggu review (presensi ditetapkan saat direview).";
        }
        if ($r['kept']) {
            $msg .= " {$r['kept']} izin/sakit dipertahankan.";
        }

        return back()->with('success', $msg);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'order_index' => ['required', 'integer', 'min:0'],
            'week_label' => ['required', 'string', 'max:20'],
            'code' => ['nullable', 'string', 'max:20'],
            'type' => ['required', Rule::in(['module', 'assessment', 'other'])],
            'assessment_stage' => ['nullable', Rule::in(['A1', 'A2', 'A3'])],
            'ai_policy_level' => ['required', 'integer', 'between:1,5'],
            'title' => ['required', 'string', 'max:255'],
            'attendance_week' => ['nullable', 'integer', 'between:1,16'],
            'attendance_session' => ['nullable', 'integer', 'between:1,2'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            // Materi modul (rich HTML) mengikuti template dokumen.
            'objectives' => ['nullable', 'string'],
            'tools_materials' => ['nullable', 'string'],
            'references' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'tasks' => ['nullable', 'string'],
        ]);

        // Checkbox (hanya terkirim bila dicentang).
        $data['is_individual'] = $request->boolean('is_individual');
        $data['is_open'] = $request->boolean('is_open');
        $data['requires_submission'] = $request->boolean('requires_submission');
        $data['counts_as_attendance'] = $data['is_individual'] && $request->boolean('counts_as_attendance');

        // Slot presensi hanya relevan untuk tugas individu.
        if (! $data['is_individual']) {
            $data['attendance_week'] = null;
            $data['attendance_session'] = null;
        }

        // Sanitasi materi rich-text (anti XSS) sebelum disimpan.
        foreach (array_keys(Module::MATERIAL_FIELDS) as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->sanitizer->clean($data[$key]);
            }
        }

        return $data;
    }

    /** Data untuk view form (opsi Sub-CLO + terpilih). */
    private function formData(?AcademicYear $ay, ?Module $module): array
    {
        $subClos = $ay
            ? SubClo::with('clo')->where('academic_year_id', $ay->id)->orderBy('order_index')->orderBy('id')->get()->groupBy('clo_id')
            : collect();
        $selectedSubClos = $module ? $module->subClos->pluck('id')->all() : [];

        return compact('module', 'ay', 'subClos', 'selectedSubClos');
    }

    /** Sub-CLO valid (milik tahun ajaran aktif). */
    private function validSubCloIds(Request $request, ?AcademicYear $ay): array
    {
        if (! $ay) {
            return [];
        }
        $ids = (array) $request->input('sub_clos', []);

        return SubClo::where('academic_year_id', $ay->id)->whereIn('id', $ids)->pluck('id')->all();
    }

    /**
     * Field logbook dikirim sebagai array paralel: field_key[], field_label[], field_type[], field_required[].
     * Kunci field lama dipertahankan (label boleh diganti tanpa memutus jawaban yang sudah masuk);
     * field baru mendapat kunci dari label, dan kunci ganda diberi akhiran agar tidak bertabrakan.
     */
    private function parseFields(Request $request): array
    {
        $keys = $request->input('field_key', []);
        $labels = $request->input('field_label', []);
        $types = $request->input('field_type', []);
        $required = $request->input('field_required', []);

        $fields = [];
        $used = [];
        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $key = (string) ($keys[$i] ?? '');
            if (! preg_match('/^[a-z0-9_]+$/', $key)) {
                $key = \Illuminate\Support\Str::slug($label, '_') ?: 'field_' . $i;
            }
            $base = $key;
            for ($n = 2; in_array($key, $used, true) || str_ends_with($key, '__name'); $n++) {
                $key = $base . '_' . $n;
            }
            $used[] = $key;

            $fields[] = [
                'key' => $key,
                'label' => $label,
                'type' => in_array($types[$i] ?? 'richtext', ['richtext', 'link', 'file'], true) ? $types[$i] : 'richtext',
                'required' => isset($required[$i]) && $required[$i],
            ];
        }

        return $fields;
    }
}
