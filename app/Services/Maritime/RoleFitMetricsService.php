<?php

namespace App\Services\Maritime;

use Illuminate\Support\Facades\DB;

class RoleFitMetricsService
{
    private const MAX_WINDOW_HOURS = 168; // 7 days
    private const DEFAULT_WINDOW_HOURS = 24;

    /**
     * Compute aggregate metrics from role_fit_evaluations.
     * All heavy work stays in SQL — no row-level loading.
     */
    public function compute(int $windowHours = self::DEFAULT_WINDOW_HOURS): array
    {
        $windowHours = max(1, min($windowHours, self::MAX_WINDOW_HOURS));
        $since = now()->subHours($windowHours);

        // ── Counts by mismatch_level ──
        $counts = DB::table('role_fit_evaluations')
            ->where('created_at', '>=', $since)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN mismatch_level = 'strong' THEN 1 ELSE 0 END) as role_mismatch,
                SUM(CASE WHEN mismatch_level = 'weak' THEN 1 ELSE 0 END) as weak_mismatch,
                SUM(CASE WHEN mismatch_level = 'none' THEN 1 ELSE 0 END) as no_mismatch
            ")
            ->first();

        $total = (int) ($counts->total ?? 0);
        $roleMismatch = (int) ($counts->role_mismatch ?? 0);

        // ── Top mismatch roles (by applied_role_key where mismatch_level = strong) ──
        $topMismatchRoles = DB::table('role_fit_evaluations')
            ->where('created_at', '>=', $since)
            ->where('mismatch_level', 'strong')
            ->select('applied_role_key')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('applied_role_key')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'role_code' => $row->applied_role_key,
                'count' => (int) $row->count,
            ])
            ->values()
            ->toArray();

        // ── Score statistics (avg, p50, p90 via ordered sampling) ──
        $scoreStats = $this->computeScoreStats($since, $total);

        return [
            'window_hours' => $windowHours,
            'generated_at' => now()->toIso8601String(),
            'counts' => [
                'total' => $total,
                'role_mismatch' => $roleMismatch,
                'weak_mismatch' => (int) ($counts->weak_mismatch ?? 0),
                'no_mismatch' => (int) ($counts->no_mismatch ?? 0),
            ],
            'rates' => [
                'role_mismatch_pct' => $total > 0
                    ? round(($roleMismatch / $total) * 100, 2)
                    : 0.0,
            ],
            'top_mismatch_roles' => $topMismatchRoles,
            'score' => $scoreStats,
        ];
    }

    /**
     * Compute avg / p50 / p90 via SQL.
     * Uses ordered LIMIT for percentile approximation (MySQL-compatible).
     */
    private function computeScoreStats($since, int $total): array
    {
        if ($total === 0) {
            return ['avg' => 0.0, 'p50' => 0.0, 'p90' => 0.0];
        }

        $avg = DB::table('role_fit_evaluations')
            ->where('created_at', '>=', $since)
            ->avg('role_fit_score');

        // p50 = median: skip (total/2 - 1) rows, take 1
        $p50Offset = max(0, (int) floor($total * 0.50) - 1);
        $p50 = DB::table('role_fit_evaluations')
            ->where('created_at', '>=', $since)
            ->orderBy('role_fit_score')
            ->offset($p50Offset)
            ->limit(1)
            ->value('role_fit_score');

        // p90: skip (total*0.90 - 1) rows, take 1
        $p90Offset = max(0, (int) floor($total * 0.90) - 1);
        $p90 = DB::table('role_fit_evaluations')
            ->where('created_at', '>=', $since)
            ->orderBy('role_fit_score')
            ->offset($p90Offset)
            ->limit(1)
            ->value('role_fit_score');

        return [
            'avg' => round((float) ($avg ?? 0), 4),
            'p50' => round((float) ($p50 ?? 0), 4),
            'p90' => round((float) ($p90 ?? 0), 4),
        ];
    }
}
