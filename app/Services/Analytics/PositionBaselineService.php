<?php

namespace App\Services\Analytics;

use App\Models\FormInterview;
use Illuminate\Support\Carbon;

class PositionBaselineService
{
    /**
     * Baseline dims:
     *  - version, language, position_code, industry_code(optional)
     * Rolling window:
     *  - lastDays default 90
     *  - maxN default 200
     *
     * Fallback chain:
     *  (v,l,pos,industry) -> (v,l,pos,NULL) -> (v,l,__generic__,NULL)
     */
    public function baseline(array $dims, int $minN = 30, int $maxN = 200, int $lastDays = 90): array
    {
        $version = $dims['version'];
        $language = $dims['language'];
        $position = $dims['position_code'];
        $industry = $dims['industry_code'] ?? null;

        $candidates = [
            ['version' => $version, 'language' => $language, 'position_code' => $position, 'industry_code' => $industry],
            ['version' => $version, 'language' => $language, 'position_code' => $position, 'industry_code' => null],
            ['version' => $version, 'language' => $language, 'position_code' => '__generic__', 'industry_code' => null],
        ];

        foreach ($candidates as $cand) {
            $stats = $this->computeStats($cand, $maxN, $lastDays);
            if ($stats['n'] >= $minN) {
                $stats['baseline_dims_used'] = $cand;
                $stats['baseline_fallback_level'] = $this->fallbackLevel($cand, $dims);
                return $stats;
            }
        }

        // Yeterli veri yoksa: eldeki en iyi (en fazla n) sonucu dön.
        $best = null;
        foreach ($candidates as $cand) {
            $stats = $this->computeStats($cand, $maxN, $lastDays);
            $stats['baseline_dims_used'] = $cand;
            $stats['baseline_fallback_level'] = $this->fallbackLevel($cand, $dims);
            if ($best === null || $stats['n'] > $best['n']) {
                $best = $stats;
            }
        }

        return $best ?? [
            'n' => 0,
            'mean' => null,
            'std' => null,
            'median' => null,
            'mad' => null,
            'baseline_dims_used' => null,
            'baseline_fallback_level' => 'none',
            'window_days' => $lastDays,
            'max_n' => $maxN,
        ];
    }

    public function zScore(float $rawScore, ?float $mean, ?float $std): ?float
    {
        if ($mean === null || $std === null || $std <= 0.000001) {
            return null;
        }
        return ($rawScore - $mean) / $std;
    }

    /**
     * MVP mapping: z -> calibrated (0..100)
     * clamp z to [-2.5, +2.5] then map linearly to [0..100]
     */
    public function calibratedScoreFromZ(?float $z): ?int
    {
        if ($z === null) {
            return null;
        }
        $z = max(-2.5, min(2.5, $z));
        $p = ($z + 2.5) / 5.0; // 0..1
        return (int) round($p * 100);
    }

    private function computeStats(array $dims, int $maxN, int $lastDays): array
    {
        $since = Carbon::now()->subDays($lastDays);

        $q = FormInterview::query()
            ->where('version', $dims['version'])
            ->where('language', $dims['language'])
            ->where('position_code', $dims['position_code'])
            ->where('status', FormInterview::STATUS_COMPLETED)
            ->whereNotNull('raw_final_score')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $since);

        if (array_key_exists('industry_code', $dims) && $dims['industry_code'] !== null) {
            $q->where('industry_code', $dims['industry_code']);
        } else {
            $q->whereNull('industry_code');
        }

        $scores = $q->orderByDesc('completed_at')
            ->limit($maxN)
            ->pluck('raw_final_score')
            ->map(fn($v) => (float) $v)
            ->values()
            ->all();

        $n = count($scores);
        if ($n === 0) {
            return [
                'n' => 0,
                'mean' => null,
                'std' => null,
                'median' => null,
                'mad' => null,
                'window_days' => $lastDays,
                'max_n' => $maxN,
            ];
        }

        sort($scores);

        $mean = array_sum($scores) / $n;

        // sample std-dev
        $var = 0.0;
        if ($n > 1) {
            foreach ($scores as $s) {
                $var += ($s - $mean) ** 2;
            }
            $var = $var / ($n - 1);
        }
        $std = $n > 1 ? sqrt($var) : null;

        $median = $this->median($scores);
        $absDev = array_map(fn($s) => abs($s - $median), $scores);
        sort($absDev);
        $mad = $this->median($absDev); // robust dispersion (not scaled)

        return [
            'n' => $n,
            'mean' => round($mean, 2),
            'std' => $std ? round($std, 2) : null,
            'median' => round($median, 2),
            'mad' => round($mad, 2),
            'window_days' => $lastDays,
            'max_n' => $maxN,
        ];
    }

    private function median(array $sorted): float
    {
        $n = count($sorted);
        $mid = intdiv($n, 2);
        if ($n % 2 === 1) {
            return (float) $sorted[$mid];
        }
        return ((float) $sorted[$mid - 1] + (float) $sorted[$mid]) / 2.0;
    }

    private function fallbackLevel(array $used, array $requested): string
    {
        $reqIndustry = $requested['industry_code'] ?? null;

        if ($used['position_code'] === ($requested['position_code'] ?? null) && $used['industry_code'] === $reqIndustry) {
            return 'exact';
        }
        if ($used['position_code'] === ($requested['position_code'] ?? null) && $used['industry_code'] === null) {
            return 'pos_only';
        }
        if ($used['position_code'] === '__generic__') {
            return 'generic';
        }
        return 'unknown';
    }
}
