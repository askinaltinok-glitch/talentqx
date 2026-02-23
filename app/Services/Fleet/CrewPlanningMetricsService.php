<?php

namespace App\Services\Fleet;

use App\Models\CrewPlanningMetric;
use App\Models\FleetVessel;
use App\Models\VesselAssignment;
use Illuminate\Support\Facades\DB;

class CrewPlanningMetricsService
{
    /**
     * Record time_to_fill_rank when an assignment is created.
     * Measures days from when the gap was detected (manning req created) to assignment.
     */
    public function recordTimeToFill(VesselAssignment $assignment): void
    {
        $vessel = $assignment->vessel;
        if (!$vessel) return;

        // Time from vessel manning requirement creation to assignment creation
        $manning = $vessel->manningRequirements()
            ->where('rank_code', $assignment->rank_code)
            ->first();

        if (!$manning) return;

        $days = $manning->created_at->diffInDays($assignment->created_at);

        CrewPlanningMetric::create([
            'company_id' => $vessel->company_id,
            'vessel_id' => $vessel->id,
            'metric_type' => CrewPlanningMetric::TYPE_TIME_TO_FILL,
            'rank_code' => $assignment->rank_code,
            'value' => $days,
            'period_date' => now()->toDateString(),
        ]);
    }

    /**
     * Record availability_match_rate for a vessel.
     * % of recommended candidates that were "available" or "soon_available".
     */
    public function recordAvailabilityMatchRate(
        string $companyId,
        string $vesselId,
        int $totalRecommended,
        int $availableCount,
    ): void {
        if ($totalRecommended === 0) return;

        $rate = round(($availableCount / $totalRecommended) * 100, 2);

        CrewPlanningMetric::create([
            'company_id' => $companyId,
            'vessel_id' => $vesselId,
            'metric_type' => CrewPlanningMetric::TYPE_AVAIL_MATCH_RATE,
            'value' => $rate,
            'period_date' => now()->toDateString(),
        ]);
    }

    /**
     * Get KPI summary for a company.
     */
    public function getCompanyKPIs(string $companyId, int $days = 30): array
    {
        $since = now()->subDays($days)->toDateString();

        $avgTimeToFill = CrewPlanningMetric::where('company_id', $companyId)
            ->where('metric_type', CrewPlanningMetric::TYPE_TIME_TO_FILL)
            ->where('period_date', '>=', $since)
            ->avg('value');

        $avgAvailRate = CrewPlanningMetric::where('company_id', $companyId)
            ->where('metric_type', CrewPlanningMetric::TYPE_AVAIL_MATCH_RATE)
            ->where('period_date', '>=', $since)
            ->avg('value');

        $overlapCount = CrewPlanningMetric::where('company_id', $companyId)
            ->where('metric_type', CrewPlanningMetric::TYPE_OVERLAP_REDUCTION)
            ->where('period_date', '>=', $since)
            ->count();

        return [
            'avg_time_to_fill_days' => $avgTimeToFill !== null ? round($avgTimeToFill, 1) : null,
            'avg_availability_match_rate' => $avgAvailRate !== null ? round($avgAvailRate, 1) : null,
            'contract_overlaps_detected' => $overlapCount,
            'period_days' => $days,
        ];
    }
}
