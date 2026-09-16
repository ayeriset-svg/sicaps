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
    public function processAttendance(Module $module)
    {
        abort_unless(
            $module->isIndividual() && $module->counts_as_attendance && $module->attendance_week && $module->attendance_session,
            422, 'Modul ini bukan tugas individu yang dihitung sebagai presensi.'
        );

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

        return back()->with('success', "Presensi tugas diproses: {$present} HADIR, {$absent} ALPA (belum mengumpulkan / lewat deadline / ditolak).");
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
     * Field logbook dikirim sebagai array paralel: field_label[], field_type[], field_required[].
     */
    private function parseFields(Request $request): array
    {
        $labels = $request->input('field_label', []);
        $types = $request->input('field_type', []);
        $required = $request->input('field_required', []);

        $fields = [];
        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $fields[] = [
                'key' => \Illuminate\Support\Str::slug($label, '_') ?: 'field_' . $i,
                'label' => $label,
                'type' => in_array($types[$i] ?? 'richtext', ['richtext', 'link', 'file'], true) ? $types[$i] : 'richtext',
                'required' => isset($required[$i]) && $required[$i],
            ];
        }

        return $fields;
    }
}
