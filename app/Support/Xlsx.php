<?php

namespace App\Support;

use ZipArchive;

/**
 * Pembaca & penulis XLSX minimalis TANPA dependency (pakai ZipArchive + SimpleXML).
 * Cukup untuk template import sederhana: satu sheet, baris header + data teks.
 */
class Xlsx
{
    /**
     * Tulis satu sheet ke berkas .xlsx. Semua sel ditulis sebagai teks (inline string)
     * agar NIM panjang tidak berubah jadi notasi ilmiah di Excel.
     *
     * @param  array<int,string>  $header
     * @param  array<int,array<int,string>>  $rows
     */
    public static function write(string $path, array $header, array $rows): void
    {
        $matrix = array_merge([$header], $rows);
        $sheet = self::sheetXml($matrix);

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Template" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
    }

    private static function sheetXml(array $matrix): string
    {
        $rowsXml = '';
        foreach ($matrix as $r => $cells) {
            $rowNum = $r + 1;
            $cellsXml = '';
            foreach (array_values($cells) as $c => $val) {
                $ref = self::colLetter($c) . $rowNum;
                $text = htmlspecialchars((string) $val, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $cellsXml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $text . '</t></is></c>';
            }
            $rowsXml .= '<row r="' . $rowNum . '">' . $cellsXml . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $rowsXml . '</sheetData></worksheet>';
    }

    /**
     * Baca .xlsx menjadi array baris asosiatif (keyed by header baris pertama,
     * di-lowercase & di-trim). Mendukung shared string, inline string, & sel numerik.
     *
     * @return array<int,array<string,string|null>>
     */
    public static function readRows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        // Shared strings (dipakai Excel secara default).
        $shared = [];
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if ($ss !== false) {
            $x = @simplexml_load_string($ss);
            if ($x !== false) {
                foreach ($x->si as $si) {
                    $shared[] = self::nodeText($si);
                }
            }
        }

        // Cari worksheet pertama.
        $sheetXml = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('~^xl/worksheets/sheet\d+\.xml$~', $name)) {
                $sheetXml = $zip->getFromName($name);
                break;
            }
        }
        $zip->close();
        if ($sheetXml === false) {
            return [];
        }

        $x = @simplexml_load_string($sheetXml);
        if ($x === false) {
            return [];
        }

        $grid = [];
        foreach ($x->sheetData->row as $row) {
            $cells = [];
            $maxIdx = -1;
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = preg_replace('/\d+/', '', $ref);
                $idx = self::colIndex($col ?: 'A');
                $t = (string) $c['t'];
                if ($t === 's') {
                    $val = $shared[(int) $c->v] ?? '';
                } elseif ($t === 'inlineStr') {
                    $val = self::nodeText($c->is);
                } elseif ($t === 'str') {
                    $val = (string) $c->v;
                } else {
                    $val = isset($c->v) ? (string) $c->v : '';
                }
                $cells[$idx] = $val;
                $maxIdx = max($maxIdx, $idx);
            }
            // Ratakan gap kolom.
            $line = [];
            for ($i = 0; $i <= $maxIdx; $i++) {
                $line[$i] = $cells[$i] ?? '';
            }
            $grid[] = $line;
        }

        // Buang baris kosong di awal, ambil header.
        $grid = array_values(array_filter($grid, fn ($l) => count(array_filter($l, fn ($v) => trim((string) $v) !== '')) > 0));
        if (empty($grid)) {
            return [];
        }
        $header = array_map(fn ($h) => strtolower(trim((string) $h, " \t\"'")), $grid[0]);

        $rows = [];
        for ($i = 1; $i < count($grid); $i++) {
            $line = array_pad($grid[$i], count($header), '');
            $line = array_slice($line, 0, count($header));
            $rows[] = @array_combine($header, $line) ?: [];
        }

        return $rows;
    }

    private static function nodeText($node): string
    {
        if ($node === null) {
            return '';
        }
        if (isset($node->t)) {
            return (string) $node->t;
        }
        $out = '';
        foreach ($node->r as $run) {
            $out .= (string) $run->t;
        }

        return $out;
    }

    private static function colLetter(int $index): string
    {
        $s = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $s = chr(65 + $mod) . $s;
            $index = intdiv($index - 1, 26);
        }

        return $s;
    }

    private static function colIndex(string $letters): int
    {
        $n = 0;
        foreach (str_split(strtoupper($letters)) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n - 1;
    }
}
