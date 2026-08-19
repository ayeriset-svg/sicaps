<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Serve berkas dari private disk untuk user terautentikasi.
     * Gambar ditampilkan inline (untuk <img>), lainnya diunduh.
     */
    /** Prefix folder yang boleh diakses via route ini. */
    private const ALLOWED_PREFIXES = ['partners/', 'topics/', 'logbooks/'];

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
        abort_unless(Storage::disk('local')->exists($path), 404);

        $mime = Storage::disk('local')->mimeType($path);
        $isImage = str_starts_with((string) $mime, 'image/');

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Content-Disposition' => ($isImage ? 'inline' : 'attachment') . '; filename="' . basename($path) . '"',
        ]);
    }
}
