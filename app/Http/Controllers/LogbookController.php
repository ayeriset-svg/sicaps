<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Module;
use App\Models\ModuleLogbook;
use App\Models\Team;
use App\Models\User;
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
        $moduleIds = $modules->pluck('id');

        // Logbook tim (user_id NULL) & submission individu milik user ini.
        $teamLogbooks = $team->logbooks()->whereNull('user_id')->get()->keyBy('module_id');
        $myLogbooks = ModuleLogbook::where('user_id', Auth::id())
            ->whereIn('module_id', $moduleIds)->get()->keyBy('module_id');

        // Submission relevan per modul (individu = milik user; tim = milik tim).
        $subs = [];
        foreach ($modules as $mod) {
            $subs[$mod->id] = $mod->isIndividual()
                ? ($myLogbooks[$mod->id] ?? null)
                : ($teamLogbooks[$mod->id] ?? null);
        }

        $isLeader = Auth::id() === $team->leader_id;

        return view('logbook.index', compact('team', 'modules', 'subs', 'isLeader'));
    }

    public function show(Module $module)
    {
        $ay = AcademicYear::active();
        $team = Auth::user()->activeTeam($ay?->id);
        abort_unless($team, 403);
        abort_unless($module->academic_year_id === $ay->id, 404);
        abort_unless($module->isLogbook(), 404, 'Modul ini tidak memiliki logbook.');

        $logbook = $this->resolveLogbook($module, $team, Auth::user(), false);
        if ($logbook->exists) {
            $logbook->load('versions.author');
        }

        $isLeader = Auth::id() === $team->leader_id;
        $isIndividual = $module->isIndividual();
        // Boleh mengerjakan: modul dibuka + belum Approved + berhak (individu=semua anggota, tim=ketua).
        $mayWork = $module->is_open
            && $logbook->status_approval !== 'Approved'
            && ($isIndividual ? true : $isLeader);
        $locked = $logbook->status_approval === 'Approved';

        return view('logbook.show', compact('team', 'module', 'logbook', 'isLeader', 'isIndividual', 'mayWork', 'locked'));
    }

    public function print(Module $module)
    {
        $ay = AcademicYear::active();
        $team = Auth::user()->activeTeam($ay?->id);
        abort_unless($team, 403);
        abort_unless($module->academic_year_id === $ay->id, 404);
        abort_unless($module->isLogbook(), 404);

        $team->load('members.student', 'leader', 'topic.partner');
        $logbook = $this->resolveLogbook($module, $team, Auth::user(), false);
        if ($logbook->exists) {
            $logbook->load('versions.author');
        }

        return view('logbook.print', compact('team', 'module', 'logbook', 'ay'));
    }

    public function update(Request $request, Module $module)
    {
        $ay = AcademicYear::active();
        $team = Auth::user()->activeTeam($ay?->id);
        abort_unless($team, 403);
        abort_unless($module->isLogbook(), 404);

        // Gate #4: hanya bisa dikerjakan bila modul/tugas dibuka koordinator.
        abort_unless($module->is_open, 403, 'Modul/tugas ini belum dibuka koordinator.');

        // Izin pengerjaan: tugas individu = tiap anggota (isi miliknya); logbook tim = ketua saja.
        if (! $module->isIndividual()) {
            abort_unless(Auth::id() === $team->leader_id, 403, 'Hanya ketua tim yang dapat submit logbook tim.');
        }

        $logbook = $this->resolveLogbook($module, $team, Auth::user(), true);

        // Lock #2: yang sudah disetujui (Approved) tidak dapat diedit lagi.
        abort_if($logbook->status_approval === 'Approved', 403, 'Sudah disetujui — tidak dapat diubah lagi.');

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
                    if (! empty($existing[$key])) {
                        \Illuminate\Support\Facades\Storage::disk('local')->delete($existing[$key]);
                    }
                    $file = $request->file("files.$key");
                    $safe = \Illuminate\Support\Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                    $name = $safe . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
                    $payload[$key] = $file->storeAs("logbooks/{$team->id}", $name, 'local');
                    $payload[$key . '__name'] = $file->getClientOriginalName();
                } else {
                    $payload[$key] = $existing[$key] ?? null;
                    $payload[$key . '__name'] = $existing[$key . '__name'] ?? null;
                }
            } else {
                $payload[$key] = $this->sanitizer->clean($request->input("fields.$key"));
            }
        }

        $this->workflow->submit($logbook, $payload, Auth::user());

        $what = $module->isIndividual() ? 'Tugas' : 'Logbook';

        return redirect()->route('logbook.show', $module)->with('success', "{$what} disubmit & menunggu review.");
    }

    /**
     * Ambil (atau siapkan) submission yang tepat: tugas individu = milik user;
     * logbook tim = milik tim (user_id NULL). $persist=true untuk firstOrCreate.
     */
    private function resolveLogbook(Module $module, Team $team, User $user, bool $persist): ModuleLogbook
    {
        $keys = [
            'team_id' => $team->id,
            'module_id' => $module->id,
            'user_id' => $module->isIndividual() ? $user->id : null,
        ];

        if ($persist) {
            return ModuleLogbook::firstOrCreate($keys, ['status_approval' => 'Not Started']);
        }

        $logbook = ModuleLogbook::firstOrNew($keys);
        if (! $logbook->exists) {
            $logbook->status_approval = 'Not Started';
        }

        return $logbook;
    }
}
