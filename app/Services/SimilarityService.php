<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleLogbook;

/**
 * Pengecekan kemiripan jawaban antar mahasiswa pada satu tugas individu
 * (indikasi menyontek). Memakai Jaccard atas shingle 3-kata (cepat & tahan
 * perbedaan urutan kecil). Skor 0-100; 100 = identik.
 */
class SimilarityService
{
    private const SHINGLE = 3;

    public function check(Module $module): array
    {
        $threshold = (float) config('capstone.similarity_threshold', 60);

        $subs = ModuleLogbook::where('module_id', $module->id)
            ->whereNotNull('user_id')
            ->whereNotNull('payload_json')
            ->with('user')
            ->get();

        // Precompute shingle set per submission.
        $shingles = [];
        foreach ($subs as $s) {
            $shingles[$s->id] = $this->shingles($this->plain($module, $s));
        }

        $flagged = 0;
        foreach ($subs as $s) {
            $matches = [];
            $max = 0.0;
            foreach ($subs as $o) {
                if ($o->id === $s->id) {
                    continue;
                }
                $pct = $this->jaccard($shingles[$s->id], $shingles[$o->id]);
                if ($pct > $max) {
                    $max = $pct;
                }
                if ($pct >= $threshold) {
                    $matches[] = ['user_id' => $o->user_id, 'name' => optional($o->user)->name ?? '—', 'percent' => $pct];
                }
            }
            usort($matches, fn ($a, $b) => $b['percent'] <=> $a['percent']);

            $s->update([
                'similarity_max' => $max,
                'similarity_json' => $matches,
                'similarity_checked_at' => now(),
            ]);
            if (! empty($matches)) {
                $flagged++;
            }
        }

        return ['submissions' => $subs->count(), 'flagged' => $flagged, 'threshold' => $threshold];
    }

    /** Gabungkan teks field richtext (plaintext) dari satu submission. */
    private function plain(Module $module, ModuleLogbook $logbook): string
    {
        $payload = $logbook->payload_json ?? [];
        $parts = [];
        foreach ($module->fields() as $field) {
            if (($field['type'] ?? 'richtext') === 'richtext') {
                $val = $payload[$field['key']] ?? '';
                if (is_string($val)) {
                    $parts[] = strip_tags($val);
                }
            }
        }

        return html_entity_decode(implode(' ', $parts), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** Set shingle 3-kata (dinormalisasi). */
    private function shingles(string $text): array
    {
        $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text))), 'UTF-8');
        $words = preg_split('/\s+/', $norm, -1, PREG_SPLIT_NO_EMPTY);
        $set = [];
        if (count($words) < self::SHINGLE) {
            foreach ($words as $w) {
                $set[$w] = true;
            }

            return $set;
        }
        for ($i = 0; $i + self::SHINGLE <= count($words); $i++) {
            $set[implode(' ', array_slice($words, $i, self::SHINGLE))] = true;
        }

        return $set;
    }

    private function jaccard(array $a, array $b): float
    {
        if (empty($a) || empty($b)) {
            return 0.0;
        }
        $inter = count(array_intersect_key($a, $b));
        $union = count($a + $b);

        return $union ? round($inter / $union * 100, 2) : 0.0;
    }
}
