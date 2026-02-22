<?php

namespace App\Services\Maritime;

use App\Models\BehavioralProfile;
use App\Models\CandidateScoringVector;
use App\Models\CandidateTrustProfile;
use App\Models\FormInterview;
use App\Models\LanguageAssessment;
use App\Models\PoolCandidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CandidateVectorService
{
    // Default weights for composite score
    private const DEFAULT_WEIGHTS = [
        'technical'   => 0.35,
        'behavioral'  => 0.25,
        'reliability' => 0.15,
        'personality' => 0.10, // placeholder, redistributed when null
        'english'     => 0.15,
    ];

    // Dynamic English weight per vessel type
    private const VESSEL_ENGLISH_WEIGHTS = [
        'PASSENGER'      => 0.25,
        'CRUISE'         => 0.25,
        'OFFSHORE'       => 0.20,
        'TANKER'         => 0.10,
        'BULK'           => 0.10,
        'CONTAINER'      => 0.15,
        'CONTAINER_ULCS' => 0.15,
        'LNG'            => 0.15,
        'RIVER'          => 0.10,
        'COASTAL'        => 0.12,
        'SHORT_SEA'      => 0.12,
        'DEEP_SEA'       => 0.15,
    ];

    // Safety thresholds: if technical + behavioral above these, English/personality alone cannot reject
    private const SAFETY_TECHNICAL_THRESHOLD = 55.0;
    private const SAFETY_BEHAVIORAL_THRESHOLD = 50.0;
    private const SAFETY_HOLD_FLOOR = 40.0;

    public function computeVector(string $candidateId): ?CandidateScoringVector
    {
        if (!config('maritime.vector_v1')) {
            return null;
        }

        try {
            return DB::transaction(function () use ($candidateId) {
                $candidate = PoolCandidate::find($candidateId);
                if (!$candidate) {
                    return null;
                }

                // Gather signals
                $technical = $this->resolveTechnicalScore($candidate);
                $behavioral = $this->resolveBehavioralScore($candidateId);
                $reliability = $this->resolveReliabilityScore($candidateId);
                $english = $this->resolveEnglishScore($candidateId);
                $englishLevel = $this->resolveEnglishLevel($candidateId);

                // Resolve dynamic English weight from vessel context
                $englishWeight = $this->resolveEnglishWeight($candidate);

                // Build weights with personality placeholder (null in v1)
                $weights = self::DEFAULT_WEIGHTS;
                $weights['english'] = $englishWeight;

                // Redistribute personality weight (null) proportionally
                $personalityScore = null; // v1 placeholder
                $weights = $this->redistributeNullWeights($weights, [
                    'technical' => $technical,
                    'behavioral' => $behavioral,
                    'reliability' => $reliability,
                    'personality' => $personalityScore,
                    'english' => $english,
                ]);

                // Compute composite score
                $composite = $this->computeComposite($weights, [
                    'technical' => $technical,
                    'behavioral' => $behavioral,
                    'reliability' => $reliability,
                    'personality' => $personalityScore,
                    'english' => $english,
                ]);

                // Apply safety rules
                $vector = [
                    'technical' => $technical,
                    'behavioral' => $behavioral,
                    'reliability' => $reliability,
                    'personality' => $personalityScore,
                    'english_proficiency' => $english,
                    'english_level' => $englishLevel,
                    'english_weight' => $englishWeight,
                    'composite_score' => $composite,
                    'weights_used' => $weights,
                ];
                $this->applySafetyRules($vector);

                // Persist
                $sv = CandidateScoringVector::updateOrCreate(
                    ['candidate_id' => $candidateId, 'version' => 'v1'],
                    [
                        'technical_score' => $vector['technical'],
                        'behavioral_score' => $vector['behavioral'],
                        'reliability_score' => $vector['reliability'],
                        'personality_score' => $vector['personality'],
                        'english_proficiency' => $vector['english_proficiency'],
                        'english_level' => $vector['english_level'],
                        'english_weight' => $vector['english_weight'],
                        'composite_score' => $vector['composite_score'],
                        'vector_json' => $vector,
                        'computed_at' => now(),
                    ]
                );

                Log::info('CandidateVectorService::computeVector', [
                    'candidate_id' => $candidateId,
                    'composite' => $vector['composite_score'],
                    'signals' => array_filter([
                        'technical' => $technical !== null,
                        'behavioral' => $behavioral !== null,
                        'reliability' => $reliability !== null,
                        'english' => $english !== null,
                    ]),
                ]);

                return $sv;
            });
        } catch (\Throwable $e) {
            Log::warning('CandidateVectorService::computeVector failed', [
                'candidate_id' => $candidateId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function resolveTechnicalScore(PoolCandidate $candidate): ?float
    {
        $interview = $candidate->formInterviews()
            ->where('status', 'completed')
            ->where('type', 'standard')
            ->orderByDesc('completed_at')
            ->first();

        if (!$interview) {
            return null;
        }

        return $interview->calibrated_score ?? $interview->final_score;
    }

    private function resolveBehavioralScore(string $candidateId): ?float
    {
        $profile = BehavioralProfile::where('candidate_id', $candidateId)
            ->where('version', 'v1')
            ->where('status', BehavioralProfile::STATUS_FINAL)
            ->first();

        if (!$profile || !$profile->dimensions_json) {
            return null;
        }

        // Composite from 7 dimensions (weighted average, excluding CONFLICT_RISK which is inverted)
        $dims = $profile->dimensions_json;
        $scores = [];
        foreach ($dims as $dim => $data) {
            $score = $data['score'] ?? 50;
            if ($dim === 'CONFLICT_RISK') {
                // Invert: high conflict = bad, so 100 - score = behavioral contribution
                $scores[] = 100 - $score;
            } else {
                $scores[] = $score;
            }
        }

        return !empty($scores) ? round(array_sum($scores) / count($scores), 2) : null;
    }

    private function resolveReliabilityScore(string $candidateId): ?float
    {
        $trust = CandidateTrustProfile::where('pool_candidate_id', $candidateId)->first();
        if (!$trust) {
            return null;
        }

        // Use stability_score if available
        return $trust->stability_score ?? $trust->composite_trust_score ?? null;
    }

    private function resolveEnglishScore(string $candidateId): ?float
    {
        $assessment = LanguageAssessment::where('candidate_id', $candidateId)->first();
        return $assessment?->overall_score ? (float) $assessment->overall_score : null;
    }

    private function resolveEnglishLevel(string $candidateId): ?string
    {
        $assessment = LanguageAssessment::where('candidate_id', $candidateId)->first();
        return $assessment?->locked_level ?? $assessment?->estimated_level;
    }

    private function resolveEnglishWeight(PoolCandidate $candidate): float
    {
        // Try to find vessel type from latest interview or candidate context
        $interview = $candidate->formInterviews()
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->first();

        $vesselType = $interview?->command_class_detected;

        if ($vesselType && isset(self::VESSEL_ENGLISH_WEIGHTS[$vesselType])) {
            return self::VESSEL_ENGLISH_WEIGHTS[$vesselType];
        }

        return self::DEFAULT_WEIGHTS['english'];
    }

    /**
     * When a signal is null, redistribute its weight proportionally to other signals.
     */
    private function redistributeNullWeights(array $weights, array $scores): array
    {
        $nullDims = [];
        $activeDims = [];

        foreach ($scores as $dim => $score) {
            if ($score === null) {
                $nullDims[] = $dim;
            } else {
                $activeDims[] = $dim;
            }
        }

        if (empty($nullDims) || empty($activeDims)) {
            return $weights;
        }

        // Sum null weights and redistribute
        $nullWeightSum = 0;
        foreach ($nullDims as $dim) {
            $nullWeightSum += $weights[$dim] ?? 0;
        }

        $activeWeightSum = 0;
        foreach ($activeDims as $dim) {
            $activeWeightSum += $weights[$dim] ?? 0;
        }

        if ($activeWeightSum <= 0) {
            return $weights;
        }

        $redistributed = $weights;
        foreach ($nullDims as $dim) {
            $redistributed[$dim] = 0;
        }
        foreach ($activeDims as $dim) {
            $proportion = ($weights[$dim] ?? 0) / $activeWeightSum;
            $redistributed[$dim] = ($weights[$dim] ?? 0) + ($nullWeightSum * $proportion);
        }

        return $redistributed;
    }

    private function computeComposite(array $weights, array $scores): ?float
    {
        $sum = 0;
        $weightSum = 0;

        foreach ($scores as $dim => $score) {
            if ($score !== null && isset($weights[$dim]) && $weights[$dim] > 0) {
                $sum += $score * $weights[$dim];
                $weightSum += $weights[$dim];
            }
        }

        if ($weightSum <= 0) {
            return null;
        }

        return round($sum / $weightSum, 2);
    }

    /**
     * Safety rule: personality and English alone cannot trigger auto-rejection.
     * If technical + behavioral are both above threshold, composite cannot drop below HOLD floor.
     */
    private function applySafetyRules(array &$vector): void
    {
        $technical = $vector['technical'];
        $behavioral = $vector['behavioral'];
        $composite = $vector['composite_score'];

        if ($technical === null || $behavioral === null || $composite === null) {
            return;
        }

        // If both technical and behavioral are solid, enforce minimum composite
        if ($technical >= self::SAFETY_TECHNICAL_THRESHOLD && $behavioral >= self::SAFETY_BEHAVIORAL_THRESHOLD) {
            if ($composite < self::SAFETY_HOLD_FLOOR) {
                $vector['composite_score'] = self::SAFETY_HOLD_FLOOR;
                $vector['safety_rule_applied'] = 'hold_floor_enforced';
            }
        }
    }
}
