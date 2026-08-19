<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Module;
use App\Models\ModuleLogbook;
use App\Services\HtmlSanitizer;
use App\Services\LogbookWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogbookController extends Controller
{
    public function __construct(
        private LogbookWorkflowService $workflow,
        private HtmlSanitizer $sanitizer,
    ) {
    }

    public function index()
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);
        $team = Auth::user()->activeTeam($ay->id);
        abort_unless($team, 403, 'Anda harus tergabung dalam tim.');

        $modules = $ay->modules()->get();
        $logbooks = $team->logbooks()->get()->keyBy('module_id');

        return view('logbook.index', compact('team', 'modules', 'logbooks'));
    }

    public function show(Module $module)
    {
        $ay = AcademicYear::active();
        $team = Auth::user()->activeTeam($ay?->id);
        abort_unless($team, 403);
        abort_unless($module->academic_year_id === $ay->id, 404);
        abort_unless($module->isLogbook(), 404, 'Modul ini tidak memiliki logbook.');

        $logbook = ModuleLogbook::firstOrCreate(
            ['team_id' => $team->id, 'module_id' => $module->id],
            ['status_approval' => 'Not Started']
        );
        $logbook->load('versions.author');

        $isLeader = Auth::id() === $team->leader_id;

        return view('logbook.show', compact('team', 'module', 'logbook', 'isLeader'));
    }

    /**
     * Tampilan cetak / simpan-PDF logbook yang sudah diisi (interim; template final menyusul).
     */
    public function print(Module $module)
    {
        $ay = AcademicYear::active();
        $team = Auth::user()->activeTeam($ay?->id);
        abort_unless($team, 403);
        abort_unless($module->academic_year_id === $ay->id, 404);
        abort_unless($module->isLogbook(), 404);

        $team->load('members.student', 'leader', 'topic.partner');
        $logbook = ModuleLogbook::where('team_id', $team->id)->where('module_id', $module->id)->first();

        return view('logbook.print', compact('team', 'module', 'logbook', 'ay'));
    }

    public function update(Request $request, Module $module)
    {
        $ay = AcademicYear::active();
        $team = Auth::user()->activeTeam($ay?->id);
        abort_unless($team, 403);
        abort_unless(Auth::id() === $team->leader_id, 403, 'Hanya ketua tim yang dapat submit logbook.');
        abort_unless($module->isLogbook(), 404);

        $logbook = ModuleLogbook::firstOrCreate(
            ['team_id' => $team->id, 'module_id' => $module->id],
            ['status_approval' => 'Not Started']
        );

        $existing = $logbook->payload_json ?? [];

        // Bangun payload dinamis sesuai definisi field modul.
        $payload = [];
        $rules = [];
        $mimes = config('capstone.file_field.mimes', ['pdf', 'doc', 'docx']);
        $maxKb = (int) config('capstone.file_field.max_kb', 10240);
        foreach ($module->fields() as $field) {
            $key = $field['key'];
            $type = $field['type'] ?? 'richtext';
            $required = (bool) ($field['required'] ?? false);

            if ($type === 'link') {
                $rules["fields.$key"] = [$required ? 'required' : 'nullable', 'url', 'max:2048'];
            } elseif ($type === 'file') {
                // Wajib hanya bila belum ada berkas tersimpan sebelumnya.
                $hasExisting = ! empty($existing[$key]);
                $rules["files.$key"] = [$required && ! $hasExisting ? 'required' : 'nullable',
                    'file', 'mimes:' . implode(',', $mimes), "max:{$maxKb}"];
            } else {
                $rules["fields.$key"] = [$required ? 'required' : 'nullable', 'string'];
            }
        }
        $request->validate($rules);

        // Bangun payload: link apa adanya; richtext disanitasi (anti XSS); file disimpan & path disimpan.
        foreach ($module->fields() as $field) {
            $key = $field['key'];
            $type = $field['type'] ?? 'richtext';

            if ($type === 'link') {
                $payload[$key] = $request->input("fields.$key");
            } elseif ($type === 'file') {
                if ($request->hasFile("files.$key")) {
                    // Hapus berkas lama bila ada, lalu simpan yang baru.
                    if (! empty($existing[$key])) {
                        \Illuminate\Support\Facades\Storage::disk('local')->delete($existing[$key]);
                    }
                    $file = $request->file("files.$key");
                    $safe = \Illuminate\Support\Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                    $name = $safe . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
                    $payload[$key] = $file->storeAs("logbooks/{$team->id}", $name, 'local');
                    $payload[$key . '__name'] = $file->getClientOriginalName();
                } else {
                    // Pertahankan berkas lama.
                    $payload[$key] = $existing[$key] ?? null;
                    $payload[$key . '__name'] = $existing[$key . '__name'] ?? null;
                }
            } else {
                $payload[$key] = $this->sanitizer->clean($request->input("fields.$key"));
            }
        }

        $this->workflow->submit($logbook, $payload, Auth::user());

        return redirect()->route('logbook.show', $module)->with('success', 'Logbook disubmit & menunggu review.');
    }
}
