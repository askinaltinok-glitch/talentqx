<?php

namespace App\Services\ML;

use App\Models\FormInterview;
use App\Models\ModelFeature;
use App\Models\ModelPrediction;
use App\Models\ModelWeight;
use App\Models\PoolCandidate;

class MlScoringService
{
    protected ?array $weights = null;
    protected string $modelVersion = 'ml_v0';

    /**
     * Predict outcome score and store prediction.
     */
    public function predictAndStore(FormInterview $interview): ?ModelPrediction
    {
        // Get or create feature
        $feature = ModelFeature::where('form_interview_id', $interview->id)->first();
        if (!$feature) {
            return null;
        }

        // Load weights
        $this->loadWeights();

        // Calculate prediction
        $result = $this->calculatePrediction($interview, $feature);

        // Store prediction
        return ModelPrediction::updateOrCreate(
            [
                'form_interview_id' => $interview->id,
                'model_version' => $this->modelVersion,
            ],
            [
                'predicted_outcome_score' => $result['score'],
                'predicted_label' => $result['label'],
                'explain_json' => $result['explain'],
                'created_at' => now(),
            ]
        );
    }

    /**
     * Calculate prediction using v0 formula.
     *
     * Formula:
     *   base = calibrated_score (or raw_final_score)
     *   penalties = risk_flags + structural meta
     *   boosts = source channel + industry + english level + video
     *   output = clamp(base + penalties + boosts, 0, 100)
     *
     * @param FormInterview $interview
     * @param ModelFeature $feature
     * @param ModelWeight|null $weights Optional weights to use (for re-predictions)
     * @return array{predicted_score: int, predicted_label: string, explain: array}
     */
    public function calculatePrediction(FormInterview $interview, ModelFeature $feature, ?ModelWeight $weights = null): array
    {
        // Use provided weights or load from database
        if ($weights) {
            $weightsData = $weights->weights_json;
            $modelVersion = $weights->model_version;
        } else {
            $this->loadWeights();
            $weightsData = $this->weights;
            $modelVersion = $this->modelVersion;
        }

        $explain = [];
        $explain['model_version'] = $modelVersion;

        // Base score
        $base = $feature->calibrated_score ?? $feature->raw_final_score ?? 50;
        $explain['base_score'] = $base;

        // Calculate penalties from risk flags
        $riskPenalty = 0;
        $riskFlags = $feature->risk_flags_json ?? [];
        $flagPenalties = $weightsData['risk_flag_penalties'] ?? [];

        foreach ($riskFlags as $flag) {
            $code = is_array($flag) ? ($flag['code'] ?? null) : $flag;
            if ($code) {
                $penalty = $flagPenalties[$code] ?? $flagPenalties['default'] ?? -3;
                $riskPenalty += $penalty;
                $explain['risk_flags'][$code] = $penalty;
            }
        }
        $explain['total_risk_penalty'] = $riskPenalty;

        // Calculate meta penalties
        $metaPenalty = 0;
        $answersMeta = $feature->answers_meta_json ?? [];
        $metaPenalties = $weightsData['meta_penalties'] ?? [];

        if ($answersMeta['rf_sparse'] ?? false) {
            $penalty = $metaPenalties['sparse_answers'] ?? -5;
            $metaPenalty += $penalty;
            $explain['meta_penalties']['sparse_answers'] = $penalty;
        }

        if ($answersMeta['rf_incomplete'] ?? false) {
            $penalty = $metaPenalties['incomplete_interview'] ?? -10;
            $metaPenalty += $penalty;
            $explain['meta_penalties']['incomplete_interview'] = $penalty;
        }

        $avgLen = $answersMeta['avg_answer_len'] ?? 0;
        if ($avgLen > 0 && $avgLen < 30) {
            $penalty = $metaPenalties['very_short_answers'] ?? -3;
            $metaPenalty += $penalty;
            $explain['meta_penalties']['very_short_answers'] = $penalty;
        }
        $explain['total_meta_penalty'] = $metaPenalty;

        // Calculate boosts
        $boost = 0;
        $boosts = $weightsData['boosts'] ?? [];
        $assessmentWeights = $weightsData['assessment_weights'] ?? [];

        // Industry boost (maritime)
        if (($feature->industry_code ?? '') === 'maritime') {
            $b = $boosts['maritime_industry'] ?? 3;
            $boost += $b;
            $explain['boosts']['maritime_industry'] = $b;
        }

        // Source channel boost (referral)
        if (in_array($feature->source_channel, ['referral', 'company_invite'])) {
            $b = $boosts['referral_source'] ?? 2;
            $boost += $b;
            $explain['boosts']['referral_source'] = $b;
        }

        // English score boost (from ModelFeature - for post-assessment predictions)
        $englishScore = $feature->english_score ?? $interview->english_assessment_score;
        if ($englishScore !== null && $englishScore >= 70) {
            $b = $assessmentWeights['english_score'] ?? $boosts['english_b2_plus'] ?? 2;
            $boost += $b;
            $explain['boosts']['english_b2_plus'] = $b;
            $explain['english_score'] = $englishScore;
        }

        // Video presence boost (maritime-specific)
        if ($feature->video_present) {
            $b = $assessmentWeights['video_present'] ?? $boosts['video_present'] ?? 1;
            $boost += $b;
            $explain['boosts']['video_present'] = $b;
        }

        $explain['total_boost'] = $boost;

        // Final calculation
        $finalScore = $base + $riskPenalty + $metaPenalty + $boost;
        $finalScore = max(0, min(100, $finalScore));

        $explain['final_calculation'] = "{$base} + ({$riskPenalty}) + ({$metaPenalty}) + {$boost} = {$finalScore}";

        // Determine label
        $threshold = $weightsData['thresholds']['good'] ?? 50;
        $label = $finalScore >= $threshold ? ModelPrediction::LABEL_GOOD : ModelPrediction::LABEL_BAD;

        return [
            'predicted_score' => (int) round($finalScore),
            'predicted_label' => $label,
            'explain' => $explain,
            // Keep old keys for backward compatibility
            'score' => (int) round($finalScore),
            'label' => $label,
        ];
    }

    /**
     * Load weights from database.
     */
    protected function loadWeights(): void
    {
        if ($this->weights !== null) {
            return;
        }

        $weightModel = ModelWeight::latest();
        if ($weightModel) {
            $this->weights = $weightModel->weights_json;
            $this->modelVersion = $weightModel->model_version;
        } else {
            // Fallback defaults
            $this->weights = [
                'risk_flag_penalties' => ['default' => -3],
                'meta_penalties' => [
                    'sparse_answers' => -5,
                    'very_short_answers' => -3,
                    'incomplete_interview' => -10,
                ],
                'boosts' => [
                    'maritime_industry' => 3,
                    'referral_source' => 2,
                    'english_b2_plus' => 2,
                ],
                'thresholds' => ['good' => 50],
            ];
            $this->modelVersion = 'ml_v0_fallback';
        }
    }

    /**
     * Get current model version.
     */
    public function getModelVersion(): string
    {
        $this->loadWeights();
        return $this->modelVersion;
    }

    /**
     * Batch predict for existing interviews.
     */
    public function backfillPredictions(int $limit = 1000): int
    {
        $features = ModelFeature::whereDoesntHave('predictions', function ($q) {
                $q->where('model_version', $this->getModelVersion());
            })
            ->with('formInterview')
            ->limit($limit)
            ->get();

        $processed = 0;
        foreach ($features as $feature) {
            if ($feature->formInterview) {
                $this->predictAndStore($feature->formInterview);
                $processed++;
            }
        }

        return $processed;
    }
}
