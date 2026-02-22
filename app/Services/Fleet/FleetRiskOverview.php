<?php

namespace App\Services\Fleet;

use App\Models\CandidateContract;
use App\Models\Vessel;

class FleetRiskOverview
{
    private VesselRiskAggregator $aggregator;

    public function __construct(VesselRiskAggregator $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    /**
     * Compute fleet-level risk overview across all vessels with active crew.
     */
    public function compute(): array
    {
        $enabled = (bool) config('maritime.vessel_risk_v1');

        if (!$enabled) {
            return [
                'enabled' => false,
                'total_vessels' => 0,
                'vessels_by_tier' => ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0],
                'fleet_average_predictive' => null,
                'fleet_average_stability' => null,
                'top_5_highest_risk_vessels' => [],
            ];
        }

        // Find all vessel IDs with at least one active crew contract
        $vesselIds = CandidateContract::whereNull('end_date')
            ->whereNotNull('vessel_id')
            ->distinct()
            ->pluck('vessel_id');

        if ($vesselIds->isEmpty()) {
            return [
                'enabled' => true,
                'total_vessels' => 0,
                'vessels_by_tier' => ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0],
                'fleet_average_predictive' => null,
                'fleet_average_stability' => null,
                'top_5_highest_risk_vessels' => [],
            ];
        }

        $vesselRisks = [];
        $allPredictive = [];
        $allStability = [];
        $tierCounts = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];

        foreach ($vesselIds as $vesselId) {
            $result = $this->aggregator->compute($vesselId);
            if (!$result) {
                continue;
            }

            $vesselRisks[] = $result;
            $tierCounts[$result['vessel_tier']] = ($tierCounts[$result['vessel_tier']] ?? 0) + 1;

            if ($result['avg_predictive_risk'] !== null) {
                $allPredictive[] = $result['avg_predictive_risk'];
            }
            if ($result['avg_stability_index'] !== null) {
                $allStability[] = $result['avg_stability_index'];
            }
        }

        // Sort by predictive risk descending for top 5
        usort($vesselRisks, fn($a, $b) => ($b['avg_predictive_risk'] ?? 0) <=> ($a['avg_predictive_risk'] ?? 0));

        $vessels = Vessel::whereIn('id', $vesselIds)->get()->keyBy('id');

        $top5 = array_slice($vesselRisks, 0, 5);
        $top5Enriched = array_map(function ($r) use ($vessels) {
            $vessel = $vessels[$r['vessel_id']] ?? null;
            return [
                'vessel_id' => $r['vessel_id'],
                'vessel_name' => $vessel?->name ?? 'Unknown',
                'vessel_imo' => $vessel?->imo,
                'vessel_tier' => $r['vessel_tier'],
                'crew_count' => $r['crew_count'],
                'avg_predictive_risk' => $r['avg_predictive_risk'],
                'avg_stability_index' => $r['avg_stability_index'],
                'critical_risk_count' => $r['critical_risk_count'],
            ];
        }, $top5);

        return [
            'enabled' => true,
            'total_vessels' => count($vesselRisks),
            'vessels_by_tier' => $tierCounts,
            'fleet_average_predictive' => !empty($allPredictive) ? round(array_sum($allPredictive) / count($allPredictive), 2) : null,
            'fleet_average_stability' => !empty($allStability) ? round(array_sum($allStability) / count($allStability), 2) : null,
            'top_5_highest_risk_vessels' => $top5Enriched,
        ];
    }
}
