<?php

namespace App\Services\ML;

use App\Models\FormInterview;
use App\Models\InterviewOutcome;
use App\Models\ModelFeature;
use App\Models\ModelPrediction;
use App\Models\ModelWeight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MlLearningService - Gradient-based Weight Learning
 *
 * Implements a learning loop inspired by gradient descent:
 * - Each outcome provides a "training signal"
 * - Weights are adjusted based on prediction error
 * - Feature contribution determines update magnitude
 * - Industry-specific and global layers supported
 *
 * Safety features:
 * - Warmup period before applying weights globally
 * - Delta clamping to prevent large swings
 * - Unstable feature detection
 * - All changes are auditable and rollbackable
 */
class MlLearningService
{
    /**
     * Update weights based on a single outcome.
     * Called when InterviewOutcome is saved.
     */
    public function updateWeightsFromOutcome(InterviewOutcome $outcome): array
    {
        $interview = $outcome->formInterview;
        if (!$interview) {
            $this->logSkippedEvent($outcome->form_interview_id, 'skipped_no_interview');
            return ['success' => false, 'reason' => 'No interview', 'status' => 'skipped_no_interview'];
        }

        // Skip demo candidates — their synthetic data must not influence ML weights
        if ($outcome->is_demo || $interview->is_demo) {
            return ['success' => false, 'reason' => 'Demo candidate skipped', 'status' => 'skipped_demo'];
        }

        $feature = ModelFeature::where('form_interview_id', $interview->id)->first();

        // Get latest prediction (prefer post_assessment type if exists)
        $prediction = ModelPrediction::where('form_interview_id', $interview->id)
            ->orderByRaw("CASE WHEN prediction_type = 'post_assessment' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->first();

        // Log skipped events with proper status
        if (!$prediction) {
            $this->logSkippedEvent($interview->id, 'skipped_missing_prediction', $feature);
            return ['success' => false, 'reason' => 'No feature or prediction', 'status' => 'skipped_missing_prediction'];
        }

        if (!$feature) {
            $this->logSkippedEvent($interview->id, 'skipped_missing_features', null, $prediction);
            return ['success' => false, 'reason' => 'No feature or prediction', 'status' => 'skipped_missing_features'];
        }

        // Calculate error
        $actualScore = $this->calculateOutcomeScore($outcome);
        $predictedScore = $prediction->predicted_outcome_score;
        $error = $actualScore - $predictedScore;

        $minErrorThreshold = config('ml.min_error_threshold', 5);

        // No learning if error is small
        if (abs($error) < $minErrorThreshold) {
            return [
                'success' => true,
                'reason' => 'Error too small, no update needed',
                'error' => $error,
                'status' => 'skipped_small_error',
            ];
        }

        $industry = $feature->industry_code ?? 'general';

        // Extract feature values that contributed to prediction
        $featureValues = $this->extractFeatureValues($feature);

        // Calculate weight updates
        $weightDeltas = $this->calculateWeightDeltas($featureValues, $error);

        // Check for unstable features
        $unstableFeatures = $this->detectUnstableFeatures($weightDeltas);

        // Check warmup status
        $warmupStatus = $this->checkWarmupStatus($industry);

        // Determine if we should apply updates
        $shouldApply = $warmupStatus['ready'] && empty($unstableFeatures);
        $status = $this->determineStatus($warmupStatus, $unstableFeatures);

        if ($shouldApply) {
            // Apply updates to feature importance
            $this->applyFeatureImportanceUpdates($weightDeltas, $industry, $error > 0);
        }

        // Log learning event with status
        $this->logLearningEvent(
            $interview,
            $feature,
            $prediction,
            $outcome,
            $error,
            $weightDeltas,
            $status,
            $unstableFeatures
        );

        // Check if enough samples to create new weights version
        if ($shouldApply) {
            $this->checkAndCreateNewWeights($industry);
        }

        return [
            'success' => true,
            'interview_id' => $interview->id,
            'error' => $error,
            'weight_deltas' => $weightDeltas,
            'status' => $status,
            'warmup' => $warmupStatus,
            'unstable_features' => $unstableFeatures,
        ];
    }

    /**
     * Log a skipped learning event.
     */
    protected function logSkippedEvent(
        string $interviewId,
        string $status,
        ?ModelFeature $feature = null,
        ?ModelPrediction $prediction = null
    ): void {
        DB::table('learning_events')->insert([
            'id' => Str::uuid()->toString(),
            'form_interview_id' => $interviewId,
            'model_version' => $prediction->model_version ?? 'unknown',
            'industry_code' => $feature->industry_code ?? null,
            'source_channel' => $feature->source_channel ?? null,
            'predicted_score' => $prediction->predicted_outcome_score ?? 0,
            'actual_score' => 0,
            'error' => 0,
            'predicted_label' => $prediction->predicted_label ?? 'UNKNOWN',
            'actual_label' => 'UNKNOWN',
            'is_false_positive' => false,
            'is_false_negative' => false,
            'status' => $status,
            'feature_snapshot_json' => json_encode(['status' => $status]),
            'created_at' => now(),
        ]);
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

        // English assessment (maritime)
        if ($feature->english_score !== null) {
            $values['assessment:english_score'] = $feature->english_score / 100.0;
        }

        // Video presence
        if ($feature->video_present) {
            $values['assessment:video_present'] = 1.0;
        }

        return $values;
    }

    /**
     * Calculate weight deltas using gradient-like update.
     */
    protected function calculateWeightDeltas(array $featureValues, int $error): array
    {
        $deltas = [];
        $learningRate = config('ml.learning_rate', 0.02);
        $deltaMin = config('ml.delta_min', -2.5);
        $deltaMax = config('ml.delta_max', 2.5);

        $normalizedError = $error / 100.0; // Normalize to [-1, 1]

        foreach ($featureValues as $featureName => $value) {
            // Calculate raw delta
            $rawDelta = $learningRate * $normalizedError * $value;

            // Clamp delta
            $delta = max($deltaMin, min($deltaMax, $rawDelta));

            if (abs($delta) > 0.001) {
                $deltas[$featureName] = round($delta, 4);
            }
        }

        return $deltas;
    }

    /**
     * Detect features with unstable (too large) deltas.
     */
    protected function detectUnstableFeatures(array $deltas): array
    {
        $threshold = config('ml.unstable_feature_threshold', 0.15);
        $unstable = [];

        foreach ($deltas as $featureName => $delta) {
            if (abs($delta) > $threshold) {
                $unstable[$featureName] = [
                    'delta' => $delta,
                    'threshold' => $threshold,
                ];
            }
        }

        return $unstable;
    }

    /**
     * Check warmup status for an industry.
     */
    protected function checkWarmupStatus(string $industry): array
    {
        $minSamples = config('ml.warmup_min_samples', 50);

        $currentSamples = DB::table('learning_events')
            ->where('industry_code', $industry)
            ->where('status', 'applied')
            ->count();

        $totalSamples = DB::table('learning_events')
            ->where('industry_code', $industry)
            ->count();

        return [
            'ready' => $totalSamples >= $minSamples,
            'current_samples' => $totalSamples,
            'min_samples' => $minSamples,
            'progress_pct' => min(100, round(($totalSamples / $minSamples) * 100, 1)),
        ];
    }

    /**
     * Determine the status based on warmup and stability.
     */
    protected function determineStatus(array $warmupStatus, array $unstableFeatures): string
    {
        if (!$warmupStatus['ready']) {
            return 'warmup_only';
        }

        if (!empty($unstableFeatures)) {
            return 'skipped_unstable_features';
        }

        return 'applied';
    }

    /**
     * Apply updates to feature importance table.
     */
    protected function applyFeatureImportanceUpdates(array $deltas, string $industry, bool $positiveImpact): void
    {
        $maxDelta = config('ml.max_delta_per_update', 0.15);

        foreach ($deltas as $featureName => $delta) {
            // Additional safety clamp at application time
            $safeDelta = max(-$maxDelta, min($maxDelta, $delta));

            DB::table('model_feature_importance')->updateOrInsert(
                [
                    'feature_name' => $featureName,
                    'industry_code' => $industry,
                ],
                [
                    'positive_impact_count' => DB::raw('COALESCE(positive_impact_count, 0) + ' . ($positiveImpact ? 1 : 0)),
                    'negative_impact_count' => DB::raw('COALESCE(negative_impact_count, 0) + ' . ($positiveImpact ? 0 : 1)),
                    'current_weight' => DB::raw("COALESCE(current_weight, 0) + {$safeDelta}"),
                    'sample_count' => DB::raw('COALESCE(sample_count, 0) + 1'),
                    'last_updated_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Log learning event with status.
     */
    protected function logLearningEvent(
        FormInterview $interview,
        ModelFeature $feature,
        ModelPrediction $prediction,
        InterviewOutcome $outcome,
        int $error,
        array $deltas,
        string $status = 'applied',
        array $unstableFeatures = []
    ): void {
        $actualLabel = $this->calculateOutcomeScore($outcome) >= 50 ? 'GOOD' : 'BAD';

        DB::table('learning_events')->insert([
            'id' => Str::uuid()->toString(),
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
            'status' => $status,
            'feature_snapshot_json' => json_encode([
                'deltas' => $deltas,
                'risk_flags' => $feature->risk_flags_json,
                'answers_meta' => $feature->answers_meta_json,
                'unstable_features' => $unstableFeatures,
                'prediction_type' => $prediction->prediction_type,
            ]),
            'created_at' => now(),
        ]);
    }

    /**
     * Check if we have enough samples to create new weights.
     */
    protected function checkAndCreateNewWeights(string $industry): void
    {
        $threshold = config('ml.auto_weight_version_threshold', 20);

        $lastWeight = ModelWeight::where('is_active', true)->first();
        $lastWeightTime = $lastWeight ? $lastWeight->created_at : now()->subYear();

        $newSamples = DB::table('learning_events')
            ->where('created_at', '>', $lastWeightTime)
            ->where('industry_code', $industry)
            ->where('status', 'applied')
            ->count();

        if ($newSamples >= $threshold) {
            $this->createNewWeightsFromLearning($industry);
        }
    }

    /**
     * Create new weight version from accumulated learning.
     */
    public function createNewWeightsFromLearning(?string $industry = null): ?ModelWeight
    {
        $currentWeights = ModelWeight::where('is_active', true)->first();
        if (!$currentWeights) {
            $currentWeights = ModelWeight::orderByDesc('created_at')->first();
        }

        if (!$currentWeights) {
            return null;
        }

        // Frozen guard: if active weights are frozen, skip auto-update
        if ($currentWeights->is_frozen && !config('ml.allow_auto_update_when_frozen', false)) {
            Log::channel('single')->info('MlLearningService: Weights frozen, skipping auto-update', [
                'version' => $currentWeights->model_version,
            ]);
            \App\Services\System\SystemEventService::log('ml_frozen_skip', 'info', 'MlLearningService', 'Auto-update skipped: active weights are frozen', [
                'version' => $currentWeights->model_version,
            ]);
            return null;
        }

        // Canary mode: only apply to canary industry
        if (config('ml.canary_mode_enabled', false) && $industry !== config('ml.canary_industry', 'maritime')) {
            Log::channel('single')->info('MlLearningService: Canary mode, skipping non-canary industry', [
                'industry' => $industry,
                'canary_industry' => config('ml.canary_industry'),
            ]);
            return null;
        }

        $newWeights = $currentWeights->weights_json;

        // Get accumulated feature importance
        $features = DB::table('model_feature_importance')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('sample_count', '>=', 5)
            ->get();

        $appliedChanges = [];
        $weightMin = config('ml.weight_min', -25.0);
        $weightMax = config('ml.weight_max', 10.0);

        foreach ($features as $f) {
            $parts = explode(':', $f->feature_name, 2);
            $type = $parts[0] ?? '';
            $code = $parts[1] ?? '';

            if (!$type || !$code) continue;

            // Calculate confidence-weighted change
            $confidence = min(1.0, $f->sample_count / 50);
            $weightChange = $f->current_weight * $confidence;

            // Apply change based on type
            switch ($type) {
                case 'risk_flag':
                    $current = $newWeights['risk_flag_penalties'][$code] ?? -3;
                    $new = $this->clampWeight($current + $weightChange, $weightMin, 0);
                    $newWeights['risk_flag_penalties'][$code] = round($new, 1);
                    $appliedChanges[$f->feature_name] = round($weightChange, 2);
                    break;

                case 'meta':
                    $current = $newWeights['meta_penalties'][$code] ?? -5;
                    $new = $this->clampWeight($current + $weightChange, $weightMin, 0);
                    $newWeights['meta_penalties'][$code] = round($new, 1);
                    $appliedChanges[$f->feature_name] = round($weightChange, 2);
                    break;

                case 'source':
                    $key = $code . '_source';
                    $current = $newWeights['boosts'][$key] ?? 0;
                    $new = $this->clampWeight($current + $weightChange, 0, $weightMax);
                    $newWeights['boosts'][$key] = round($new, 1);
                    $appliedChanges[$f->feature_name] = round($weightChange, 2);
                    break;

                case 'industry':
                    $key = $code . '_industry';
                    $current = $newWeights['boosts'][$key] ?? 0;
                    $new = $this->clampWeight($current + $weightChange, 0, $weightMax);
                    $newWeights['boosts'][$key] = round($new, 1);
                    $appliedChanges[$f->feature_name] = round($weightChange, 2);
                    break;

                case 'assessment':
                    // English/video assessment weights
                    $key = $code;
                    $current = $newWeights['assessment_weights'][$key] ?? 0;
                    $new = $this->clampWeight($current + $weightChange, $weightMin, $weightMax);
                    $newWeights['assessment_weights'][$key] = round($new, 1);
                    $appliedChanges[$f->feature_name] = round($weightChange, 2);
                    break;
            }
        }

        // Volatility check: block if any single delta > 20% of current weight
        $volatilityMax = config('ml.volatility_max_ratio', 0.20);
        $suddenShiftRatio = config('ml.sudden_shift_ratio', 0.30);
        $volatileFeatures = [];
        $highDeltaCount = 0;

        foreach ($appliedChanges as $featureName => $change) {
            $parts = explode(':', $featureName, 2);
            $type = $parts[0] ?? '';
            $code = $parts[1] ?? '';
            $originalWeight = 0;

            switch ($type) {
                case 'risk_flag':
                    $originalWeight = ($currentWeights->weights_json['risk_flag_penalties'][$code] ?? -3);
                    break;
                case 'meta':
                    $originalWeight = ($currentWeights->weights_json['meta_penalties'][$code] ?? -5);
                    break;
                case 'source':
                    $originalWeight = ($currentWeights->weights_json['boosts'][$code . '_source'] ?? 0);
                    break;
                case 'industry':
                    $originalWeight = ($currentWeights->weights_json['boosts'][$code . '_industry'] ?? 0);
                    break;
                case 'assessment':
                    $originalWeight = ($currentWeights->weights_json['assessment_weights'][$code] ?? 0);
                    break;
            }

            if ($originalWeight != 0 && abs($change / $originalWeight) > $volatilityMax) {
                $volatileFeatures[$featureName] = [
                    'delta' => $change,
                    'original' => $originalWeight,
                    'ratio' => round(abs($change / $originalWeight), 4),
                ];
            }

            if (abs($change) > 0.10) {
                $highDeltaCount++;
            }
        }

        if (!empty($volatileFeatures)) {
            \App\Services\System\SystemEventService::alert('ml_volatility_block', 'MlLearningService', 'Weight update blocked: feature volatility exceeds threshold', [
                'volatile_features' => $volatileFeatures,
                'threshold' => $volatilityMax,
            ]);
            return null;
        }

        $totalChanges = count($appliedChanges);
        if ($totalChanges > 0 && ($highDeltaCount / $totalChanges) > $suddenShiftRatio) {
            \App\Services\System\SystemEventService::alert('ml_sudden_shift_block', 'MlLearningService', 'Weight update blocked: sudden shift across too many features', [
                'high_delta_count' => $highDeltaCount,
                'total_changes' => $totalChanges,
                'ratio' => round($highDeltaCount / $totalChanges, 4),
                'threshold' => $suddenShiftRatio,
            ]);
            return null;
        }

        // Create new version (always create, even if no changes - for audit trail)
        $version = $this->generateModelVersion();
        $industryNote = $industry ? " [{$industry}]" : "";

        // Deactivate all current weights
        ModelWeight::where('is_active', true)->update(['is_active' => false]);

        $weight = ModelWeight::create([
            'model_version' => $version,
            'weights_json' => $newWeights,
            'is_active' => true,
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
            'id' => Str::uuid()->toString(),
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
     * Generate a new model version string.
     */
    protected function generateModelVersion(): string
    {
        return 'ml_v1_' . now()->format('Ymd_Hi');
    }

    /**
     * Clamp weight to valid range.
     */
    protected function clampWeight(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /**
     * Rollback to a specific model version.
     */
    public function rollbackToVersion(string $modelVersion): array
    {
        $targetWeight = ModelWeight::where('model_version', $modelVersion)->first();

        if (!$targetWeight) {
            return ['success' => false, 'reason' => 'Model version not found'];
        }

        DB::transaction(function () use ($targetWeight) {
            // Deactivate all
            ModelWeight::where('is_active', true)->update(['is_active' => false]);

            // Activate target
            $targetWeight->update(['is_active' => true]);

            // Log rollback
            DB::table('learning_cycles')->insert([
                'id' => Str::uuid()->toString(),
                'model_version_before' => ModelWeight::where('is_active', false)
                    ->orderByDesc('created_at')
                    ->first()
                    ->model_version ?? 'unknown',
                'model_version_after' => $targetWeight->model_version,
                'industry_code' => null,
                'trigger' => 'rollback',
                'samples_processed' => 0,
                'weight_deltas_json' => json_encode(['rollback_to' => $targetWeight->model_version]),
                'created_at' => now(),
            ]);
        });

        Log::channel('single')->info('MlLearningService::rollbackToVersion', [
            'version' => $modelVersion,
        ]);

        return [
            'success' => true,
            'active_version' => $targetWeight->model_version,
            'message' => "Rolled back to version {$modelVersion}",
        ];
    }

    /**
     * Get learning diagnostics for CLI output.
     */
    public function getDiagnostics(int $windowDays = 90, ?string $industry = null): array
    {
        $from = now()->subDays($windowDays)->toDateString();

        // Warmup status
        $warmupStatus = $industry
            ? $this->checkWarmupStatus($industry)
            : ['ready' => true, 'current_samples' => 'N/A', 'min_samples' => config('ml.warmup_min_samples')];

        // Event counts by status
        $eventCounts = DB::table('learning_events')
            ->where('created_at', '>=', $from)
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Top changed features
        $topFeatures = DB::table('model_feature_importance')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('sample_count', '>=', 3)
            ->orderByDesc(DB::raw('ABS(current_weight)'))
            ->limit(10)
            ->get(['feature_name', 'current_weight', 'sample_count', 'positive_impact_count', 'negative_impact_count']);

        // Unstable features count
        $unstableCount = DB::table('learning_events')
            ->where('created_at', '>=', $from)
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('status', 'skipped_unstable_features')
            ->count();

        // Current active weights
        $activeWeights = ModelWeight::where('is_active', true)->first();

        return [
            'warmup_status' => $warmupStatus,
            'event_counts' => $eventCounts,
            'top_features' => $topFeatures,
            'unstable_features_count' => $unstableCount,
            'active_model_version' => $activeWeights ? $activeWeights->model_version : null,
        ];
    }

    /**
     * Batch learn from historical outcomes (for simulation/backfill).
     */
    public function batchLearn(int $windowDays = 90, ?string $industry = null, bool $dryRun = false): array
    {
        $from = now()->subDays($windowDays)->toDateString();

        $query = DB::table('interview_outcomes as io')
            ->join('form_interviews as fi', 'io.form_interview_id', '=', 'fi.id')
            ->join('model_features as mf', 'mf.form_interview_id', '=', 'fi.id')
            ->join('model_predictions as mp', 'mp.form_interview_id', '=', 'fi.id')
            ->join('pool_candidates as pc', 'fi.pool_candidate_id', '=', 'pc.id')
            ->where('pc.is_demo', false)
            ->where('io.created_at', '>=', $from)
            ->when($industry, fn($q) => $q->where('mf.industry_code', $industry));

        $outcomes = $query->select([
            'io.id as outcome_id',
            'fi.id as interview_id',
        ])->get();

        $results = [
            'total' => $outcomes->count(),
            'processed' => 0,
            'applied' => 0,
            'warmup_only' => 0,
            'skipped_unstable' => 0,
            'skipped_small_error' => 0,
            'errors' => 0,
        ];

        if ($dryRun) {
            $results['dry_run'] = true;
            $results['would_process'] = $outcomes->count();
            return $results;
        }

        foreach ($outcomes as $row) {
            $outcome = InterviewOutcome::find($row->outcome_id);
            if ($outcome) {
                $result = $this->updateWeightsFromOutcome($outcome);
                $results['processed']++;

                $status = $result['status'] ?? 'unknown';
                switch ($status) {
                    case 'applied':
                        $results['applied']++;
                        break;
                    case 'warmup_only':
                        $results['warmup_only']++;
                        break;
                    case 'skipped_unstable_features':
                        $results['skipped_unstable']++;
                        break;
                    case 'skipped_small_error':
                        $results['skipped_small_error']++;
                        break;
                    default:
                        if (!($result['success'] ?? false)) {
                            $results['errors']++;
                        }
                }
            }
        }

        // Force create new weights after batch (unless dry run)
        $newWeights = $this->createNewWeightsFromLearning($industry);
        $results['new_weights_version'] = $newWeights ? $newWeights->model_version : null;

        // Get diagnostics
        $results['diagnostics'] = $this->getDiagnostics($windowDays, $industry);

        return $results;
    }
}
