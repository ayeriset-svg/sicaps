<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Template import (Excel .xlsx) untuk tiap fitur import. Berkas dapat diisi
 * langsung di Excel / Google Sheets lalu diunggah kembali.
 */
class TemplateController extends Controller
{
    /** Template import Master Mahasiswa (+ nilai historis opsional). */
    public function students(): BinaryFileResponse
    {
        return $this->xlsx('template-import-mahasiswa.xlsx',
            ['identity_number', 'name', 'angkatan', 'class_name', 'password', 'year', 'semester', 'final_score', 'grade_letter'],
            [
                ['2101001', 'Contoh Mahasiswa', '2021', 'SIA-3A', '', '', '', '', ''],
                ['2001099', 'Alumni Contoh', '2020', 'SIA-3B', '', '2023/2024', 'ganjil', '82', 'AB'],
            ]
        );
    }

    /** Template import Master User. */
    public function users(): BinaryFileResponse
    {
        return $this->xlsx('template-import-user.xlsx',
            ['identity_number', 'name', 'email', 'role', 'angkatan', 'class_name', 'password'],
            [
                ['2101001', 'Contoh Mahasiswa', 'contoh@student.kampus.ac.id', 'mahasiswa', '2021', 'SIA-3A', ''],
                ['19850101', 'Contoh Koordinator', 'koordinator@kampus.ac.id', 'superadmin', '', '', 'SandiAwal123'],
            ]
        );
    }

    private function xlsx(string $filename, array $header, array $rows): BinaryFileResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
        Xlsx::write($tmp, $header, $rows);

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
