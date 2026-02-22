<?php

namespace App\Services\RankStcw;

use App\Models\CandidateContract;
use App\Models\PoolCandidate;
use App\Models\RankHierarchy;
use App\Models\SeafarerCertificate;
use App\Models\StcwRequirement;
use App\Services\Trust\RankProgressionAnalyzer;

class StcwComplianceChecker
{
    private RankProgressionAnalyzer $rankAnalyzer;

    public function __construct(RankProgressionAnalyzer $rankAnalyzer)
    {
        $this->rankAnalyzer = $rankAnalyzer;
    }

    /**
     * Check STCW compliance for a candidate's current rank and vessel type.
     *
     * Returns: [
     *   compliance_ratio   => 0.0–1.0,
     *   total_required     => int,
     *   total_held         => int (valid + not expired),
     *   missing_certs      => [{code, name}],
     *   expired_certs      => [{code, name, expired_at}],
     *   expiring_soon      => [{code, name, expires_at}],
     *   held_valid         => [code, ...],
     *   rank_code          => string (canonical),
     *   stcw_rank_code     => string,
     *   vessel_type        => string,
     * ]
     */
    public function check(string $poolCandidateId, ?string $vesselType = null): ?array
    {
        $candidate = PoolCandidate::with(['certificates'])->find($poolCandidateId);

        if (!$candidate) {
            return null;
        }

        // Determine current rank from latest contract (most recent start_date)
        $latestContract = CandidateContract::where('pool_candidate_id', $poolCandidateId)
            ->orderByDesc('start_date')
            ->first();
        if (!$latestContract || !$latestContract->rank_code) {
            return $this->emptyResult('unknown', 'unknown', $vesselType ?? 'any');
        }

        $canonical = $this->rankAnalyzer->normalizeRank($latestContract->rank_code);
        if (!$canonical) {
            return $this->emptyResult($latestContract->rank_code, 'unknown', $vesselType ?? 'any');
        }

        // Get the STCW rank code from rank_hierarchy
        $hierarchy = RankHierarchy::findByCanonical($canonical);
        $stcwRankCode = $hierarchy?->stcw_rank_code;
        if (!$stcwRankCode) {
            return $this->emptyResult($canonical, 'unknown', $vesselType ?? 'any');
        }

        // Determine vessel type from latest contract if not provided
        $effectiveVesselType = $vesselType ?? $latestContract->vessel_type ?? 'any';

        // Map CandidateContract vessel types to StcwRequirement vessel types
        $stcwVesselType = $this->mapVesselType($effectiveVesselType);

        // Get required certificate codes
        $requiredCodes = StcwRequirement::getRequiredCodes($stcwRankCode, $stcwVesselType);

        if (empty($requiredCodes)) {
            return $this->emptyResult($canonical, $stcwRankCode, $stcwVesselType);
        }

        // Get candidate's certificates
        $certificates = $candidate->certificates;
        $heldValid = [];
        $expiredCerts = [];
        $expiringSoon = [];

        foreach ($certificates as $cert) {
            $code = $cert->certificate_code ?? $cert->certificate_type;

            if ($cert->isExpired()) {
                $expiredCerts[$code] = [
                    'code' => $code,
                    'name' => $code,
                    'expired_at' => $cert->expires_at?->toDateString(),
                ];
            } elseif ($cert->isValid()) {
                $heldValid[] = $code;
                if ($cert->isExpiringSoon(90)) {
                    $expiringSoon[] = [
                        'code' => $code,
                        'name' => $code,
                        'expires_at' => $cert->expires_at?->toDateString(),
                    ];
                }
            }
        }

        $heldValidUnique = array_unique($heldValid);

        // Calculate missing certificates
        $missing = [];
        $matchedCount = 0;
        foreach ($requiredCodes as $reqCode) {
            if (in_array($reqCode, $heldValidUnique)) {
                $matchedCount++;
            } else {
                $missing[] = [
                    'code' => $reqCode,
                    'name' => $reqCode,
                    'is_expired' => isset($expiredCerts[$reqCode]),
                ];
            }
        }

        $totalRequired = count($requiredCodes);
        $complianceRatio = $totalRequired > 0
            ? round($matchedCount / $totalRequired, 4)
            : 1.0;

        // Filter expired_certs to only those that are required
        $expiredRequired = [];
        foreach ($expiredCerts as $code => $info) {
            if (in_array($code, $requiredCodes)) {
                $expiredRequired[] = $info;
            }
        }

        return [
            'compliance_ratio' => $complianceRatio,
            'total_required' => $totalRequired,
            'total_held' => $matchedCount,
            'missing_certs' => array_values($missing),
            'expired_certs' => $expiredRequired,
            'expiring_soon' => $expiringSoon,
            'held_valid' => array_values($heldValidUnique),
            'rank_code' => $canonical,
            'stcw_rank_code' => $stcwRankCode,
            'vessel_type' => $stcwVesselType,
        ];
    }

    /**
     * Map CandidateContract vessel types to StcwRequirement vessel types.
     */
    private function mapVesselType(string $contractVesselType): string
    {
        return match ($contractVesselType) {
            'tanker', 'chemical', 'lng_lpg' => 'tanker',
            'passenger' => 'passenger',
            'offshore' => 'offshore',
            'bulk_carrier', 'container', 'general_cargo', 'ro_ro', 'car_carrier' => 'cargo',
            default => 'any',
        };
    }

    private function emptyResult(string $rankCode, string $stcwRankCode, string $vesselType): array
    {
        return [
            'compliance_ratio' => 0.0,
            'total_required' => 0,
            'total_held' => 0,
            'missing_certs' => [],
            'expired_certs' => [],
            'expiring_soon' => [],
            'held_valid' => [],
            'rank_code' => $rankCode,
            'stcw_rank_code' => $stcwRankCode,
            'vessel_type' => $vesselType,
        ];
    }
}
