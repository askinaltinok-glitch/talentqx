<?php

namespace App\Services\Maritime;

use App\Models\PoolCandidate;

/**
 * Fleet Type Resolver — maps vessel types to fleet categories
 * and resolves the dominant fleet type from a candidate's contract history.
 *
 * Fleet types: tanker, bulk, container, river
 * Unknown vessel types map to null (no special profile).
 */
class FleetTypeResolver
{
    private const FLEET_MAP = [
        'tanker'        => 'tanker',
        'chemical'      => 'tanker',
        'lng_lpg'       => 'tanker',
        'bulk_carrier'  => 'bulk',
        'container'     => 'container',
        'river_vessel'  => 'river',
    ];

    /**
     * Resolve the dominant fleet type from a candidate's contracts.
     * Uses total contract duration per fleet type — longest fleet wins.
     * Returns null if no contracts or all vessels are unmapped types.
     */
    public function resolve(PoolCandidate $candidate): ?string
    {
        $contracts = $candidate->contracts()
            ->whereNotNull('vessel_type')
            ->whereNotNull('start_date')
            ->get();

        if ($contracts->isEmpty()) {
            return null;
        }

        $fleetDurations = [];
        foreach ($contracts as $contract) {
            $fleet = self::FLEET_MAP[$contract->vessel_type] ?? null;
            if (!$fleet) {
                continue;
            }
            $months = $contract->durationMonths();
            $fleetDurations[$fleet] = ($fleetDurations[$fleet] ?? 0) + $months;
        }

        if (empty($fleetDurations)) {
            return null;
        }

        arsort($fleetDurations);
        return array_key_first($fleetDurations);
    }

    /**
     * Static helper for direct vessel_type → fleet mapping.
     */
    public static function mapVesselType(string $vesselType): ?string
    {
        return self::FLEET_MAP[$vesselType] ?? null;
    }
}
