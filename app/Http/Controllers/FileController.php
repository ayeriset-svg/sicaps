<?php

namespace App\Http\Controllers;

use App\Models\ManualBook;
use App\Models\ModuleLogbook;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Serve berkas dari private disk untuk user terautentikasi.
     * Gambar ditampilkan inline (untuk <img>), lainnya diunduh.
     */
    /** Prefix folder yang boleh diakses via route ini. */
    private const ALLOWED_PREFIXES = ['partners/', 'topics/', 'logbooks/', 'manual-books/'];

    public function show(string $path)
    {
        $path = urldecode($path);
        abort_unless(Auth::check(), 403);

        // Cegah path traversal & karakter berbahaya, serta paksa hanya folder tertentu.
        abort_if(str_contains($path, '..') || str_contains($path, "\0") || str_starts_with($path, '/'), 403);
        $allowed = false;
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $allowed = true;
                break;
            }
        }
        abort_unless($allowed, 403);
        if (str_starts_with($path, 'logbooks/')) {
            abort_unless($this->canAccessLogbookFile(Auth::user(), $path), 403);
        }
        // Lampiran Manual Book yang masih draf hanya untuk superadmin.
        if (str_starts_with($path, 'manual-books/') && ! Auth::user()->isSuperadmin()) {
            abort_unless(ManualBook::where('file_path', $path)->where('is_published', true)->exists(), 404);
        }
        abort_unless(Storage::disk('local')->exists($path), 404);

        $mime = Storage::disk('local')->mimeType($path);
        // Hanya gambar raster yang ditampilkan inline; lainnya (termasuk SVG/HTML) dipaksa unduh.
        $isImage = in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Content-Disposition' => ($isImage ? 'inline' : 'attachment') . '; filename="' . basename($path) . '"',
        ]);
    }

    /**
     * Berkas logbook (logbooks/{team_id}/...) hanya untuk superadmin & anggota tim tsb.
     * Berkas tugas individu hanya untuk pemiliknya (bukan anggota tim lain).
     */
    private function canAccessLogbookFile(User $user, string $path): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        $teamId = (int) (explode('/', $path)[1] ?? 0);
        $team = Team::find($teamId);
        if (! $team) {
            return false;
        }

        $isMember = (int) $team->leader_id === (int) $user->id
            || $team->members()->where('student_id', $user->id)->exists();
        if (! $isMember) {
            return false;
        }

        // Cari submission yang merujuk berkas ini (isi terkini atau riwayat versi).
        $owner = ModuleLogbook::with('versions')->where('team_id', $team->id)->get()
            ->first(fn ($lb) => in_array($path, (array) $lb->payload_json, true)
                || $lb->versions->contains(fn ($v) => in_array($path, (array) $v->payload_json, true)));

        // Logbook tim (user_id NULL) boleh dilihat semua anggota; tugas individu hanya pemiliknya.
        return ! $owner || $owner->user_id === null || (int) $owner->user_id === (int) $user->id;
    }
}
