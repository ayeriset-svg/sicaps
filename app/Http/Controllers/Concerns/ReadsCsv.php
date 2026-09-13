<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Xlsx;
use Illuminate\Http\UploadedFile;

trait ReadsCsv
{
    /**
     * Baca berkas import (Excel .xlsx ATAU CSV) menjadi baris asosiatif.
     *
     * @return array<int, array<string, string|null>>
     */
    protected function readImportRows(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: (string) $file->guessExtension());
        if ($ext === 'xlsx') {
            return Xlsx::readRows($file->getRealPath());
        }

        return $this->readCsvRows($file->getRealPath());
    }

    /**
     * Baca CSV menjadi array baris asosiatif (keyed by header).
     * Tahan terhadap: BOM UTF-8, pemisah koma/titik-koma/tab (Excel ID sering ";"),
     * spasi/kutip pada header, dan baris dengan jumlah kolom tak sama.
     *
     * @return array<int, array<string, string|null>>
     */
    protected function readCsvRows(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Buang BOM UTF-8 di awal berkas.
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Deteksi pemisah dari baris pertama (koma / titik-koma / tab).
        $firstLine = strtok($content, "\r\n") ?: '';
        $counts = [
            ',' => substr_count($firstLine, ','),
            ';' => substr_count($firstLine, ';'),
            "\t" => substr_count($firstLine, "\t"),
        ];
        arsort($counts);
        $delimiter = array_key_first($counts);
        if ($counts[$delimiter] === 0) {
            $delimiter = ',';
        }

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $content);
        rewind($fh);

        $header = null;
        $rows = [];
        while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
            if ($header === null) {
                $header = array_map(
                    fn ($h) => strtolower(trim((string) $h, " \t\"'\xEF\xBB\xBF")),
                    $row
                );
                continue;
            }
            // Lewati baris kosong.
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $row = array_slice(array_pad($row, count($header), null), 0, count($header));
            $combined = @array_combine($header, $row);
            if ($combined !== false) {
                $rows[] = $combined;
            }
        }
        fclose($fh);

        return $rows;
    }
}
