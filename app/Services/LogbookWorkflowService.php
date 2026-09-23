<?php

namespace App\Services;

use App\Models\ModuleLogbook;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Mengelola siklus status logbook (Pending, Revision Needed, Approved) dan
 * penanganan versioning payload dinamis per field.
 */
class LogbookWorkflowService
{
    /**
     * Mahasiswa submit isi logbook (payload keyed by field key).
     */
    public function submit(ModuleLogbook $logbook, array $payload, User $actor): ModuleLogbook
    {
        // Simpan snapshot versi tiap kali submit ulang (riwayat revisi tetap ada).
        $isResubmit = $logbook->exists && $logbook->payload_json !== null;
        if ($isResubmit) {
            $this->snapshot($logbook, $actor);
        }

        $logbook->fill([
            'payload_json' => $payload,
            'status_approval' => 'Pending',
            'updated_by' => $actor->id,
            'submitted_at' => now(),
        ]);
        if ($isResubmit) {
            $logbook->revision_count = ($logbook->revision_count ?? 0) + 1;
        }
        $logbook->save();

        return $logbook;
    }

    /**
     * Superadmin memberi keputusan review + feedback. Menyimpan snapshot agar
     * riwayat keputusan review (termasuk revisi) tetap tersimpan.
     */
    public function review(ModuleLogbook $logbook, string $status, ?string $feedback, User $reviewer): ModuleLogbook
    {
        $allowed = ['Approved', 'Revision Needed', 'Rejected', 'Pending', 'Not Started'];
        abort_unless(in_array($status, $allowed, true), 422, 'Status tidak valid.');

        $logbook->update([
            'status_approval' => $status,
            'feedback' => $feedback,
            'reviewed_at' => now(),
        ]);

        // Snapshot SETELAH keputusan → riwayat mencatat status final (mis. Approved/Done)
        // beserta komentar review & waktu persetujuan, bukan lagi 'Pending'.
        if ($logbook->payload_json !== null) {
            $this->snapshot($logbook, $reviewer);
        }

        return $logbook;
    }

    /**
     * Hapus berkas lama hanya bila tidak lagi dirujuk riwayat versi
     * (agar tautan berkas pada riwayat revisi tetap berfungsi).
     */
    public function deleteFileIfUnreferenced(ModuleLogbook $logbook, ?string $path): void
    {
        if (! $path) {
            return;
        }

        $referenced = $logbook->versions()->get(['payload_json'])
            ->contains(fn ($v) => in_array($path, (array) $v->payload_json, true));

        if (! $referenced) {
            Storage::disk('local')->delete($path);
        }
    }

    public function snapshot(ModuleLogbook $logbook, User $actor): void
    {
        $next = ($logbook->versions()->max('version_number') ?? 0) + 1;

        $logbook->versions()->create([
            'version_number' => $next,
            'payload_json' => $logbook->payload_json,
            'status_snapshot' => $logbook->status_approval,
            'feedback_snapshot' => $logbook->feedback,
            'submitted_by' => $actor->id,
        ]);
    }
}
