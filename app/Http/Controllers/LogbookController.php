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
        if (! $team) {
            return $this->redirectNoTeam();
        }

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
        if (! $team) {
            return $this->redirectNoTeam();
        }
        abort_unless($module->academic_year_id === $ay->id, 404);
        // Modul (logbook/tugas) & assessment sama-sama dapat dibuka untuk melihat materi.
        abort_unless($module->isLogbook() || $module->type === 'assessment', 404, 'Modul tidak ditemukan.');
        $module->load('subClos.clo');

        $logbook = $this->resolveLogbook($module, $team, Auth::user(), false);
        if ($logbook->exists) {
            $logbook->load('versions.author');
        }

        $isLeader = Auth::id() === $team->leader_id;
        $isIndividual = $module->isIndividual();
        $requiresSubmission = $module->requiresSubmission();
        $scheduleState = $module->scheduleState();
        // Boleh mengerjakan: modul dibuka & dalam jendela waktu + belum final + berhak.
        $finalStatuses = ['Approved', 'Rejected'];
        $mayWork = $module->acceptsWorkFor($logbook->status_approval)
            && ! in_array($logbook->status_approval, $finalStatuses, true)
            && ($isIndividual ? true : $isLeader);
        $locked = in_array($logbook->status_approval, $finalStatuses, true);

        return view('logbook.show', compact('team', 'module', 'logbook', 'isLeader', 'isIndividual', 'requiresSubmission', 'mayWork', 'locked', 'scheduleState'));
    }

    public function print(Module $module)
    {
        $ay = AcademicYear::active();
        $team = Auth::user()->activeTeam($ay?->id);
        abort_unless($team, 403);
        abort_unless($module->academic_year_id === $ay->id, 404);
        abort_unless($module->isLogbook() || $module->type === 'assessment', 404);

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
        abort_unless($module->academic_year_id === $ay->id, 404);
        abort_unless($module->requiresSubmission(), 404, 'Modul ini tidak memiliki pengerjaan.');

        // Izin pengerjaan: tugas individu = tiap anggota (isi miliknya); logbook tim = ketua saja.
        if (! $module->isIndividual()) {
            abort_unless(Auth::id() === $team->leader_id, 403, 'Hanya ketua tim yang dapat submit logbook tim.');
        }

        // Gate #4: hanya bisa dikerjakan bila modul/tugas dibuka koordinator & dalam jendela waktu.
        // Pengecualian: revisi ("Perlu Revisi") tetap boleh dikirim walau deadline lewat.
        $current = $this->resolveLogbook($module, $team, Auth::user(), false);
        abort_unless($module->acceptsWorkFor($current->status_approval), 403, 'Modul/tugas ini belum dibuka atau sudah melewati batas waktu (deadline).');

        $logbook = $this->resolveLogbook($module, $team, Auth::user(), true);

        // Lock #2: yang sudah disetujui (Approved) tidak dapat diedit lagi.
        abort_if(in_array($logbook->status_approval, ['Approved', 'Rejected'], true), 403, 'Status sudah final (disetujui/ditolak) — tidak dapat diubah lagi.');

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
                    // Berkas lama TIDAK dihapus: isi sebelumnya disimpan sebagai riwayat versi saat submit ulang.
                    $file = $request->file("files.$key");
                    $name = \App\Support\UploadName::make($file, $mimes);
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

    /** Logbook & tugas individu disimpan per tim, jadi mahasiswa wajib bertim dulu. */
    private function redirectNoTeam()
    {
        return redirect()->route('team.index')->with('error',
            'Anda belum tergabung dalam tim. Logbook tim maupun tugas individu baru dapat dikerjakan setelah Anda bergabung ke tim — buat tim (bila ketua) atau minta ketua menambahkan Anda.');
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
