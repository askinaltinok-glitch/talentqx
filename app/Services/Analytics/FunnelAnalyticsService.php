<?php

namespace App\Services\Analytics;

use App\Models\CandidatePresentation;
use App\Models\FormInterview;
use App\Models\PoolCandidate;
use App\Models\TalentRequest;
use Illuminate\Support\Facades\DB;

/**
 * FunnelAnalyticsService
 *
 * Investor-grade analytics for:
 * - Candidate funnel (Apply → Interview → Pool → Present → Hire)
 * - Channel quality (CAC, conversion rates by source)
 * - Time-to-hire metrics
 * - Pool health metrics
 */
class FunnelAnalyticsService
{
    /**
     * Get complete funnel metrics for a date range.
     */
    public function getFunnelMetrics(
        string $startDate,
        string $endDate,
        ?string $industry = null,
        ?string $sourceChannel = null
    ): array {
        $baseQuery = PoolCandidate::query()
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);

        if ($industry) {
            $baseQuery->where('primary_industry', $industry);
        }

        if ($sourceChannel) {
            $baseQuery->where('source_channel', $sourceChannel);
        }

        // Stage counts
        $registered = (clone $baseQuery)->count();
        $assessed = (clone $baseQuery)
            ->whereIn('status', [
                PoolCandidate::STATUS_ASSESSED,
                PoolCandidate::STATUS_IN_POOL,
                PoolCandidate::STATUS_PRESENTED,
                PoolCandidate::STATUS_HIRED,
            ])
            ->count();
        $inPool = (clone $baseQuery)
            ->whereIn('status', [
                PoolCandidate::STATUS_IN_POOL,
                PoolCandidate::STATUS_PRESENTED,
                PoolCandidate::STATUS_HIRED,
            ])
            ->count();
        $presented = (clone $baseQuery)
            ->whereIn('status', [
                PoolCandidate::STATUS_PRESENTED,
                PoolCandidate::STATUS_HIRED,
            ])
            ->count();
        $hired = (clone $baseQuery)
            ->where('status', PoolCandidate::STATUS_HIRED)
            ->count();

        // Conversion rates
        $registeredToAssessed = $registered > 0 ? round(($assessed / $registered) * 100, 1) : 0;
        $assessedToPool = $assessed > 0 ? round(($inPool / $assessed) * 100, 1) : 0;
        $poolToPresented = $inPool > 0 ? round(($presented / $inPool) * 100, 1) : 0;
        $presentedToHired = $presented > 0 ? round(($hired / $presented) * 100, 1) : 0;
        $overallConversion = $registered > 0 ? round(($hired / $registered) * 100, 2) : 0;

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'industry' => $industry ?? 'all',
                'source_channel' => $sourceChannel ?? 'all',
            ],
            'funnel' => [
                'registered' => $registered,
                'assessed' => $assessed,
                'in_pool' => $inPool,
                'presented' => $presented,
                'hired' => $hired,
            ],
            'conversion_rates' => [
                'registered_to_assessed' => $registeredToAssessed,
                'assessed_to_pool' => $assessedToPool,
                'pool_to_presented' => $poolToPresented,
                'presented_to_hired' => $presentedToHired,
                'overall_conversion' => $overallConversion,
            ],
            'drop_off' => [
                'registration_drop' => $registered - $assessed,
                'assessment_drop' => $assessed - $inPool,
                'pool_drop' => $inPool - $presented,
                'presentation_drop' => $presented - $hired,
            ],
        ];
    }

    /**
     * Get channel quality metrics.
     */
    public function getChannelQuality(
        string $startDate,
        string $endDate,
        ?string $industry = null
    ): array {
        $query = PoolCandidate::query()
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->selectRaw('
                source_channel,
                COUNT(*) as total_candidates,
                SUM(CASE WHEN status IN (\'in_pool\', \'presented_to_company\', \'hired\') THEN 1 ELSE 0 END) as pooled,
                SUM(CASE WHEN status = \'hired\' THEN 1 ELSE 0 END) as hired,
                AVG(CASE WHEN status IN (\'in_pool\', \'presented_to_company\', \'hired\') THEN 1 ELSE 0 END) * 100 as pool_rate,
                AVG(CASE WHEN status = \'hired\' THEN 1 ELSE 0 END) * 100 as hire_rate
            ')
            ->groupBy('source_channel');

        if ($industry) {
            $query->where('primary_industry', $industry);
        }

        $channels = $query->get();

        // Get average interview scores by channel
        $scoresByChannel = FormInterview::query()
            ->join('pool_candidates', 'form_interviews.pool_candidate_id', '=', 'pool_candidates.id')
            ->whereBetween('form_interviews.completed_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('form_interviews.status', 'completed')
            ->when($industry, fn($q) => $q->where('pool_candidates.primary_industry', $industry))
            ->selectRaw('
                pool_candidates.source_channel,
                AVG(COALESCE(form_interviews.calibrated_score, form_interviews.final_score)) as avg_score,
                COUNT(*) as interview_count
            ')
            ->groupBy('pool_candidates.source_channel')
            ->pluck('avg_score', 'source_channel')
            ->toArray();

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'industry' => $industry ?? 'all',
            ],
            'channels' => $channels->map(function ($channel) use ($scoresByChannel) {
                return [
                    'channel' => $channel->source_channel,
                    'candidates' => $channel->total_candidates,
                    'pooled' => $channel->pooled,
                    'hired' => $channel->hired,
                    'pool_rate' => round($channel->pool_rate, 1),
                    'hire_rate' => round($channel->hire_rate, 2),
                    'avg_score' => isset($scoresByChannel[$channel->source_channel])
                        ? round($scoresByChannel[$channel->source_channel], 1)
                        : null,
                    'quality_score' => $this->calculateChannelQualityScore(
                        $channel->pool_rate,
                        $channel->hire_rate,
                        $scoresByChannel[$channel->source_channel] ?? null
                    ),
                ];
            })->sortByDesc('quality_score')->values(),
        ];
    }

    /**
     * Get time-to-hire metrics.
     */
    public function getTimeToHireMetrics(
        string $startDate,
        string $endDate,
        ?string $industry = null
    ): array {
        $hired = PoolCandidate::query()
            ->where('status', PoolCandidate::STATUS_HIRED)
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->when($industry, fn($q) => $q->where('primary_industry', $industry))
            ->get();

        if ($hired->isEmpty()) {
            return [
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
                'sample_size' => 0,
                'avg_days_to_hire' => null,
                'median_days_to_hire' => null,
                'percentiles' => [],
            ];
        }

        // Get presentations that led to hire
        $presentationTimes = CandidatePresentation::query()
            ->whereIn('pool_candidate_id', $hired->pluck('id'))
            ->where('status', CandidatePresentation::STATUS_HIRED)
            ->get()
            ->mapWithKeys(function ($pres) {
                $daysToHire = $pres->presented_at->diffInDays($pres->updated_at);
                return [$pres->pool_candidate_id => $daysToHire];
            });

        // Calculate registration to hire times
        $daysToHire = $hired->map(function ($candidate) use ($presentationTimes) {
            // From registration to last update (approximation of hire date)
            return $candidate->created_at->diffInDays($candidate->updated_at);
        })->sort()->values();

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'industry' => $industry ?? 'all',
            ],
            'sample_size' => $daysToHire->count(),
            'avg_days_to_hire' => round($daysToHire->avg(), 1),
            'median_days_to_hire' => round($daysToHire->median(), 1),
            'min_days' => $daysToHire->min(),
            'max_days' => $daysToHire->max(),
            'percentiles' => [
                'p25' => $this->percentile($daysToHire->toArray(), 25),
                'p50' => $this->percentile($daysToHire->toArray(), 50),
                'p75' => $this->percentile($daysToHire->toArray(), 75),
                'p90' => $this->percentile($daysToHire->toArray(), 90),
            ],
        ];
    }

    /**
     * Get pool health metrics.
     */
    public function getPoolHealthMetrics(?string $industry = null): array
    {
        $baseQuery = PoolCandidate::inPool();

        if ($industry) {
            $baseQuery->where('primary_industry', $industry);
        }

        $totalInPool = (clone $baseQuery)->count();

        // Age distribution (days since last assessed)
        $ageDistribution = (clone $baseQuery)
            ->selectRaw('
                CASE
                    WHEN DATEDIFF(NOW(), last_assessed_at) <= 7 THEN "0-7 days"
                    WHEN DATEDIFF(NOW(), last_assessed_at) <= 30 THEN "8-30 days"
                    WHEN DATEDIFF(NOW(), last_assessed_at) <= 90 THEN "31-90 days"
                    ELSE "90+ days"
                END as age_bucket,
                COUNT(*) as count
            ')
            ->groupBy('age_bucket')
            ->pluck('count', 'age_bucket')
            ->toArray();

        // English level distribution
        $englishDistribution = (clone $baseQuery)
            ->selectRaw('english_level_self, COUNT(*) as count')
            ->groupBy('english_level_self')
            ->pluck('count', 'english_level_self')
            ->toArray();

        // Source channel distribution
        $sourceDistribution = (clone $baseQuery)
            ->selectRaw('source_channel, COUNT(*) as count')
            ->groupBy('source_channel')
            ->pluck('count', 'source_channel')
            ->toArray();

        // Maritime-specific: seafarers vs non
        $seafarerCount = (clone $baseQuery)->where('seafarer', true)->count();

        // Candidates with complete assessments
        $withEnglishAssessment = (clone $baseQuery)
            ->whereHas('formInterviews', function ($q) {
                $q->where('english_assessment_status', 'completed');
            })
            ->count();

        $withVideoAssessment = (clone $baseQuery)
            ->whereHas('formInterviews', function ($q) {
                $q->whereNotNull('video_assessment_url');
            })
            ->count();

        // Stale pool (not presented in 60+ days)
        $staleCount = (clone $baseQuery)
            ->where('last_assessed_at', '<', now()->subDays(60))
            ->count();

        return [
            'industry' => $industry ?? 'all',
            'total_in_pool' => $totalInPool,
            'health_score' => $this->calculatePoolHealthScore(
                $totalInPool,
                $staleCount,
                $withEnglishAssessment,
                $withVideoAssessment
            ),
            'freshness' => [
                'age_distribution' => $ageDistribution,
                'stale_count' => $staleCount,
                'stale_percentage' => $totalInPool > 0 ? round(($staleCount / $totalInPool) * 100, 1) : 0,
            ],
            'completeness' => [
                'with_english_assessment' => $withEnglishAssessment,
                'with_video_assessment' => $withVideoAssessment,
                'english_rate' => $totalInPool > 0 ? round(($withEnglishAssessment / $totalInPool) * 100, 1) : 0,
                'video_rate' => $totalInPool > 0 ? round(($withVideoAssessment / $totalInPool) * 100, 1) : 0,
            ],
            'distribution' => [
                'by_english_level' => $englishDistribution,
                'by_source_channel' => $sourceDistribution,
                'seafarers' => $seafarerCount,
                'non_seafarers' => $totalInPool - $seafarerCount,
            ],
        ];
    }

    /**
     * Get company consumption metrics.
     */
    public function getCompanyMetrics(
        string $startDate,
        string $endDate,
        ?string $companyId = null
    ): array {
        $query = TalentRequest::query()
            ->with(['company', 'presentations'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);

        if ($companyId) {
            $query->where('pool_company_id', $companyId);
        }

        $requests = $query->get();

        // Aggregate metrics
        $totalRequests = $requests->count();
        $totalPresented = $requests->sum('presented_count');
        $totalHired = $requests->sum('hired_count');
        $fulfillmentRate = $totalRequests > 0
            ? round(($requests->where('status', 'fulfilled')->count() / $totalRequests) * 100, 1)
            : 0;

        // By status
        $byStatus = $requests->groupBy('status')
            ->map(fn($group) => $group->count())
            ->toArray();

        // By industry
        $byIndustry = $requests->groupBy('industry_code')
            ->map(fn($group) => [
                'requests' => $group->count(),
                'presented' => $group->sum('presented_count'),
                'hired' => $group->sum('hired_count'),
            ])
            ->toArray();

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'company_id' => $companyId ?? 'all',
            ],
            'summary' => [
                'total_requests' => $totalRequests,
                'total_candidates_presented' => $totalPresented,
                'total_hired' => $totalHired,
                'fulfillment_rate' => $fulfillmentRate,
                'avg_candidates_per_request' => $totalRequests > 0
                    ? round($totalPresented / $totalRequests, 1)
                    : 0,
                'conversion_rate' => $totalPresented > 0
                    ? round(($totalHired / $totalPresented) * 100, 1)
                    : 0,
            ],
            'by_status' => $byStatus,
            'by_industry' => $byIndustry,
        ];
    }

    /**
     * Get weekly trend metrics for charts.
     */
    public function getWeeklyTrends(int $weeks = 12, ?string $industry = null): array
    {
        $trends = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek()->toDateString();
            $weekEnd = now()->subWeeks($i)->endOfWeek()->toDateString();

            $query = PoolCandidate::query()
                ->whereBetween('created_at', [$weekStart, $weekEnd . ' 23:59:59']);

            if ($industry) {
                $query->where('primary_industry', $industry);
            }

            $trends[] = [
                'week' => $weekStart,
                'registrations' => (clone $query)->count(),
                'assessments' => FormInterview::query()
                    ->whereBetween('completed_at', [$weekStart, $weekEnd . ' 23:59:59'])
                    ->when($industry, fn($q) => $q->where('industry_code', $industry))
                    ->count(),
                'hires' => (clone $query)
                    ->where('status', PoolCandidate::STATUS_HIRED)
                    ->count(),
            ];
        }

        return [
            'industry' => $industry ?? 'all',
            'weeks' => $weeks,
            'data' => $trends,
        ];
    }

    /**
     * Calculate channel quality score (0-100).
     */
    private function calculateChannelQualityScore(
        float $poolRate,
        float $hireRate,
        ?float $avgScore
    ): int {
        $score = 0;

        // Pool rate contributes 40%
        $score += min(40, $poolRate * 0.4);

        // Hire rate contributes 30% (scaled x10 since it's usually small)
        $score += min(30, $hireRate * 3);

        // Avg score contributes 30%
        if ($avgScore !== null) {
            $score += min(30, ($avgScore / 100) * 30);
        }

        return (int) round($score);
    }

    /**
     * Calculate pool health score (0-100).
     */
    private function calculatePoolHealthScore(
        int $total,
        int $stale,
        int $withEnglish,
        int $withVideo
    ): int {
        if ($total === 0) {
            return 0;
        }

        $score = 100;

        // Penalize for stale candidates (up to -30)
        $stalePct = ($stale / $total) * 100;
        $score -= min(30, $stalePct * 0.6);

        // Reward for completeness (up to +20 each)
        $englishPct = ($withEnglish / $total) * 100;
        $videoPct = ($withVideo / $total) * 100;

        // If we're below 50% completeness, penalize
        if ($englishPct < 50) {
            $score -= (50 - $englishPct) * 0.2;
        }
        if ($videoPct < 50) {
            $score -= (50 - $videoPct) * 0.2;
        }

        return max(0, min(100, (int) round($score)));
    }

    /**
     * Calculate percentile value.
     */
    private function percentile(array $data, int $percentile): float
    {
        if (empty($data)) {
            return 0;
        }

        sort($data);
        $index = ($percentile / 100) * (count($data) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        $fraction = $index - $lower;

        if ($lower == $upper) {
            return $data[$lower];
        }

        return round($data[$lower] + ($data[$upper] - $data[$lower]) * $fraction, 1);
    }
}
