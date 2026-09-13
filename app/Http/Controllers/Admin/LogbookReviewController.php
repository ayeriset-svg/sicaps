<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ModuleLogbook;
use App\Services\AiDetectionService;
use App\Services\HtmlSanitizer;
use App\Services\LogbookWorkflowService;
use App\Services\ProofreaderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LogbookReviewController extends Controller
{
    public function index(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);

        $teamIds = $ay->teams()->pluck('id');

        $logbooks = ModuleLogbook::with('team.leader', 'module', 'user')
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
        $logbook->load('team.members.student', 'module', 'user', 'versions.author');

        return view('admin.logbook-review.show', compact('logbook'));
    }

    /** Form edit isi logbook oleh koordinator (mis. koreksi data). */
    public function edit(ModuleLogbook $logbook)
    {
        $logbook->load('team', 'module', 'user');

        return view('admin.logbook-review.edit', compact('logbook'));
    }

    /** Simpan hasil edit isi logbook oleh koordinator (payload disanitasi; file ditangani). */
    public function updateContent(Request $request, ModuleLogbook $logbook, HtmlSanitizer $sanitizer)
    {
        $module = $logbook->module;
        $existing = $logbook->payload_json ?? [];
        $mimes = config('capstone.file_field.mimes', ['pdf', 'doc', 'docx']);
        $maxKb = (int) config('capstone.file_field.max_kb', 10240);

        $rules = [];
        foreach ($module->fields() as $field) {
            $key = $field['key'];
            $type = $field['type'] ?? 'richtext';
            if ($type === 'link') {
                $rules["fields.$key"] = ['nullable', 'url', 'max:2048'];
            } elseif ($type === 'file') {
                $rules["files.$key"] = ['nullable', 'file', 'mimes:' . implode(',', $mimes), "max:{$maxKb}"];
            } else {
                $rules["fields.$key"] = ['nullable', 'string'];
            }
        }
        $request->validate($rules);

        $payload = [];
        foreach ($module->fields() as $field) {
            $key = $field['key'];
            $type = $field['type'] ?? 'richtext';
            if ($type === 'link') {
                $payload[$key] = $request->input("fields.$key");
            } elseif ($type === 'file') {
                if ($request->hasFile("files.$key")) {
                    if (! empty($existing[$key])) {
                        Storage::disk('local')->delete($existing[$key]);
                    }
                    $file = $request->file("files.$key");
                    $safe = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                    $name = $safe . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
                    $payload[$key] = $file->storeAs("logbooks/{$logbook->team_id}", $name, 'local');
                    $payload[$key . '__name'] = $file->getClientOriginalName();
                } else {
                    $payload[$key] = $existing[$key] ?? null;
                    $payload[$key . '__name'] = $existing[$key . '__name'] ?? null;
                }
            } else {
                $payload[$key] = $sanitizer->clean($request->input("fields.$key"));
            }
        }

        $logbook->update(['payload_json' => $payload, 'updated_by' => Auth::id()]);

        return redirect()->route('admin.logbook-review.show', $logbook)->with('success', 'Isi logbook diperbarui oleh koordinator.');
    }

    public function review(Request $request, ModuleLogbook $logbook, LogbookWorkflowService $workflow)
    {
        $data = $request->validate([
            'status_approval' => ['required', Rule::in(['Approved', 'Revision Needed'])],
            'feedback' => ['nullable', 'string'],
        ]);

        $workflow->review($logbook, $data['status_approval'], $data['feedback'] ?? null, Auth::user());

        $msg = 'Review logbook disimpan.';
        if ($this->syncAttendance($logbook->fresh('module'))) {
            $msg = $data['status_approval'] === 'Approved'
                ? 'Review disimpan. Tugas individu PASS → mahasiswa otomatis ditandai HADIR pada presensi.'
                : 'Review disimpan. Status bukan PASS → penanda hadir otomatis (jika ada) dibatalkan.';
        }

        return back()->with('success', $msg);
    }

    /**
     * Sinkronkan presensi untuk TUGAS INDIVIDU: PASS → hadir pada slot presensi
     * yang ditentukan modul; selain PASS → penanda hadir otomatis dibatalkan.
     * Return true bila modul ini memang memicu presensi otomatis.
     */
    private function syncAttendance(ModuleLogbook $logbook): bool
    {
        $module = $logbook->module;
        if (! $module || ! $module->isIndividual() || ! $logbook->user_id
            || ! $module->attendance_week || ! $module->attendance_session) {
            return false;
        }

        $slot = [
            'student_id' => $logbook->user_id,
            'academic_year_id' => $module->academic_year_id,
            'week_number' => $module->attendance_week,
            'session_number' => $module->attendance_session,
        ];

        if ($logbook->status_approval === 'Approved') {
            Attendance::updateOrCreate($slot, ['status' => 'present', 'recorded_by' => Auth::id()]);
        } else {
            // Batalkan hanya penanda "present" pada slot khusus tugas ini.
            Attendance::where($slot)->where('status', 'present')->delete();
        }

        return true;
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
        $logbook->load('team.members.student', 'team.leader', 'team.topic.partner', 'module', 'user', 'team.academicYear', 'versions.author');

        return view('logbook.print', [
            'team' => $logbook->team,
            'module' => $logbook->module,
            'logbook' => $logbook,
            'ay' => $logbook->team->academicYear,
        ]);
    }
}
