<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Nama simpan berkas unggahan: slug nama asli + timestamp + ekstensi AMAN.
 * Ekstensi dari nama file hanya dipakai bila termasuk daftar yang diizinkan;
 * selain itu memakai ekstensi hasil deteksi isi berkas (mencegah mis. .html/.php).
 */
class UploadName
{
    public static function make(UploadedFile $file, array $allowedExtensions): string
    {
        $allowed = array_map('strtolower', $allowedExtensions);
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, $allowed, true)) {
            $ext = $file->guessExtension() ?: 'bin';
        }

        $safe = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'berkas';

        return $safe . '-' . now()->format('YmdHis') . '.' . $ext;
    }
}
