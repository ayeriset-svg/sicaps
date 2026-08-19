<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleLogbook;

/**
 * Pemeriksa INDIKASI penggunaan AI pada isi logbook (teks + gambar).
 *
 * PENTING: ini estimasi HEURISTIK (indikator skrining), BUKAN vonis dan BUKAN
 * detektor AI berbasis ML. Untuk akurasi tinggi, hubungkan API detektor eksternal
 * melalui provider (lihat detectTextViaProvider()).
 */
class AiDetectionService
{
    /**
     * Analisis satu logbook. Mengembalikan ringkasan + detail.
     */
    public function analyze(ModuleLogbook $logbook): array
    {
        $module = $logbook->module;
        $payload = $logbook->payload_json ?? [];

        // Kumpulkan HTML dari field richtext (link diabaikan).
        $htmls = [];
        foreach (($module?->fields() ?? []) as $field) {
            if (($field['type'] ?? 'richtext') === 'richtext') {
                $val = $payload[$field['key']] ?? '';
                if (is_string($val) && trim($val) !== '') {
                    $htmls[$field['label'] ?? $field['key']] = $val;
                }
            }
        }
        $combinedHtml = implode("\n\n", $htmls);

        $text = $this->analyzeText($combinedHtml);
        $image = $this->analyzeImages($htmls);

        // Gabungkan skor keseluruhan.
        $textPct = $text['score'];   // null jika teks terlalu pendek
        $imagePct = $image['score']; // null jika tak ada gambar

        if ($textPct !== null && $imagePct !== null) {
            $overall = round($textPct * 0.7 + $imagePct * 0.3, 1);
        } elseif ($textPct !== null) {
            $overall = $textPct;
        } elseif ($imagePct !== null) {
            $overall = $imagePct;
        } else {
            $overall = null;
        }

        $engine = $text['engine'] ?? 'heuristic';

        return [
            'overall' => $overall,
            'text' => $textPct,
            'image' => $imagePct,
            'detail' => [
                'text' => $text,
                'image' => $image,
                'engine' => $engine,
                'note' => $engine === 'provider'
                    ? 'Analisis teks memakai API detektor eksternal. Gambar via tanda-tangan metadata.'
                    : 'Estimasi indikatif (heuristik lokal), bukan vonis. Untuk akurasi tinggi, konfigurasi API detektor.',
            ],
        ];
    }

    /* ============================ TEKS ============================ */

    private function analyzeText(string $html): array
    {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $wc = count($words);

        if ($wc < 30) {
            return ['score' => null, 'words' => $wc, 'note' => 'Teks terlalu pendek untuk dianalisis (min. 30 kata).'];
        }

        // (Slot provider eksternal — jika dikonfigurasi, pakai hasilnya.)
        if (($ext = $this->detectTextViaProvider($text)) !== null) {
            return array_merge(['words' => $wc, 'engine' => 'provider'], $ext);
        }

        $lower = ' ' . mb_strtolower($text, 'UTF-8') . ' ';

        // Kalimat & panjangnya (untuk keseragaman + panjang rata-rata).
        $sentences = preg_split('/[.!?]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $lens = [];
        foreach ($sentences as $s) {
            $c = count(preg_split('/\s+/', trim($s), -1, PREG_SPLIT_NO_EMPTY));
            if ($c > 0) {
                $lens[] = $c;
            }
        }
        $n = max(1, count($lens));
        $mean = array_sum($lens) / $n;
        $var = 0.0;
        foreach ($lens as $l) {
            $var += ($l - $mean) ** 2;
        }
        $cv = $mean > 0 ? sqrt($var / $n) / $mean : 0;

        // 1) Keformalan: densitas konektor/register formal khas AI (per 1000 kata).
        $formalHits = 0;
        foreach (config('capstone.ai_phrases') as $p) {
            $formalHits += substr_count($lower, $p);
        }
        $formalScore = $this->clamp(($formalHits / $wc * 1000) * 7.5, 0, 100);

        // 2) Bahasa gaul/slang: SINYAL MANUSIA — makin banyak, makin bukan AI.
        $collHits = 0;
        foreach (config('capstone.ai_colloquial') as $c) {
            $collHits += substr_count($lower, $c);
        }
        $collDensity = $collHits / $wc * 1000;

        // 3) Rasio kata panjang (>=10 huruf) — kosakata canggih khas AI.
        $longWords = 0;
        foreach ($words as $w) {
            if (mb_strlen($w) >= 10) {
                $longWords++;
            }
        }
        $longWordScore = $this->clamp(($longWords / $wc) * 320, 0, 100);

        // 4) Panjang kalimat rata-rata (AI cenderung panjang).
        $sentLenScore = $this->clamp(($mean - 13) / 15 * 100, 0, 100);

        // 5) Keseragaman kalimat (variasi rendah = terstruktur seperti AI).
        $uniformity = $this->clamp((0.55 - $cv) / 0.55 * 100, 0, 100);

        // 6) Penanda struktur (enumerasi paralel yang disukai AI).
        $structureHits = preg_match_all('/(^|\s)(pertama|kedua|ketiga|keempat|selanjutnya|berikutnya)\b/u', $lower)
            + substr_count($lower, ' - ') + preg_match_all('/\b[1-9]\.\s/u', $text);
        $structureScore = $this->clamp($structureHits * 12, 0, 100);

        $raw = 0.42 * $formalScore
            + 0.18 * $longWordScore
            + 0.15 * $sentLenScore
            + 0.15 * $uniformity
            + 0.10 * $structureScore;

        // Bonus "bersih": nyaris tanpa slang + cukup formal = ciri kuat AI.
        if ($collDensity < 1 && $formalScore > 15) {
            $raw += 12;
        }

        // Penalti slang: kehadiran bahasa gaul menurunkan indikasi AI (sinyal manusia terkuat).
        $score = $raw * (1 - min(0.9, $collDensity * 0.05));
        $score = $this->clamp($score, 0, 100);

        return [
            'score' => round($score, 1),
            'engine' => 'heuristic',
            'words' => $wc,
            'sentences' => count($lens),
            'burstiness_cv' => round($cv, 2),
            'formal_hits' => $formalHits,
            'colloquial_hits' => $collHits,
            'avg_sentence_len' => round($mean, 1),
            'components' => [
                'formal' => round($formalScore, 1),
                'long_words' => round($longWordScore, 1),
                'sentence_len' => round($sentLenScore, 1),
                'uniformity' => round($uniformity, 1),
                'structure' => round($structureScore, 1),
                'colloquial_density' => round($collDensity, 1),
            ],
        ];
    }

    /**
     * Integrasi API detektor AI eksternal (opsional). Return null bila tak dikonfigurasi
     * atau gagal → otomatis fallback ke heuristik.
     *
     * Konfigurasi di config/services.php ('ai_detector') via .env:
     *   AI_DETECTOR_ENABLED, AI_DETECTOR_URL, AI_DETECTOR_KEY,
     *   AI_DETECTOR_TEXT_FIELD (default "text"),
     *   AI_DETECTOR_RESPONSE_PATH (dot-notation ke nilai %, mis. "score" / "documents.0.average_generated_prob"),
     *   AI_DETECTOR_SCALE (1 bila respons 0-100; 100 bila 0-1).
     */
    private function detectTextViaProvider(string $text): ?array
    {
        $cfg = config('services.ai_detector');
        if (! $cfg || empty($cfg['enabled']) || empty($cfg['url'])) {
            return null;
        }

        try {
            $field = $cfg['text_field'] ?: 'text';
            $req = \Illuminate\Support\Facades\Http::timeout(30)->acceptJson();
            if (! empty($cfg['key'])) {
                $req = $req->withToken($cfg['key']);
            }
            $resp = $req->post($cfg['url'], [$field => $text]);
            if (! $resp->successful()) {
                return null;
            }

            $raw = data_get($resp->json(), $cfg['response_path'] ?: 'ai_percentage');
            if ($raw === null || ! is_numeric($raw)) {
                return null;
            }
            $pct = (float) $raw * (float) ($cfg['scale'] ?: 1);

            return [
                'score' => round($this->clamp($pct, 0, 100), 1),
                'engine' => 'provider',
                'provider' => $cfg['name'] ?? 'external-api',
            ];
        } catch (\Throwable $e) {
            // Diamkan; fallback ke heuristik.
            return null;
        }
    }

    /* ============================ GAMBAR ============================ */

    private function analyzeImages(array $htmls): array
    {
        // Ekstrak gambar base64 inline dari seluruh field.
        $images = [];
        foreach ($htmls as $label => $html) {
            if (preg_match_all('/<img[^>]+src=["\']data:image\/([a-z0-9.+-]+);base64,([^"\']+)["\']/i', $html, $m, PREG_SET_ORDER)) {
                foreach ($m as $img) {
                    $images[] = ['field' => $label, 'format' => strtolower($img[1]), 'data' => $img[2]];
                }
            }
        }

        $total = count($images);
        if ($total === 0) {
            return ['score' => null, 'total' => 0, 'flagged' => 0, 'items' => [], 'note' => 'Tidak ada gambar untuk diperiksa.'];
        }

        $signatures = config('capstone.ai_image_signatures');
        $flagged = 0;
        $items = [];

        foreach ($images as $i => $img) {
            $binary = base64_decode($img['data'], true);
            $hit = null;
            if ($binary !== false) {
                $hay = strtolower($binary);
                foreach ($signatures as $sig) {
                    // Dukung penanda "parameters\x00" (Stable Diffusion tEXt chunk).
                    $needle = str_replace('\x00', "\0", strtolower($sig));
                    if ($needle !== '' && str_contains($hay, $needle)) {
                        $hit = $sig;
                        break;
                    }
                }
                // Sinyal lemah: PNG tanpa metadata kamera & tanpa penanda — tidak di-flag.
            }
            if ($hit) {
                $flagged++;
            }
            $items[] = [
                'no' => $i + 1,
                'field' => $img['field'],
                'format' => $img['format'],
                'ai_signature' => $hit,
                'flagged' => (bool) $hit,
                'size_kb' => $binary !== false ? round(strlen($binary) / 1024, 1) : null,
            ];
        }

        return [
            'score' => round($flagged / $total * 100, 1),
            'total' => $total,
            'flagged' => $flagged,
            'items' => $items,
            'note' => 'Deteksi berbasis tanda-tangan metadata generator AI (Midjourney/SD/DALL·E/C2PA, dll). Gambar AI tanpa metadata tidak selalu terdeteksi.',
        ];
    }

    private function clamp(float $v, float $min, float $max): float
    {
        return max($min, min($max, $v));
    }
}
