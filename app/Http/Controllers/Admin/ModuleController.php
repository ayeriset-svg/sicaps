<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Module;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;
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

        return view('admin.modules.form', compact('module', 'ay'));
    }

    public function edit(Module $module)
    {
        $ay = AcademicYear::active();

        return view('admin.modules.form', compact('module', 'ay'));
    }

    public function store(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);

        $data = $this->validated($request);
        $data['academic_year_id'] = $ay->id;
        $data['fields_json'] = $this->parseFields($request);

        Module::create($data);

        return redirect()->route('admin.modules.index')->with('success', 'Modul ditambahkan.');
    }

    public function update(Request $request, Module $module)
    {
        $data = $this->validated($request);
        $data['fields_json'] = $this->parseFields($request);

        $module->update($data);

        return redirect()->route('admin.modules.index')->with('success', 'Modul diperbarui.');
    }

    public function destroy(Module $module)
    {
        $module->delete();

        return back()->with('success', 'Modul dihapus.');
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
            // Materi modul (rich HTML) mengikuti template dokumen.
            'objectives' => ['nullable', 'string'],
            'tools_materials' => ['nullable', 'string'],
            'ai_rules' => ['nullable', 'string'],
            'references' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'tasks' => ['nullable', 'string'],
        ]);

        // Sanitasi materi rich-text (anti XSS) sebelum disimpan.
        foreach (array_keys(Module::MATERIAL_FIELDS) as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->sanitizer->clean($data[$key]);
            }
        }

        return $data;
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
