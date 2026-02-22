<?php

namespace App\Services\Ais;

use App\Models\CandidateContract;
use App\Models\ContractAisVerification;
use App\Models\Vessel;
use App\Services\Ais\Dto\TrackResultDto;

class ConfidenceScorer
{
    private const WEIGHT_DATA_QUALITY     = 0.35;
    private const WEIGHT_DAYS_COVERAGE    = 0.25;
    private const WEIGHT_VESSEL_TYPE      = 0.15;
    private const WEIGHT_PERIOD_OVERLAP   = 0.15;
    private const WEIGHT_STATIC_DATA      = 0.10;

    private const THRESHOLD_VERIFIED = 0.60;

    public function score(TrackResultDto $track, CandidateContract $contract, ?Vessel $vessel): ConfidenceResult
    {
        $reasons = [];
        $anomalies = [];
        $weightedSum = 0.0;

        $contractDays = max((int) $contract->start_date->diffInDays($contract->end_date), 1);

        // 1. Data quality (0.35)
        $dqScore = $track->dataQuality;
        $weightedSum += $dqScore * self::WEIGHT_DATA_QUALITY;
        $reasons[] = [
            'code' => 'DATA_QUALITY',
            'weight' => round($dqScore * self::WEIGHT_DATA_QUALITY, 3),
            'detail' => sprintf('Data quality: %.0f%%', $dqScore * 100),
        ];

        // 2. Days coverage (0.25)
        $dcScore = min($track->daysCovered / $contractDays, 1.0);
        $weightedSum += $dcScore * self::WEIGHT_DAYS_COVERAGE;
        $reasons[] = [
            'code' => 'DAYS_COVERAGE',
            'weight' => round($dcScore * self::WEIGHT_DAYS_COVERAGE, 3),
            'detail' => sprintf('%d of %d days covered (%.0f%%)', $track->daysCovered, $contractDays, $dcScore * 100),
        ];

        if ($track->daysCovered < $contractDays * 0.3) {
            $anomalies[] = [
                'type' => 'LOW_COVERAGE',
                'detail' => sprintf('Only %d of %d days covered (%.0f%%)', $track->daysCovered, $contractDays, $dcScore * 100),
                'severity' => 'warning',
            ];
        }

        // 3. Vessel type match (0.15)
        $vtScore = $this->scoreVesselType($contract, $vessel);
        $weightedSum += $vtScore * self::WEIGHT_VESSEL_TYPE;
        $reasons[] = [
            'code' => 'VESSEL_TYPE_MATCH',
            'weight' => round($vtScore * self::WEIGHT_VESSEL_TYPE, 3),
            'detail' => $vtScore >= 1.0
                ? 'Vessel type matches contract'
                : ($vtScore >= 0.5 ? 'Close vessel type match' : 'Vessel type mismatch or unknown'),
        ];

        if ($vtScore === 0.0 && $vessel && $vessel->vessel_type_normalized && $contract->vessel_type) {
            $anomalies[] = [
                'type' => 'TYPE_MISMATCH',
                'detail' => sprintf('Vessel type "%s" does not match contract type "%s"', $vessel->vessel_type_normalized, $contract->vessel_type),
                'severity' => 'warning',
            ];
        }

        // 4. Period overlap (0.15)
        $poScore = $this->scorePeriodOverlap($track, $contract);
        $weightedSum += $poScore * self::WEIGHT_PERIOD_OVERLAP;
        $reasons[] = [
            'code' => 'PERIOD_OVERLAP',
            'weight' => round($poScore * self::WEIGHT_PERIOD_OVERLAP, 3),
            'detail' => $poScore >= 1.0
                ? 'Track period covers contract dates'
                : sprintf('Partial period overlap (%.0f%%)', $poScore * 100),
        ];

        if ($poScore === 0.0 && $track->firstSeen && $track->lastSeen) {
            $anomalies[] = [
                'type' => 'PERIOD_GAP',
                'detail' => sprintf('Track dates (%s to %s) do not overlap contract period (%s to %s)',
                    $track->firstSeen->toDateString(),
                    $track->lastSeen->toDateString(),
                    $contract->start_date->toDateString(),
                    $contract->end_date->toDateString(),
                ),
                'severity' => 'error',
            ];
        }

        // 5. Static data available (0.10)
        $sdScore = $this->scoreStaticData($vessel);
        $weightedSum += $sdScore * self::WEIGHT_STATIC_DATA;
        $reasons[] = [
            'code' => 'STATIC_DATA',
            'weight' => round($sdScore * self::WEIGHT_STATIC_DATA, 3),
            'detail' => $sdScore >= 1.0
                ? 'Full vessel static data available'
                : ($sdScore >= 0.5 ? 'Partial vessel static data' : 'No vessel static data'),
        ];

        $finalScore = round($weightedSum, 2);
        $status = $finalScore >= self::THRESHOLD_VERIFIED
            ? ContractAisVerification::STATUS_VERIFIED
            : ContractAisVerification::STATUS_FAILED;

        return new ConfidenceResult(
            score: $finalScore,
            reasons: $reasons,
            anomalies: $anomalies,
            status: $status,
        );
    }

    private function scoreVesselType(CandidateContract $contract, ?Vessel $vessel): float
    {
        if (!$vessel || !$vessel->vessel_type_normalized || !$contract->vessel_type) {
            return 0.0;
        }

        if ($vessel->vessel_type_normalized === $contract->vessel_type) {
            return 1.0;
        }

        if (VesselTypeNormalizer::isCloseMatch($vessel->vessel_type_normalized, $contract->vessel_type)) {
            return 0.5;
        }

        return 0.0;
    }

    private function scorePeriodOverlap(TrackResultDto $track, CandidateContract $contract): float
    {
        if (!$track->firstSeen || !$track->lastSeen) {
            return 0.0;
        }

        $graceDays = 7;
        $startOk = $track->firstSeen->lte($contract->start_date->copy()->addDays($graceDays));
        $endOk = $track->lastSeen->gte($contract->end_date->copy()->subDays($graceDays));

        if ($startOk && $endOk) {
            return 1.0;
        }

        // Partial: calculate overlap ratio
        $overlapStart = max($track->firstSeen->timestamp, $contract->start_date->timestamp);
        $overlapEnd = min($track->lastSeen->timestamp, $contract->end_date->timestamp);
        $contractSpan = max($contract->end_date->timestamp - $contract->start_date->timestamp, 1);

        if ($overlapEnd <= $overlapStart) {
            return 0.0;
        }

        return round(($overlapEnd - $overlapStart) / $contractSpan, 2);
    }

    private function scoreStaticData(?Vessel $vessel): float
    {
        if (!$vessel) {
            return 0.0;
        }

        $fields = 0;
        $total = 3;

        if ($vessel->name && $vessel->name !== 'Unknown') {
            $fields++;
        }
        if ($vessel->flag) {
            $fields++;
        }
        if ($vessel->type || $vessel->vessel_type_normalized) {
            $fields++;
        }

        if ($fields === $total) {
            return 1.0;
        }
        if ($fields > 0) {
            return 0.5;
        }

        return 0.0;
    }
}
