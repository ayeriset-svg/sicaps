<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Template import (CSV) untuk tiap fitur import. Berkas dapat dibuka & diedit
 * di Microsoft Excel / Google Sheets, lalu disimpan sebagai .csv dan diunggah.
 */
class TemplateController extends Controller
{
    /** Template import Master Mahasiswa (+ nilai historis opsional). */
    public function students(): StreamedResponse
    {
        return $this->stream('template-import-mahasiswa.csv',
            ['identity_number', 'name', 'angkatan', 'class_name', 'password', 'year', 'semester', 'final_score', 'grade_letter'],
            [
                ['2101001', 'Contoh Mahasiswa', '2021', 'SIA-3A', '', '', '', '', ''],
                ['2001099', 'Alumni Contoh', '2020', 'SIA-3B', '', '2023/2024', 'ganjil', '82', 'AB'],
            ]
        );
    }

    /** Template import Master User. */
    public function users(): StreamedResponse
    {
        return $this->stream('template-import-user.csv',
            ['identity_number', 'name', 'email', 'role', 'angkatan', 'class_name', 'password'],
            [
                ['2101001', 'Contoh Mahasiswa', 'contoh@student.kampus.ac.id', 'mahasiswa', '2021', 'SIA-3A', ''],
                ['19850101', 'Contoh Koordinator', 'koordinator@kampus.ac.id', 'superadmin', '', '', 'SandiAwal123'],
            ]
        );
    }

    /**
     * Kirim file CSV (dengan BOM UTF-8 agar Excel membaca karakter non-ASCII dengan benar).
     */
    private function stream(string $filename, array $header, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
