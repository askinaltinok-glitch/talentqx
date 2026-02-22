<?php

namespace App\Services\Maritime;

use App\Models\CandidateCommandProfile;
use App\Models\CommandClass;
use App\Models\CommandDetectionLog;
use Illuminate\Support\Facades\Log;

class CommandClassDetector
{
    /**
     * Detection weights per dimension.
     * Sum = 1.0
     */
    private const WEIGHTS = [
        'vessel_type'  => 0.30,
        'dwt_range'    => 0.20,
        'trading_area' => 0.15,
        'cargo_type'   => 0.15,
        'automation'   => 0.10,
        'crew_scale'   => 0.10,
    ];

    /**
     * Minimum confidence to consider a class match valid.
     */
    private const MIN_CONFIDENCE = 15.0;

    /**
     * Threshold for secondary class inclusion.
     */
    private const SECONDARY_THRESHOLD = 40.0;

    /**
     * Threshold for multi-class flag (primary - secondary < this).
     */
    private const MULTI_CLASS_GAP = 15.0;

    /**
     * Run detection against all active command classes.
     *
     * @return array{
     *   command_class: string,
     *   confidence: float,
     *   alternative_classes: array,
     *   scoring_breakdown: array
     * }
     */
    public function detect(CandidateCommandProfile $profile): array
    {
        $classes = CommandClass::allActive();

        if ($classes->isEmpty()) {
            Log::error('CommandClassDetector: No active command classes found');
            return [
                'command_class' => null,
                'confidence' => 0,
                'alternative_classes' => [],
                'scoring_breakdown' => [],
            ];
        }

        $scores = [];
        $breakdowns = [];

        foreach ($classes as $class) {
            $breakdown = $this->scoreAgainstClass($profile, $class);
            $total = $this->sumBreakdown($breakdown);
            $scores[$class->code] = round($total, 2);
            $breakdowns[$class->code] = $breakdown;
        }

        // Sort descending
        arsort($scores);
        $sorted = array_keys($scores);

        $primaryCode = $sorted[0];
        $primaryScore = $scores[$primaryCode];

        // Build alternatives (all classes above threshold, excluding primary)
        $alternatives = [];
        foreach ($sorted as $idx => $code) {
            if ($idx === 0) continue;
            if ($scores[$code] >= self::MIN_CONFIDENCE) {
                $alternatives[] = [
                    'command_class' => $code,
                    'score' => $scores[$code],
                ];
            }
        }

        // Multi-class flags
        $multiClassFlags = [];
        $secondaryCode = $sorted[1] ?? null;
        $secondaryScore = $secondaryCode ? $scores[$secondaryCode] : 0;

        if (
            $secondaryScore >= self::SECONDARY_THRESHOLD
            && ($primaryScore - $secondaryScore) < self::MULTI_CLASS_GAP
        ) {
            $multiClassFlags[] = [
                'type' => 'MULTI_CLASS',
                'primary' => $primaryCode,
                'secondary' => $secondaryCode,
                'gap' => round($primaryScore - $secondaryScore, 2),
            ];
        }

        if ($primaryScore < self::SECONDARY_THRESHOLD) {
            $multiClassFlags[] = [
                'type' => 'LOW_CONFIDENCE',
                'score' => $primaryScore,
            ];
        }

        // Persist to profile
        $profile->update([
            'derived_command_class' => $primaryCode,
            'confidence_score' => $primaryScore,
            'multi_class_flags' => !empty($multiClassFlags) ? $multiClassFlags : null,
        ]);

        // Audit log — every detection stored
        $this->log($profile, $scores, $breakdowns, $primaryCode, $primaryScore);

        $result = [
            'command_class' => $primaryCode,
            'confidence' => $primaryScore,
            'alternative_classes' => $alternatives,
            'scoring_breakdown' => $breakdowns,
        ];

        Log::info('CommandClassDetector: detection complete', [
            'candidate_id' => $profile->candidate_id,
            'detected' => $primaryCode,
            'confidence' => $primaryScore,
            'alternatives_count' => count($alternatives),
        ]);

        return $result;
    }

    /**
     * Score a profile against a single command class.
     * Returns breakdown per dimension.
     */
    private function scoreAgainstClass(
        CandidateCommandProfile $profile,
        CommandClass $class
    ): array {
        return [
            'vessel_type' => [
                'weight' => self::WEIGHTS['vessel_type'],
                'overlap' => $this->vesselTypeOverlap($profile, $class),
                'score' => round(
                    $this->vesselTypeOverlap($profile, $class) * self::WEIGHTS['vessel_type'] * 100,
                    2
                ),
            ],
            'dwt_range' => [
                'weight' => self::WEIGHTS['dwt_range'],
                'overlap' => $this->dwtRangeOverlap($profile, $class),
                'score' => round(
                    $this->dwtRangeOverlap($profile, $class) * self::WEIGHTS['dwt_range'] * 100,
                    2
                ),
            ],
            'trading_area' => [
                'weight' => self::WEIGHTS['trading_area'],
                'overlap' => $this->tradingAreaOverlap($profile, $class),
                'score' => round(
                    $this->tradingAreaOverlap($profile, $class) * self::WEIGHTS['trading_area'] * 100,
                    2
                ),
            ],
            'cargo_type' => [
                'weight' => self::WEIGHTS['cargo_type'],
                'overlap' => $this->cargoTypeOverlap($profile, $class),
                'score' => round(
                    $this->cargoTypeOverlap($profile, $class) * self::WEIGHTS['cargo_type'] * 100,
                    2
                ),
            ],
            'automation' => [
                'weight' => self::WEIGHTS['automation'],
                'overlap' => $this->automationOverlap($profile, $class),
                'score' => round(
                    $this->automationOverlap($profile, $class) * self::WEIGHTS['automation'] * 100,
                    2
                ),
            ],
            'crew_scale' => [
                'weight' => self::WEIGHTS['crew_scale'],
                'overlap' => $this->crewScaleOverlap($profile, $class),
                'score' => round(
                    $this->crewScaleOverlap($profile, $class) * self::WEIGHTS['crew_scale'] * 100,
                    2
                ),
            ],
        ];
    }

    /**
     * Vessel type set overlap: |profile ∩ class| / |class|
     */
    private function vesselTypeOverlap(CandidateCommandProfile $profile, CommandClass $class): float
    {
        $candidateTypes = $profile->getVesselTypes();
        $classTypes = $class->vessel_types;

        if (empty($classTypes)) return 0.0;
        if (empty($candidateTypes)) return 0.0;

        $intersection = array_intersect($candidateTypes, $classTypes);
        return count($intersection) / count($classTypes);
    }

    /**
     * DWT range overlap ratio.
     */
    private function dwtRangeOverlap(CandidateCommandProfile $profile, CommandClass $class): float
    {
        $cMin = $profile->getDwtMin();
        $cMax = $profile->getDwtMax();

        if ($cMin === null || $cMax === null) return 0.0;

        return $this->rangeOverlapRatio($cMin, $cMax, $class->dwt_min, $class->dwt_max);
    }

    /**
     * Trading area set overlap.
     */
    private function tradingAreaOverlap(CandidateCommandProfile $profile, CommandClass $class): float
    {
        $candidateAreas = $profile->trading_areas ?? [];
        $classAreas = $class->trading_areas;

        if (empty($classAreas)) return 0.0;
        if (empty($candidateAreas)) return 0.0;

        $intersection = array_intersect($candidateAreas, $classAreas);
        return count($intersection) / count($classAreas);
    }

    /**
     * Cargo type set overlap.
     */
    private function cargoTypeOverlap(CandidateCommandProfile $profile, CommandClass $class): float
    {
        $candidateCargo = $profile->cargo_history ?? [];
        $classCargo = $class->cargo_types;

        if (empty($classCargo)) return 0.0;
        if (empty($candidateCargo)) return 0.0;

        $intersection = array_intersect($candidateCargo, $classCargo);
        return count($intersection) / count($classCargo);
    }

    /**
     * Automation level overlap.
     * Checks if candidate's automation levels intersect with class requirements.
     */
    private function automationOverlap(CandidateCommandProfile $profile, CommandClass $class): float
    {
        $candidateLevels = $profile->getAutomationLevels();
        $classLevels = $class->automation_levels;

        if (empty($classLevels)) return 0.0;
        if (empty($candidateLevels)) return 0.0;

        $intersection = array_intersect($candidateLevels, $classLevels);
        return count($intersection) / count($classLevels);
    }

    /**
     * Crew scale range overlap.
     */
    private function crewScaleOverlap(CandidateCommandProfile $profile, CommandClass $class): float
    {
        $cMin = $profile->getCrewMin();
        $cMax = $profile->getCrewMax();

        if ($cMin === null || $cMax === null) return 0.0;

        return $this->rangeOverlapRatio($cMin, $cMax, $class->crew_min, $class->crew_max);
    }

    /**
     * Calculate range overlap ratio: overlap_length / class_range_length.
     * Returns 0.0 - 1.0
     */
    private function rangeOverlapRatio(int $cMin, int $cMax, int $classMin, int $classMax): float
    {
        $classRange = $classMax - $classMin;
        if ($classRange <= 0) return 0.0;

        $overlapMin = max($cMin, $classMin);
        $overlapMax = min($cMax, $classMax);

        if ($overlapMin >= $overlapMax) return 0.0;

        return ($overlapMax - $overlapMin) / $classRange;
    }

    /**
     * Sum all dimension scores from a breakdown.
     */
    private function sumBreakdown(array $breakdown): float
    {
        $total = 0.0;
        foreach ($breakdown as $dim) {
            $total += $dim['score'];
        }
        return $total;
    }

    /**
     * Store audit log entry.
     */
    private function log(
        CandidateCommandProfile $profile,
        array $scores,
        array $breakdowns,
        string $detectedClass,
        float $confidence
    ): void {
        CommandDetectionLog::create([
            'candidate_id' => $profile->candidate_id,
            'profile_snapshot' => [
                'vessel_types' => $profile->getVesselTypes(),
                'dwt_min' => $profile->getDwtMin(),
                'dwt_max' => $profile->getDwtMax(),
                'trading_areas' => $profile->trading_areas,
                'cargo_history' => $profile->cargo_history,
                'automation_levels' => $profile->getAutomationLevels(),
                'crew_min' => $profile->getCrewMin(),
                'crew_max' => $profile->getCrewMax(),
            ],
            'scoring_output' => [
                'scores' => $scores,
                'breakdowns' => $breakdowns,
            ],
            'detected_class' => $detectedClass,
            'confidence' => $confidence,
        ]);
    }
}
