<?php

namespace App\Services\Analytics;

use App\Models\ApplyFormEvent;
use App\Models\PoolCandidate;
use App\Models\PoolCompany;
use Illuminate\Support\Facades\DB;

class GeoAnalyticsService
{
    /**
     * Candidates grouped by country_code with counts.
     */
    public function candidatesByCountry(string $from, string $to, ?string $industry = null): array
    {
        $query = PoolCandidate::query()
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '');

        if ($industry) {
            $query->where('primary_industry', $industry);
        }

        return $query
            ->selectRaw('country_code, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN (\'in_pool\', \'presented_to_company\', \'hired\') THEN 1 ELSE 0 END) as qualified')
            ->selectRaw('SUM(CASE WHEN status = \'hired\' THEN 1 ELSE 0 END) as hired')
            ->groupBy('country_code')
            ->orderByDesc('total')
            ->get()
            ->map(fn($row) => [
                'country_code' => $row->country_code,
                'total' => (int) $row->total,
                'qualified' => (int) $row->qualified,
                'hired' => (int) $row->hired,
                'qualification_rate' => $row->total > 0
                    ? round(($row->qualified / $row->total) * 100, 1)
                    : 0,
            ])
            ->toArray();
    }

    /**
     * Companies grouped by country with counts.
     */
    public function companiesByCountry(): array
    {
        return PoolCompany::query()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->selectRaw('country, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = \'active\' THEN 1 ELSE 0 END) as active')
            ->groupBy('country')
            ->orderByDesc('total')
            ->get()
            ->map(fn($row) => [
                'country' => $row->country,
                'total' => (int) $row->total,
                'active' => (int) $row->active,
            ])
            ->toArray();
    }

    /**
     * Hourly distribution of apply submissions (UTC).
     */
    public function hourlyApplyDistribution(string $from, string $to): array
    {
        $rows = PoolCandidate::query()
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->pluck('total', 'hour')
            ->toArray();

        // Fill all 24 hours
        $distribution = [];
        for ($h = 0; $h < 24; $h++) {
            $distribution[] = [
                'hour' => $h,
                'label' => sprintf('%02d:00', $h),
                'total' => $rows[$h] ?? 0,
            ];
        }

        return $distribution;
    }

    /**
     * Heat intensity data for map visualization.
     * Returns weighted scores per country based on volume + quality.
     */
    public function mapHeatIntensity(string $from, string $to): array
    {
        return PoolCandidate::query()
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->selectRaw('country_code')
            ->selectRaw('COUNT(*) as volume')
            ->selectRaw('AVG(CASE WHEN status IN (\'in_pool\', \'presented_to_company\', \'hired\') THEN 1 ELSE 0 END) * 100 as quality_pct')
            ->groupBy('country_code')
            ->having('volume', '>=', 1)
            ->get()
            ->map(function ($row) {
                // Weighted intensity: 60% volume (log-scaled) + 40% quality
                $volumeScore = min(100, log(max(1, $row->volume), 2) * 15);
                $qualityScore = $row->quality_pct ?? 0;
                $intensity = round(($volumeScore * 0.6) + ($qualityScore * 0.4), 1);

                return [
                    'country_code' => $row->country_code,
                    'volume' => (int) $row->volume,
                    'quality_pct' => round($row->quality_pct, 1),
                    'intensity' => min(100, $intensity),
                ];
            })
            ->sortByDesc('intensity')
            ->values()
            ->toArray();
    }

    /**
     * Apply form drop-off analysis.
     * Shows where users abandon the apply flow.
     */
    public function applyDropOffAnalysis(string $from, string $to): array
    {
        $events = ApplyFormEvent::inDateRange($from, $to);

        // Unique sessions
        $totalSessions = (clone $events)->distinct('session_id')->count('session_id');

        // Sessions per step (step_view)
        $stepViews = (clone $events)
            ->where('event_type', ApplyFormEvent::EVENT_STEP_VIEW)
            ->selectRaw('step_number, COUNT(DISTINCT session_id) as sessions')
            ->groupBy('step_number')
            ->pluck('sessions', 'step_number')
            ->toArray();

        // Step completions
        $stepCompletes = (clone $events)
            ->where('event_type', ApplyFormEvent::EVENT_STEP_COMPLETE)
            ->selectRaw('step_number, COUNT(DISTINCT session_id) as sessions')
            ->groupBy('step_number')
            ->pluck('sessions', 'step_number')
            ->toArray();

        // Submissions
        $submissions = (clone $events)
            ->where('event_type', ApplyFormEvent::EVENT_SUBMIT)
            ->distinct('session_id')
            ->count('session_id');

        // Abandons per step
        $abandons = (clone $events)
            ->where('event_type', ApplyFormEvent::EVENT_ABANDON)
            ->selectRaw('step_number, COUNT(DISTINCT session_id) as sessions')
            ->groupBy('step_number')
            ->pluck('sessions', 'step_number')
            ->toArray();

        // Average time per step
        $avgTimePerStep = (clone $events)
            ->where('event_type', ApplyFormEvent::EVENT_STEP_COMPLETE)
            ->whereNotNull('time_spent_seconds')
            ->selectRaw('step_number, AVG(time_spent_seconds) as avg_seconds, PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY time_spent_seconds) as median_seconds')
            ->groupBy('step_number');

        // MySQL doesn't support PERCENTILE_CONT, use alternative
        $avgTimePerStep = (clone $events)
            ->where('event_type', ApplyFormEvent::EVENT_STEP_COMPLETE)
            ->whereNotNull('time_spent_seconds')
            ->selectRaw('step_number, AVG(time_spent_seconds) as avg_seconds')
            ->groupBy('step_number')
            ->pluck('avg_seconds', 'step_number')
            ->toArray();

        // Build funnel
        $steps = [
            1 => ['name' => 'Personal Info', 'key' => 'personal'],
            2 => ['name' => 'Maritime Experience', 'key' => 'maritime'],
            3 => ['name' => 'Confirm & Submit', 'key' => 'confirm'],
        ];

        $funnel = [];
        foreach ($steps as $num => $step) {
            $viewed = $stepViews[$num] ?? 0;
            $completed = $stepCompletes[$num] ?? 0;
            $abandoned = $abandons[$num] ?? 0;
            $prevViewed = $num === 1 ? $totalSessions : ($stepViews[$num] ?? 0);

            $funnel[] = [
                'step' => $num,
                'name' => $step['name'],
                'key' => $step['key'],
                'sessions_viewed' => $viewed,
                'sessions_completed' => $completed,
                'sessions_abandoned' => $abandoned,
                'completion_rate' => $viewed > 0 ? round(($completed / $viewed) * 100, 1) : 0,
                'drop_off_rate' => $viewed > 0 ? round((($viewed - $completed) / $viewed) * 100, 1) : 0,
                'avg_time_seconds' => isset($avgTimePerStep[$num]) ? round($avgTimePerStep[$num]) : null,
            ];
        }

        // Top abandon reasons from meta
        $abandonReasons = ApplyFormEvent::inDateRange($from, $to)
            ->where('event_type', ApplyFormEvent::EVENT_ABANDON)
            ->whereNotNull('meta')
            ->get()
            ->pluck('meta')
            ->flatMap(function ($meta) {
                $reasons = [];
                if (isset($meta['field_errors']) && is_array($meta['field_errors'])) {
                    foreach ($meta['field_errors'] as $field) {
                        $reasons[] = $field;
                    }
                }
                if (isset($meta['reason'])) {
                    $reasons[] = $meta['reason'];
                }
                return $reasons;
            })
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->toArray();

        // Country breakdown for drop-offs
        $dropOffByCountry = ApplyFormEvent::inDateRange($from, $to)
            ->where('event_type', ApplyFormEvent::EVENT_ABANDON)
            ->whereNotNull('country_code')
            ->selectRaw('country_code, COUNT(DISTINCT session_id) as abandon_sessions')
            ->groupBy('country_code')
            ->orderByDesc('abandon_sessions')
            ->limit(15)
            ->pluck('abandon_sessions', 'country_code')
            ->toArray();

        return [
            'period' => ['from' => $from, 'to' => $to],
            'total_sessions' => $totalSessions,
            'total_submissions' => $submissions,
            'overall_conversion' => $totalSessions > 0
                ? round(($submissions / $totalSessions) * 100, 1)
                : 0,
            'funnel' => $funnel,
            'top_abandon_reasons' => $abandonReasons,
            'drop_off_by_country' => $dropOffByCountry,
        ];
    }

    /**
     * Combined dashboard for GeoAnalytics.
     */
    public function dashboard(string $from, string $to, ?string $industry = null): array
    {
        return [
            'candidates_by_country' => $this->candidatesByCountry($from, $to, $industry),
            'companies_by_country' => $this->companiesByCountry(),
            'hourly_distribution' => $this->hourlyApplyDistribution($from, $to),
            'heat_map' => $this->mapHeatIntensity($from, $to),
            'drop_off' => $this->applyDropOffAnalysis($from, $to),
        ];
    }
}
