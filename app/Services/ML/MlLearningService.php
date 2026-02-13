<?php

namespace App\Services\ML;

use App\Models\FormInterview;
use App\Models\InterviewOutcome;
use App\Models\ModelFeature;
use App\Models\ModelPrediction;
use App\Models\ModelWeight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MlLearningService - Gradient-based Weight Learning
 * 
 * Implements a learning loop inspired by gradient descent:
 * - Each outcome provides a "training signal"
 * - Weights are adjusted based on prediction error
 * - Feature contribution determines update magnitude
 * - Industry-specific and global layers supported
 */
class MlLearningService
{
    protected const LEARNING_RATE = 0.05;
    protected const WEIGHT_MIN = -25.0;
    protected const WEIGHT_MAX = 10.0;
    protected const MIN_CONFIDENCE = 0.1;
    protected const MAX_CONFIDENCE = 1.0;

    /**
     * Update weights based on a single outcome.
     * Called when InterviewOutcome is saved.
     */
    public function updateWeightsFromOutcome(InterviewOutcome $outcome): array
    {
        $interview = $outcome->formInterview;
        if (!$interview) {
            return ['success' => false, 'reason' => 'No interview'];
        }

        $feature = ModelFeature::where('form_interview_id', $interview->id)->first();
        $prediction = ModelPrediction::where('form_interview_id', $interview->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$feature || !$prediction) {
            return ['success' => false, 'reason' => 'No feature or prediction'];
        }

        // Calculate error
        $actualScore = $this->calculateOutcomeScore($outcome);
        $predictedScore = $prediction->predicted_outcome_score;
        $error = $actualScore - $predictedScore;

        // No learning if error is small
        if (abs($error) < 5) {
            return ['success' => true, 'reason' => 'Error too small, no update needed', 'error' => $error];
        }

        $industry = $feature->industry_code ?? 'general';

        // Extract feature values that contributed to prediction
        $featureValues = $this->extractFeatureValues($feature);

        // Calculate weight updates
        $weightDeltas = $this->calculateWeightDeltas($featureValues, $error);

        // Apply updates to feature importance
        $this->applyFeatureImportanceUpdates($weightDeltas, $industry, $error > 0);

        // Log learning event
        $this->logLearningEvent($interview, $feature, $prediction, $outcome, $error, $weightDeltas);

        // Check if enough samples to create new weights version
        $this->checkAndCreateNewWeights($industry);

        return [
            'success' => true,
            'interview_id' => $interview->id,
            'error' => $error,
            'weight_deltas' => $weightDeltas,
        ];
    }

    /**
     * Calculate outcome score from outcome data.
     */
    protected function calculateOutcomeScore(InterviewOutcome $outcome): int
    {
        if ($outcome->outcome_score !== null) {
            return (int) $outcome->outcome_score;
        }

        if (!$outcome->hired) return 0;
        if (!$outcome->started) return 10;
        if (!$outcome->still_employed_30d) return 30;
        if (!$outcome->still_employed_90d) return 50;
        if ($outcome->incident_flag) return 70;
        if ($outcome->performance_rating >= 4) return 100;
        return 85;
    }

    /**
     * Extract feature values from model feature.
     */
    protected function extractFeatureValues(ModelFeature $feature): array
    {
        $values = [];

        // Risk flags
        $riskFlags = $feature->risk_flags_json ?? [];
        foreach ($riskFlags as $flag) {
            $code = is_array($flag) ? ($flag['code'] ?? null) : $flag;
            if ($code) {
                $values["risk_flag:{$code}"] = 1.0;
            }
        }

        // Meta features
        $meta = $feature->answers_meta_json ?? [];
        if ($meta['rf_sparse'] ?? false) {
            $values['meta:sparse_answers'] = 1.0;
        }
        if ($meta['rf_incomplete'] ?? false) {
            $values['meta:incomplete_interview'] = 1.0;
        }

        $avgLen = $meta['avg_answer_len'] ?? 0;
        if ($avgLen > 0 && $avgLen < 30) {
            $values['meta:very_short_answers'] = 1.0;
        }

        // Source channel
        if ($feature->source_channel) {
            $values["source:{$feature->source_channel}"] = 1.0;
        }

        // Industry
        if ($feature->industry_code) {
            $values["industry:{$feature->industry_code}"] = 1.0;
        }

        // Base calibrated score (normalized)
        $values['base:calibrated_score'] = ($feature->calibrated_score ?? 50) / 100.0;

        return $values;
    }

    /**
     * Calculate weight deltas using gradient-like update.
     * 
     * For each feature:
     *   delta = learning_rate * error * feature_value
     * 
     * If error > 0: actual > predicted → we underestimated
     *   - Boost positive features (source, industry)
     *   - Reduce penalties
     * 
     * If error < 0: actual < predicted → we overestimated
     *   - Reduce boosts
     *   - Increase penalties
     */
    protected function calculateWeightDeltas(array $featureValues, int $error): array
    {
        $deltas = [];
        $normalizedError = $error / 100.0; // Normalize to [-1, 1]

        foreach ($featureValues as $featureName => $value) {
            // Determine if feature is penalty or boost type
            $isPenalty = str_starts_with($featureName, 'risk_flag:') || 
                         str_starts_with($featureName, 'meta:');

            // Calculate raw delta
            $rawDelta = self::LEARNING_RATE * $normalizedError * $value;

            // For penalties: if we underestimated (error > 0), reduce penalty
            // For boosts: if we underestimated (error > 0), increase boost
            if ($isPenalty) {
                // Penalties are negative, so positive error means reduce penalty (add to it)
                $delta = $rawDelta;
            } else {
                // Boosts are positive, positive error means increase
                $delta = $rawDelta;
            }

            // Clamp delta
            $delta = max(-2.0, min(2.0, $delta));

            if (abs($delta) > 0.01) {
                $deltas[$featureName] = round($delta, 4);
            }
        }

        return $deltas;
    }

    /**
     * Apply updates to feature importance table.
     */
    protected function applyFeatureImportanceUpdates(array $deltas, string $industry, bool $positiveImpact): void
    {
        foreach ($deltas as $featureName => $delta) {
            DB::table('model_feature_importance')->updateOrInsert(
                [
                    'feature_name' => $featureName,
                    'industry_code' => $industry,
                ],
                [
                    'positive_impact_count' => DB::raw('COALESCE(positive_impact_count, 0) + ' . ($positiveImpact ? 1 : 0)),
                    'negative_impact_count' => DB::raw('COALESCE(negative_impact_count, 0) + ' . ($positiveImpact ? 0 : 1)),
                    'current_weight' => DB::raw("COALESCE(current_weight, 0) + {$delta}"),
                    'sample_count' => DB::raw('COALESCE(sample_count, 0) + 1'),
                    'last_updated_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Log learning event.
     */
    protected function logLearningEvent(
        FormInterview $interview,
        ModelFeature $feature,
        ModelPrediction $prediction,
        InterviewOutcome $outcome,
        int $error,
        array $deltas
    ): void {
        $actualLabel = $this->calculateOutcomeScore($outcome) >= 50 ? 'GOOD' : 'BAD';

        DB::table('learning_events')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'form_interview_id' => $interview->id,
            'model_version' => $prediction->model_version,
            'industry_code' => $feature->industry_code,
            'source_channel' => $feature->source_channel,
            'predicted_score' => $prediction->predicted_outcome_score,
            'actual_score' => $this->calculateOutcomeScore($outcome),
            'error' => $error,
            'predicted_label' => $prediction->predicted_label,
            'actual_label' => $actualLabel,
            'is_false_positive' => $prediction->predicted_label === 'GOOD' && $actualLabel === 'BAD',
            'is_false_negative' => $prediction->predicted_label === 'BAD' && $actualLabel === 'GOOD',
            'feature_snapshot_json' => json_encode([
                'deltas' => $deltas,
                'risk_flags' => $feature->risk_flags_json,
                'answers_meta' => $feature->answers_meta_json,
            ]),
            'created_at' => now(),
        ]);
    }

    /**
     * Check if we have enough samples to create new weights.
     */
    protected function checkAndCreateNewWeights(string $industry): void
    {
        // Count learning events since last weight update
        $lastWeight = ModelWeight::latest();
        $lastWeightTime = $lastWeight ? $lastWeight->created_at : now()->subYear();

        $newSamples = DB::table('learning_events')
            ->where('created_at', '>', $lastWeightTime)
            ->where('industry_code', $industry)
            ->count();

        // Create new weights every 20 samples
        if ($newSamples >= 20) {
            $this->createNewWeightsFromLearning($industry);
        }
    }

    /**
     * Create new weight version from accumulated learning.
     */
    public function createNewWeightsFromLearning(?string $industry = null): ?ModelWeight
    {
        $currentWeights = ModelWeight::latest();
        if (!$currentWeights) {
            return null;
        }

        $newWeights = $currentWeights->weights_json;

        // Get accumulated feature importance
        $features = DB::table('model_feature_importance')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('sample_count', '>=', 5)
            ->get();

        $appliedChanges = [];

        foreach ($features as $f) {
            $parts = explode(':', $f->feature_name, 2);
            $type = $parts[0] ?? '';
            $code = $parts[1] ?? '';

            if (!$type || !$code) continue;

            // Calculate confidence-weighted change
            $confidence = min(1.0, $f->sample_count / 50);
            $weightChange = $f->current_weight * $confidence;

            // Apply change
            switch ($type) {
                case 'risk_flag':
                    $current = $newWeights['risk_flag_penalties'][$code] ?? -3;
                    $new = $this->clampWeight($current + $weightChange, self::WEIGHT_MIN, 0);
                    $newWeights['risk_flag_penalties'][$code] = round($new, 1);
                    $appliedChanges[$f->feature_name] = round($weightChange, 2);
                    break;

                case 'meta':
                    $key = $code;
                    $current = $newWeights['meta_penalties'][$key] ?? -5;
                    $new = $this->clampWeight($current + $weightChange, self::WEIGHT_MIN, 0);
                    $newWeights['meta_penalties'][$key] = round($new, 1);
                    $appliedChanges[$f->feature_name] = round($weightChange, 2);
                    break;

                case 'source':
                    $key = $code . '_source';
                    $current = $newWeights['boosts'][$key] ?? 0;
                    $new = $this->clampWeight($current + $weightChange, 0, self::WEIGHT_MAX);
                    $newWeights['boosts'][$key] = round($new, 1);
                    $appliedChanges[$f->feature_name] = round($weightChange, 2);
                    break;

                case 'industry':
                    $key = $code . '_industry';
                    $current = $newWeights['boosts'][$key] ?? 0;
                    $new = $this->clampWeight($current + $weightChange, 0, self::WEIGHT_MAX);
                    $newWeights['boosts'][$key] = round($new, 1);
                    $appliedChanges[$f->feature_name] = round($weightChange, 2);
                    break;
            }
        }

        if (empty($appliedChanges)) {
            return null;
        }

        // Create new version
        $version = ModelWeight::nextVersion();
        $industryNote = $industry ? " [{$industry}]" : "";

        $weight = ModelWeight::create([
            'model_version' => $version,
            'weights_json' => $newWeights,
            'notes' => sprintf(
                "Auto-learned%s on %s. %d feature changes applied.",
                $industryNote,
                now()->toDateString(),
                count($appliedChanges)
            ),
            'created_at' => now(),
        ]);

        // Log learning cycle
        DB::table('learning_cycles')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'model_version_before' => $currentWeights->model_version,
            'model_version_after' => $version,
            'industry_code' => $industry,
            'trigger' => 'auto',
            'samples_processed' => $features->sum('sample_count'),
            'weight_deltas_json' => json_encode($appliedChanges),
            'created_at' => now(),
        ]);

        // Reset accumulated weights
        DB::table('model_feature_importance')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->update([
                'current_weight' => 0,
                'sample_count' => 0,
                'updated_at' => now(),
            ]);

        Log::channel('single')->info('MlLearningService::createNewWeightsFromLearning', [
            'version' => $version,
            'industry' => $industry,
            'changes' => count($appliedChanges),
        ]);

        return $weight;
    }

    /**
     * Clamp weight to valid range.
     */
    protected function clampWeight(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /**
     * Batch learn from historical outcomes (for simulation/backfill).
     */
    public function batchLearn(int $windowDays = 90, ?string $industry = null): array
    {
        $from = now()->subDays($windowDays)->toDateString();

        $query = DB::table('interview_outcomes as io')
            ->join('form_interviews as fi', 'io.form_interview_id', '=', 'fi.id')
            ->join('model_features as mf', 'mf.form_interview_id', '=', 'fi.id')
            ->join('model_predictions as mp', 'mp.form_interview_id', '=', 'fi.id')
            ->where('io.created_at', '>=', $from)
            ->when($industry, fn($q) => $q->where('mf.industry_code', $industry));

        $outcomes = $query->select([
            'io.id as outcome_id',
            'fi.id as interview_id',
        ])->get();

        $processed = 0;
        $errors = [];

        foreach ($outcomes as $row) {
            $outcome = InterviewOutcome::find($row->outcome_id);
            if ($outcome) {
                $result = $this->updateWeightsFromOutcome($outcome);
                if ($result['success']) {
                    $processed++;
                } else {
                    $errors[] = $result['reason'];
                }
            }
        }

        // Force create new weights after batch
        $newWeights = $this->createNewWeightsFromLearning($industry);

        return [
            'processed' => $processed,
            'errors' => count($errors),
            'new_weights_version' => $newWeights ? $newWeights->model_version : null,
        ];
    }
}
