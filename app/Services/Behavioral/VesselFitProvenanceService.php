<?php

namespace App\Services\Behavioral;

use App\Models\CandidateCommandProfile;
use App\Models\CandidateContract;
use App\Models\PoolCandidate;

/**
 * VesselFitProvenanceService
 *
 * @deprecated Use VesselFitEvidenceService instead. This service only tags
 * provenance labels without adjusting fit scores. The new evidence service
 * computes scores from actual data sources with full explainability.
 *
 * Enriches behavioral vessel-fit scores with provenance tracking:
 * where does the evidence for each vessel type actually come from?
 *
 * Provenance sources (in priority order):
 * - contract_history: candidate has real contracts on this vessel type
 * - source_meta: candidate's apply-form declared vessel types
 * - behavioral_only: derived purely from behavioral interview scoring
 *
 * Suppression guard: if derived_from=behavioral_only AND confidence < 0.40,
 * the entry is marked suppressed=true (insufficient evidence).
 */
class VesselFitProvenanceService
{
    /**
     * Map behavioral fit codes (uppercase) to contract vessel_type values (lowercase).
     */
    private const CODE_TO_CONTRACT_TYPE = [
        'TANKER'         => ['tanker', 'oil_tanker', 'chemical_tanker', 'product_tanker'],
        'LNG'            => ['lng', 'lpg', 'lng_lpg', 'gas_carrier'],
        'CONTAINER_ULCS' => ['container', 'container_ship', 'ulcs'],
        'PASSENGER'      => ['passenger', 'cruise', 'ferry', 'ro_pax'],
        'OFFSHORE'       => ['offshore', 'platform_supply', 'ahts', 'fpso', 'construction'],
        'RIVER'          => ['river', 'inland', 'barge'],
        'COASTAL'        => ['coastal', 'coaster'],
        'SHORT_SEA'      => ['short_sea', 'shortsea'],
        'DEEP_SEA'       => ['deep_sea', 'deepsea', 'ocean'],
    ];

    /**
     * Enrich fit_json entries with provenance and suppression data.
     *
     * @param array $fitJson  The behavioral fit_json (keyed by vessel code)
     * @param string $candidateId  Pool candidate UUID
     * @param float $confidence  Behavioral profile confidence (0-1)
     * @return array  Enriched fit_json with derived_from + suppressed fields
     */
    public function enrich(array $fitJson, string $candidateId, float $confidence): array
    {
        $contractVesselTypes = $this->getContractVesselTypes($candidateId);
        $sourceMetaVesselTypes = $this->getSourceMetaVesselTypes($candidateId);
        $profileVesselTypes = $this->getCommandProfileVesselTypes($candidateId);

        $enriched = [];

        foreach ($fitJson as $code => $entry) {
            $derivedFrom = 'behavioral_only';

            // Check contract history first (highest evidence)
            if ($this->hasContractMatch($code, $contractVesselTypes)) {
                $derivedFrom = 'contract_history';
            }
            // Then check source_meta / command profile
            elseif ($this->hasSourceMetaMatch($code, $sourceMetaVesselTypes, $profileVesselTypes)) {
                $derivedFrom = 'source_meta';
            }

            $suppressed = false;
            if ($derivedFrom === 'behavioral_only' && $confidence < 0.40) {
                $suppressed = true;
            }

            $entry['derived_from'] = $derivedFrom;
            $entry['suppressed'] = $suppressed;
            $enriched[$code] = $entry;
        }

        return $enriched;
    }

    /**
     * Get distinct vessel types from candidate's contracts.
     */
    private function getContractVesselTypes(string $candidateId): array
    {
        return CandidateContract::where('pool_candidate_id', $candidateId)
            ->whereNotNull('vessel_type')
            ->pluck('vessel_type')
            ->map(fn($v) => strtolower(trim($v)))
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get vessel types from candidate's source_meta (apply form).
     */
    private function getSourceMetaVesselTypes(string $candidateId): array
    {
        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate || !$candidate->source_meta) {
            return [];
        }

        $types = $candidate->source_meta['vessel_types'] ?? [];
        return array_map(fn($v) => strtolower(trim($v)), $types);
    }

    /**
     * Get vessel types from CandidateCommandProfile.
     */
    private function getCommandProfileVesselTypes(string $candidateId): array
    {
        $profile = CandidateCommandProfile::where('candidate_id', $candidateId)->first();
        if (!$profile) {
            return [];
        }

        return array_map(fn($v) => strtolower(trim($v)), $profile->getVesselTypes());
    }

    /**
     * Check if a behavioral code matches any contract vessel type.
     */
    private function hasContractMatch(string $code, array $contractTypes): bool
    {
        $mappedTypes = self::CODE_TO_CONTRACT_TYPE[$code] ?? [strtolower($code)];

        foreach ($mappedTypes as $mapped) {
            foreach ($contractTypes as $ct) {
                if ($ct === $mapped || str_contains($ct, $mapped) || str_contains($mapped, $ct)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a behavioral code matches source_meta or command profile vessel types.
     */
    private function hasSourceMetaMatch(string $code, array $sourceMetaTypes, array $profileTypes): bool
    {
        $allTypes = array_merge($sourceMetaTypes, $profileTypes);
        if (empty($allTypes)) {
            return false;
        }

        $mappedTypes = self::CODE_TO_CONTRACT_TYPE[$code] ?? [strtolower($code)];

        foreach ($mappedTypes as $mapped) {
            foreach ($allTypes as $t) {
                if ($t === $mapped || str_contains($t, $mapped) || str_contains($mapped, $t)) {
                    return true;
                }
            }
        }

        return false;
    }
}
