<?php

namespace App\Services;

use App\Models\ModuleLogbook;

/**
 * Proofreader / Technical Editor tata tulis ilmiah (rule-based / deterministik).
 *
 * Checklist v2 (8 butir):
 *  1. STRUKTUR       — paragraf ideal >= 5 kalimat; tandai paragraf "sebatang kara" (< 3 kalimat).
 *  2. SITASI         — sitasi wajib gaya IEEE [1]/[1-3]; peringatkan gaya APA/Harvard (Nama, 2023).
 *  3. EJAAN (PUEBI)  — kata tidak baku + awalan "di-"/"ke-" (pisah utk tempat, sambung utk kerja pasif).
 *  4. ISTILAH_ASING  — istilah Inggris/teknis (termasuk akronim VR/XR/API) yang belum dicetak miring.
 *  5. CAPTION        — Gambar/Tabel wajib punya caption bernomor (relevansi caption perlu tinjauan manual).
 *  6. KATA_GANTI     — kata ganti orang pertama (saya/kami/penulis) → pasif ilmiah (impersonal).
 *  7. ALIGNMENT      — paragraf utama wajib rata kanan-kiri (justify, line-height 1.5); caption rata tengah.
 *  8. RUJUKAN_AMBIGU — "gambar di bawah" / "tabel di atas" → sebut nomor/label (mis. Gambar 3.1).
 *
 * Menghasilkan: score, summary, total_issues, issues[], corrected_text (auto-fix aman).
 */
class ProofreaderService
{
    private const PENALTY = [
        'Struktur_Paragraf' => 4,
        'Sitasi' => 5,
        'Ejaan' => 6,
        'Istilah_Asing' => 3,
        'Caption' => 4,
        'Kata_Ganti' => 4,
        'Alignment' => 2,
        'Rujukan_Ambigu' => 5,
    ];

    private const LABELS = [
        'Struktur_Paragraf' => 'struktur paragraf',
        'Sitasi' => 'format sitasi (IEEE)',
        'Ejaan' => 'ejaan/kata tidak baku',
        'Istilah_Asing' => 'istilah asing belum dimiringkan',
        'Caption' => 'caption gambar/tabel',
        'Kata_Ganti' => 'kata ganti orang pertama',
        'Alignment' => 'perataan/spasi',
        'Rujukan_Ambigu' => 'rujukan gambar/tabel ambigu',
    ];

    private const MAX_PER_CATEGORY = 8;

    public function check(ModuleLogbook $logbook): array
    {
        $fields = $this->extractRichHtml($logbook);
        if (empty($fields)) {
            return $this->empty('Tidak ada konten teks (rich text) untuk diperiksa.');
        }

        $combined = implode("\n", $fields);
        $plain = $this->plain($combined);
        $wc = count(preg_split('/\s+/', $plain, -1, PREG_SPLIT_NO_EMPTY));
        if ($wc < 15) {
            return $this->empty('Teks terlalu pendek untuk diperiksa (min. 15 kata).');
        }

        [$dom, $root] = $this->dom($combined);
        $outsideEm = $this->textOutsideEmphasis($dom, $root);

        $issues = array_merge(
            $this->checkStructure($root),                 // 1
            $this->checkCitation($plain),                 // 2
            $this->checkEjaan($plain),                    // 3
            $this->checkForeign($outsideEm ?: $plain),    // 4
            $this->checkCaption($root),                   // 5
            $this->checkFirstPerson($plain),              // 6
            $this->checkAlignment($root),                 // 7
            $this->checkAmbiguous($plain),                // 8
        );

        $issues = $this->capPerCategory($issues);

        return [
            'score' => $this->scoreFrom($issues),
            'summary' => $this->summarize($issues),
            'total_issues' => count($issues),
            'issues' => array_values($issues),
            'corrected_text' => $this->buildCorrected($fields),
            'checked_words' => $wc,
        ];
    }

    /* ==================== (1) STRUKTUR PARAGRAF ==================== */

    private function checkStructure(?\DOMElement $root): array
    {
        if (! $root) {
            return [];
        }
        $issues = [];
        $minSent = (int) config('capstone.proofreader.min_paragraph_sentences', 3);
        $ideal = (int) config('capstone.proofreader.ideal_paragraph_sentences', 5);
        $minWords = (int) config('capstone.proofreader.min_paragraph_words', 15);

        $xp = new \DOMXPath($root->ownerDocument);
        foreach ($xp->query('.//p', $root) as $block) {
            $t = trim(preg_replace('/\s+/', ' ', $block->textContent));
            if ($t === '' || $this->isCaption($t)) {
                continue;
            }
            $words = count(preg_split('/\s+/', $t, -1, PREG_SPLIT_NO_EMPTY));
            if ($words < $minWords) {
                continue; // lewati label/frasa pendek
            }
            $nSent = count(array_filter(
                preg_split('/[.!?]+/u', $t, -1, PREG_SPLIT_NO_EMPTY),
                fn ($s) => trim($s) !== ''
            ));
            if ($nSent < $minSent) {
                $issues[] = $this->issue(
                    'Struktur_Paragraf',
                    $this->limit($t),
                    "Kembangkan menjadi minimal {$ideal} kalimat (1 kalimat utama + kalimat penjelas).",
                    "Paragraf hanya berisi {$nSent} kalimat (\"sebatang kara\"). Paragraf ilmiah idealnya {$ideal} kalimat: gagasan pokok yang didukung beberapa kalimat penjelas.",
                );
            }
        }

        return $issues;
    }

    /* ==================== (2) SITASI IEEE ==================== */

    private function checkCitation(string $text): array
    {
        $issues = [];
        // Pola author-year khas APA/Harvard: butuh pemisah (koma/&/dan/dkk/et al) sebelum tahun.
        $pattern = '/\([A-Z][a-zA-Z]+[^)]*?(?:,|&|\bdan\b|\bdkk\.?|\bet al\.?)[^)]*?(?:19|20)\d{2}[a-z]?\)/u';
        if (preg_match_all($pattern, $text, $ms, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            $seen = [];
            foreach ($ms as $m) {
                $cite = trim($m[0][0]);
                if (isset($seen[$cite])) {
                    continue;
                }
                $seen[$cite] = true;
                $issues[] = $this->issue(
                    'Sitasi',
                    $this->sentenceAround($text, $m[0][1]),
                    "Ganti ke gaya IEEE dengan nomor dalam kurung siku, mis. [1] atau [1]–[3] (bukan \"{$cite}\").",
                    "Sitasi \"{$cite}\" memakai gaya APA/Harvard. Dokumen wajib memakai gaya IEEE: rujukan diberi nomor berurut dalam kurung siku ([1], [2], [1-3]).",
                );
            }
        }

        return $issues;
    }

    /* ==================== (3) EJAAN + awalan di-/ke- ==================== */

    private function checkEjaan(string $text): array
    {
        $issues = [];

        foreach (config('capstone.proofreader.nonbaku') as $wrong => $right) {
            if (preg_match('/\b' . preg_quote($wrong, '/') . '\b/iu', $text, $m, PREG_OFFSET_CAPTURE)) {
                $snippet = $this->sentenceAround($text, $m[0][1]);
                $issues[] = $this->issue('Ejaan', $snippet,
                    $this->replaceWord($snippet, $wrong, $right),
                    "Kata \"{$m[0][0]}\" tidak baku; gunakan \"{$right}\" (PUEBI/KBBI).");
            }
        }

        // di- kata depan (dipisah)
        foreach (config('capstone.proofreader.di_should_separate') as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '\b/iu', $text, $m, PREG_OFFSET_CAPTURE)) {
                $fixed = 'di ' . mb_substr($w, 2);
                $snippet = $this->sentenceAround($text, $m[0][1]);
                $issues[] = $this->issue('Ejaan', $snippet,
                    $this->replaceWord($snippet, $w, $fixed),
                    "\"{$m[0][0]}\" — awalan \"di\" sebagai kata depan (tempat/arah) harus dipisah: \"{$fixed}\".");
            }
        }

        // ke- kata depan (dipisah)
        foreach (config('capstone.proofreader.ke_should_separate') as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '\b/iu', $text, $m, PREG_OFFSET_CAPTURE)) {
                $fixed = 'ke ' . mb_substr($w, 2);
                $snippet = $this->sentenceAround($text, $m[0][1]);
                $issues[] = $this->issue('Ejaan', $snippet,
                    $this->replaceWord($snippet, $w, $fixed),
                    "\"{$m[0][0]}\" — awalan \"ke\" sebagai kata depan (tempat/arah) harus dipisah: \"{$fixed}\".");
            }
        }

        // di- kerja pasif (digabung)
        foreach (config('capstone.proofreader.di_verbs') as $v) {
            if (preg_match('/\bdi\s+' . preg_quote($v, '/') . '\b/iu', $text, $m, PREG_OFFSET_CAPTURE)) {
                $fixed = 'di' . $v;
                $snippet = $this->sentenceAround($text, $m[0][1]);
                $issues[] = $this->issue('Ejaan', $snippet,
                    preg_replace('/\bdi\s+' . preg_quote($v, '/') . '\b/iu', $fixed, $snippet),
                    "\"{$m[0][0]}\" — awalan \"di-\" pembentuk kata kerja pasif harus digabung: \"{$fixed}\".");
            }
        }

        return $issues;
    }

    /* ==================== (4) ISTILAH ASING ==================== */

    private function checkForeign(string $text): array
    {
        $issues = [];

        // (a) istilah/frasa (case-insensitive, frasa terpanjang diprioritaskan)
        $terms = config('capstone.proofreader.foreign_terms');
        usort($terms, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $seen = [];
        foreach ($terms as $term) {
            $key = mb_strtolower($term);
            if (isset($seen[$key])) {
                continue;
            }
            if (preg_match('/\b' . preg_quote($term, '/') . '\b/iu', $text, $m, PREG_OFFSET_CAPTURE)) {
                $seen[$key] = true;
                $issues[] = $this->issue('Istilah_Asing',
                    $this->sentenceAround($text, $m[0][1]),
                    "Cetak miring: \"{$m[0][0]}\" → italic (mis. <em>{$m[0][0]}</em>), atau beri padanan Indonesia.",
                    "Istilah asing \"{$m[0][0]}\" sebaiknya dicetak miring (italic) atau diberi padanan Indonesia.");
            }
        }

        // (b) akronim teknis (CASE-SENSITIVE huruf besar)
        foreach (config('capstone.proofreader.foreign_acronyms') as $ac) {
            if (isset($seen[mb_strtolower($ac)])) {
                continue;
            }
            if (preg_match('/\b' . preg_quote($ac, '/') . '\b/u', $text, $m, PREG_OFFSET_CAPTURE)) {
                $seen[mb_strtolower($ac)] = true;
                $issues[] = $this->issue('Istilah_Asing',
                    $this->sentenceAround($text, $m[0][1]),
                    "Cetak miring akronim asing \"{$ac}\" → <em>{$ac}</em> (kepanjangannya pun ditulis miring saat pertama muncul).",
                    "Akronim/istilah asing \"{$ac}\" sebaiknya dicetak miring (italic).");
            }
        }

        return $issues;
    }

    /* ==================== (5) CAPTION GAMBAR/TABEL ==================== */

    private function checkCaption(?\DOMElement $root): array
    {
        if (! $root) {
            return [];
        }
        $issues = [];
        $xp = new \DOMXPath($root->ownerDocument);

        $imgCount = $xp->query('.//img')->length;
        $tableCount = $xp->query('.//table')->length;

        // Kumpulkan label caption bernomor dari blok teks.
        $gambarCap = 0;
        $tabelCap = 0;
        foreach ($xp->query('.//p | .//figcaption | .//caption', $root) as $b) {
            $t = trim(preg_replace('/\s+/', ' ', $b->textContent));
            if (preg_match('/^gambar\s*[\d.]+/i', $t)) {
                $gambarCap++;
            } elseif (preg_match('/^tabel\s*[\d.]+/i', $t)) {
                $tabelCap++;
            }
        }

        if ($imgCount > 0 && $gambarCap < $imgCount) {
            $issues[] = $this->issue('Caption',
                "Ditemukan {$imgCount} gambar, tetapi hanya {$gambarCap} yang punya caption bernomor.",
                'Beri caption bernomor di bawah tiap gambar, mis. "Gambar 3.1 Arsitektur sistem", dan pastikan isinya sesuai konteks paragraf yang merujuknya.',
                'Setiap gambar wajib memiliki caption bernomor ("Gambar x.y ..."). Relevansi isi caption terhadap narasi perlu diperiksa manual.');
        }
        if ($tableCount > 0 && $tabelCap < $tableCount) {
            $issues[] = $this->issue('Caption',
                "Ditemukan {$tableCount} tabel, tetapi hanya {$tabelCap} yang punya caption bernomor.",
                'Beri caption bernomor di atas tiap tabel, mis. "Tabel 4.2 Hasil pengujian", sesuai konteks paragraf yang merujuknya.',
                'Setiap tabel wajib memiliki caption bernomor ("Tabel x.y ..."). Relevansi isi caption terhadap narasi perlu diperiksa manual.');
        }

        return $issues;
    }

    /* ==================== (6) KATA GANTI ORANG PERTAMA ==================== */

    private function checkFirstPerson(string $text): array
    {
        $issues = [];
        foreach (['saya', 'kami', 'kita', 'aku', 'penulis'] as $p) {
            if (preg_match('/\b' . $p . '\b/iu', $text, $m, PREG_OFFSET_CAPTURE)) {
                $issues[] = $this->issue('Kata_Ganti',
                    $this->sentenceAround($text, $m[0][1]),
                    'Ubah ke bentuk pasif ilmiah/impersonal (mis. "…dilakukan/dianalisis…" tanpa "' . $m[0][0] . '").',
                    "Hindari kata ganti orang pertama \"{$m[0][0]}\" pada tulisan ilmiah; gunakan kalimat pasif/impersonal.");
            }
        }

        return $issues;
    }

    /* ==================== (7) ALIGNMENT & SPASI ==================== */

    private function checkAlignment(?\DOMElement $root): array
    {
        if (! $root) {
            return [];
        }
        $issues = [];
        $mainWords = (int) config('capstone.proofreader.main_paragraph_words', 12);
        $xp = new \DOMXPath($root->ownerDocument);

        foreach ($xp->query('.//p', $root) as $p) {
            $t = trim(preg_replace('/\s+/', ' ', $p->textContent));
            if ($t === '') {
                continue;
            }
            $style = $p->getAttribute('style') . ' ' . $p->getAttribute('class');
            $isJustify = stripos($style, 'justify') !== false;
            $isCenter = stripos($style, 'center') !== false;

            if ($this->isCaption($t)) {
                if (! $isCenter) {
                    $issues[] = $this->issue('Alignment', $this->limit($t),
                        'Ratakan caption ke tengah (text-align: center / class "text-center").',
                        'Caption Gambar/Tabel wajib rata tengah (center).');
                }
                continue;
            }
            $words = count(preg_split('/\s+/', $t, -1, PREG_SPLIT_NO_EMPTY));
            if ($words >= $mainWords && ! $isJustify) {
                $issues[] = $this->issue('Alignment', $this->limit($t),
                    'Ratakan paragraf ke kanan-kiri (text-align: justify) dengan line-height 1.5.',
                    'Paragraf utama wajib rata kanan-kiri (justify) dan berjarak baris (line-height) 1,5.');
            }
        }

        return $issues;
    }

    /* ==================== (8) RUJUKAN AMBIGU ==================== */

    private function checkAmbiguous(string $text): array
    {
        $issues = [];
        $pattern = '/\b(gambar|tabel|grafik|diagram|bagan|persamaan)\s+(di\s*(atas|bawah)(\s+ini)?|berikut(\s+ini)?|tersebut)\b/iu';
        if (preg_match_all($pattern, $text, $ms, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            $seen = [];
            foreach ($ms as $m) {
                $phrase = preg_replace('/\s+/', ' ', mb_strtolower($m[0][0]));
                if (isset($seen[$phrase])) {
                    continue;
                }
                $seen[$phrase] = true;
                $obj = ucfirst(mb_strtolower($m[1][0]));
                $issues[] = $this->issue('Rujukan_Ambigu',
                    $this->sentenceAround($text, $m[0][1]),
                    "Sebut nomor/label spesifik, mis. \"{$obj} 3.1\", bukan \"{$m[0][0]}\".",
                    "Rujukan \"{$m[0][0]}\" ambigu. Sebutkan nomor {$obj} secara eksplisit (mis. {$obj} 3.1).");
            }
        }

        return $issues;
    }

    /* ==================== CORRECTED TEXT (auto-fix aman) ==================== */

    private function buildCorrected(array $fields): string
    {
        $multi = count($fields) > 1;
        $out = [];
        foreach ($fields as $label => $html) {
            $corrected = $this->correctHtml($html);
            $out[] = $multi ? '<h4>' . e($label) . '</h4>' . $corrected : $corrected;
        }

        return implode("\n", $out);
    }

    private function correctHtml(string $html): string
    {
        [$dom, $root] = $this->dom($html);
        if (! $root) {
            return $html;
        }
        $xp = new \DOMXPath($dom);

        // Perbaiki teks (ejaan/di-/ke- + miringkan istilah asing) pada text node.
        foreach (iterator_to_array($xp->query('.//text()', $root)) as $node) {
            $orig = $node->nodeValue;
            if (trim($orig) === '') {
                continue;
            }
            $inEm = $this->hasAncestor($node, ['em', 'i', 'code', 'a']);
            $fixedText = $this->applyTextFixes($orig);
            $fragment = $inEm ? e($fixedText) : $this->wrapForeign($fixedText);
            if ($fragment !== e($orig)) {
                $this->replaceNodeWithHtml($dom, $node, $fragment);
            }
        }

        // Perbaiki perataan: paragraf utama -> justify + line-height 1.5; caption -> center.
        foreach ($xp->query('.//p', $root) as $p) {
            $t = trim(preg_replace('/\s+/', ' ', $p->textContent));
            if ($t === '') {
                continue;
            }
            if ($this->isCaption($t)) {
                $this->setStyle($p, 'text-align', 'center');
            } elseif (count(preg_split('/\s+/', $t, -1, PREG_SPLIT_NO_EMPTY)) >= 12) {
                $this->setStyle($p, 'text-align', 'justify');
                $this->setStyle($p, 'line-height', '1.5');
            }
        }

        return $this->innerHtml($dom, $root);
    }

    private function applyTextFixes(string $text): string
    {
        foreach (config('capstone.proofreader.di_verbs') as $v) {
            $text = preg_replace_callback('/\bdi\s+(' . preg_quote($v, '/') . ')\b/iu', function ($m) {
                return (mb_substr($m[0], 0, 1) === 'D' ? 'Di' : 'di') . mb_strtolower($m[1]);
            }, $text);
        }
        foreach (config('capstone.proofreader.di_should_separate') as $w) {
            $text = $this->replaceWord($text, $w, 'di ' . mb_substr($w, 2));
        }
        foreach (config('capstone.proofreader.ke_should_separate') as $w) {
            $text = $this->replaceWord($text, $w, 'ke ' . mb_substr($w, 2));
        }
        foreach (config('capstone.proofreader.nonbaku') as $wrong => $right) {
            $text = $this->replaceWord($text, $wrong, $right);
        }

        return $text;
    }

    /** Bungkus istilah asing + akronim dengan <em> (satu lintasan). Input plain, output HTML. */
    private function wrapForeign(string $plain): string
    {
        $escaped = e($plain);

        $terms = config('capstone.proofreader.foreign_terms');
        usort($terms, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $alt = implode('|', array_map(fn ($t) => preg_quote($t, '/'), $terms));
        $escaped = preg_replace_callback('/\b(' . $alt . ')\b/iu', fn ($m) => '<em>' . $m[0] . '</em>', $escaped);

        $acr = array_map(fn ($t) => preg_quote($t, '/'), config('capstone.proofreader.foreign_acronyms'));
        if ($acr) {
            // Case-sensitive; hindari yang sudah terbungkus <em>.
            $escaped = preg_replace_callback('/\b(' . implode('|', $acr) . ')\b/u', function ($m) {
                return '<em>' . $m[0] . '</em>';
            }, $escaped);
        }

        return $escaped;
    }

    /* ==================== HELPER ==================== */

    private function isCaption(string $t): bool
    {
        return (bool) preg_match('/^(gambar|tabel|grafik|bagan)\s*[\d.]/i', trim($t));
    }

    private function setStyle(\DOMElement $el, string $prop, string $val): void
    {
        $style = trim($el->getAttribute('style'));
        if (stripos($style, $prop) !== false) {
            return; // sudah punya properti ini, jangan timpa
        }
        $style = $style === '' ? '' : rtrim($style, ';') . '; ';
        $el->setAttribute('style', $style . "{$prop}: {$val};");
    }

    private function extractRichHtml(ModuleLogbook $logbook): array
    {
        $module = $logbook->module;
        $payload = $logbook->payload_json ?? [];
        $out = [];
        foreach (($module?->fields() ?? []) as $field) {
            if (($field['type'] ?? 'richtext') === 'richtext') {
                $val = $payload[$field['key']] ?? '';
                if (is_string($val) && trim(strip_tags($val)) !== '') {
                    $out[$field['label'] ?? $field['key']] = $val;
                }
            }
        }

        return $out;
    }

    private function plain(string $html): string
    {
        return trim(preg_replace('/[ \t]+/', ' ',
            html_entity_decode(strip_tags(preg_replace('/<\/(p|div|li|h[1-6]|br)[^>]*>/i', "$0\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8')
        ));
    }

    /** @return array{0:\DOMDocument,1:?\DOMElement} */
    private function dom(string $html): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="pfroot">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);
        $root = $xp->query('//*[@id="pfroot"]')->item(0);

        return [$dom, $root instanceof \DOMElement ? $root : null];
    }

    private function textOutsideEmphasis(\DOMDocument $dom, ?\DOMElement $root): string
    {
        if (! $root) {
            return '';
        }
        $xp = new \DOMXPath($dom);
        $parts = [];
        foreach ($xp->query('.//text()', $root) as $node) {
            if ($this->hasAncestor($node, ['em', 'i', 'code'])) {
                continue;
            }
            $parts[] = $node->nodeValue;
        }

        return trim(preg_replace('/[ \t]+/', ' ', implode(' ', $parts)));
    }

    private function hasAncestor(\DOMNode $node, array $tags): bool
    {
        for ($p = $node->parentNode; $p; $p = $p->parentNode) {
            if (in_array(strtolower($p->nodeName), $tags, true)) {
                return true;
            }
        }

        return false;
    }

    private function replaceNodeWithHtml(\DOMDocument $dom, \DOMNode $node, string $html): void
    {
        $tmp = new \DOMDocument();
        libxml_use_internal_errors(true);
        $tmp->loadHTML('<?xml encoding="utf-8"?><span id="w">' . $html . '</span>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xp = new \DOMXPath($tmp);
        $w = $xp->query('//*[@id="w"]')->item(0);
        if (! $w) {
            return;
        }
        foreach (iterator_to_array($w->childNodes) as $child) {
            $node->parentNode->insertBefore($dom->importNode($child, true), $node);
        }
        $node->parentNode->removeChild($node);
    }

    private function innerHtml(\DOMDocument $dom, \DOMElement $root): string
    {
        $html = '';
        foreach ($root->childNodes as $c) {
            $html .= $dom->saveHTML($c);
        }

        return $html;
    }

    private function replaceWord(string $text, string $wrong, string $right, bool $preserveCase = true): string
    {
        return preg_replace_callback('/\b' . preg_quote($wrong, '/') . '\b/iu', function ($m) use ($right, $preserveCase) {
            if (! $preserveCase) {
                return $right;
            }
            $w = $m[0];
            if (mb_strlen($w) > 1 && mb_strtoupper($w, 'UTF-8') === $w) {
                return mb_strtoupper($right, 'UTF-8');
            }
            if (mb_substr($w, 0, 1) === mb_strtoupper(mb_substr($w, 0, 1), 'UTF-8')) {
                return mb_strtoupper(mb_substr($right, 0, 1), 'UTF-8') . mb_substr($right, 1);
            }

            return $right;
        }, $text);
    }

    private function sentenceAround(string $text, int $pos): string
    {
        $len = strlen($text);
        $pos = max(0, min($pos, $len - 1));
        $delims = ['.', '!', '?', "\n"];
        $start = 0;
        for ($i = $pos; $i > 0; $i--) {
            if (in_array($text[$i - 1], $delims, true)) {
                $start = $i;
                break;
            }
        }
        $end = $len;
        for ($i = $pos; $i < $len; $i++) {
            if (in_array($text[$i], $delims, true)) {
                $end = $i + 1;
                break;
            }
        }

        return $this->limit(trim(substr($text, $start, $end - $start)));
    }

    private function limit(string $s, int $max = 180): string
    {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1) . '…' : $s;
    }

    private function issue(string $category, string $original, string $suggestion, string $explanation): array
    {
        return [
            'category' => $category,
            'original_text' => $original,
            'suggestion' => $suggestion,
            'explanation' => $explanation,
        ];
    }

    private function capPerCategory(array $issues): array
    {
        $counts = [];
        $kept = [];
        foreach ($issues as $i) {
            $c = $i['category'];
            $counts[$c] = ($counts[$c] ?? 0) + 1;
            if ($counts[$c] <= self::MAX_PER_CATEGORY) {
                $kept[] = $i;
            }
        }

        return $kept;
    }

    private function scoreFrom(array $issues): int
    {
        $penalty = 0;
        foreach ($issues as $i) {
            $penalty += self::PENALTY[$i['category']] ?? 4;
        }

        return (int) max(0, min(100, 100 - $penalty));
    }

    private function summarize(array $issues): string
    {
        if (empty($issues)) {
            return 'Tata tulis sudah baik — tidak ditemukan isu signifikan.';
        }
        $counts = [];
        foreach ($issues as $i) {
            $counts[$i['category']] = ($counts[$i['category']] ?? 0) + 1;
        }
        arsort($counts);
        $parts = [];
        foreach ($counts as $cat => $n) {
            $parts[] = "{$n} " . (self::LABELS[$cat] ?? $cat);
        }

        return 'Ditemukan ' . array_sum($counts) . ' catatan tata tulis: ' . implode(', ', $parts) . '.';
    }

    private function empty(string $note): array
    {
        return [
            'score' => null,
            'summary' => $note,
            'total_issues' => 0,
            'issues' => [],
            'corrected_text' => null,
            'checked_words' => 0,
        ];
    }
}
