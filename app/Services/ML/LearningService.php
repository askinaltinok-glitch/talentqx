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
 * LearningService - Closed Learning Loop
 * 
 * This service implements the actual learning cycle:
 * 1. Outcome arrives → compare with prediction
 * 2. Calculate error → update weights
 * 3. Detect patterns → false positives / false negatives
 * 4. Track source channel success
 * 5. Store learning insights
 */
class LearningService
{
    protected const LEARNING_RATE = 0.1; // How aggressive to learn
    protected const MIN_SAMPLES_FOR_LEARNING = 10;
    protected const PATTERN_THRESHOLD = 3; // Min occurrences to flag a pattern

    /**
     * Process a new outcome and trigger learning.
     * Called when InterviewOutcome is recorded.
     */
    public function learnFromOutcome(InterviewOutcome $outcome): array
    {
        $interview = $outcome->formInterview;
        if (!$interview) {
            return ['learned' => false, 'reason' => 'No interview found'];
        }

        // Get feature and prediction
        $feature = ModelFeature::where('form_interview_id', $interview->id)->first();
        $prediction = ModelPrediction::where('form_interview_id', $interview->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$feature || !$prediction) {
            return ['learned' => false, 'reason' => 'No feature or prediction found'];
        }

        // Calculate actual outcome score
        $actualScore = $this->calculateOutcomeScore($outcome);
        $predictedScore = $prediction->predicted_outcome_score;
        $error = $actualScore - $predictedScore;

        // Determine if this is a false positive or false negative
        $actualLabel = $actualScore >= 50 ? 'GOOD' : 'BAD';
        $predictedLabel = $prediction->predicted_label;

        $isFalsePositive = $predictedLabel === 'GOOD' && $actualLabel === 'BAD';
        $isFalseNegative = $predictedLabel === 'BAD' && $actualLabel === 'GOOD';

        // Store learning event
        $learningEvent = $this->storeLearningEvent($interview, $feature, $prediction, $outcome, [
            'actual_score' => $actualScore,
            'predicted_score' => $predictedScore,
            'error' => $error,
            'actual_label' => $actualLabel,
            'predicted_label' => $predictedLabel,
            'is_false_positive' => $isFalsePositive,
            'is_false_negative' => $isFalseNegative,
        ]);

        // Analyze patterns if this is an error case
        $patterns = [];
        if ($isFalsePositive) {
            $patterns['false_positive'] = $this->analyzeFalsePositive($feature, $outcome);
        }
        if ($isFalseNegative) {
            $patterns['false_negative'] = $this->analyzeFalseNegative($feature, $outcome);
        }

        // Track source channel success
        $this->trackSourceChannelSuccess($feature, $outcome);

        // Log learning
        Log::channel('single')->info('LearningService::learnFromOutcome', [
            'interview_id' => $interview->id,
            'predicted' => $predictedScore,
            'actual' => $actualScore,
            'error' => $error,
            'fp' => $isFalsePositive,
            'fn' => $isFalseNegative,
        ]);

        return [
            'learned' => true,
            'interview_id' => $interview->id,
            'predicted_score' => $predictedScore,
            'actual_score' => $actualScore,
            'error' => $error,
            'is_false_positive' => $isFalsePositive,
            'is_false_negative' => $isFalseNegative,
            'patterns' => $patterns,
        ];
    }

    /**
     * Calculate outcome score from InterviewOutcome (0-100).
     */
    protected function calculateOutcomeScore(InterviewOutcome $outcome): int
    {
        // If outcome_score is already set, use it
        if ($outcome->outcome_score !== null) {
            return (int) $outcome->outcome_score;
        }

        // Otherwise calculate from components
        $score = 0;

        if (!$outcome->hired) {
            return 0; // Not hired = 0
        }

        $score = 10; // Hired

        if ($outcome->started) {
            $score = 30;
        }

        if ($outcome->still_employed_30d) {
            $score = 50;
        }

        if ($outcome->still_employed_90d) {
            $score = 70;
        }

        if ($outcome->still_employed_90d && !$outcome->incident_flag) {
            $score = 85;
        }

        if ($outcome->still_employed_90d && !$outcome->incident_flag && $outcome->performance_rating >= 4) {
            $score = 100;
        }

        return $score;
    }

    /**
     * Store learning event for analysis.
     */
    protected function storeLearningEvent(
        FormInterview $interview,
        ModelFeature $feature,
        ModelPrediction $prediction,
        InterviewOutcome $outcome,
        array $metrics
    ): void {
        DB::table('learning_events')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'form_interview_id' => $interview->id,
            'model_version' => $prediction->model_version,
            'industry_code' => $feature->industry_code,
            'source_channel' => $feature->source_channel,
            'predicted_score' => $metrics['predicted_score'],
            'actual_score' => $metrics['actual_score'],
            'error' => $metrics['error'],
            'predicted_label' => $metrics['predicted_label'],
            'actual_label' => $metrics['actual_label'],
            'is_false_positive' => $metrics['is_false_positive'],
            'is_false_negative' => $metrics['is_false_negative'],
            'feature_snapshot_json' => json_encode([
                'risk_flags' => $feature->risk_flags_json,
                'answers_meta' => $feature->answers_meta_json,
                'calibrated_score' => $feature->calibrated_score,
            ]),
            'created_at' => now(),
        ]);
    }

    /**
     * Analyze false positive pattern (predicted GOOD, actual BAD).
     * What risk flags were missed?
     */
    protected function analyzeFalsePositive(ModelFeature $feature, InterviewOutcome $outcome): array
    {
        $riskFlags = $feature->risk_flags_json ?? [];
        $answersMeta = $feature->answers_meta_json ?? [];

        $patterns = [
            'missed_signals' => [],
            'source_channel' => $feature->source_channel,
            'industry' => $feature->industry_code,
        ];

        // Check if structural issues were present but not penalized enough
        if ($answersMeta['rf_sparse'] ?? false) {
            $patterns['missed_signals'][] = 'sparse_answers_underweighted';
        }
        if ($answersMeta['rf_incomplete'] ?? false) {
            $patterns['missed_signals'][] = 'incomplete_underweighted';
        }
        if (($answersMeta['avg_answer_len'] ?? 100) < 30) {
            $patterns['missed_signals'][] = 'very_short_answers_underweighted';
        }

        // Check if certain risk flags should have higher penalty
        foreach ($riskFlags as $flag) {
            $code = is_array($flag) ? ($flag['code'] ?? null) : $flag;
            if ($code) {
                $patterns['missed_signals'][] = "risk_flag_underweighted:{$code}";
            }
        }

        // Track for weight adjustment
        $this->recordPatternOccurrence('false_positive', $patterns);

        return $patterns;
    }

    /**
     * Analyze false negative pattern (predicted BAD, actual GOOD).
     * What risk flags were over-weighted?
     */
    protected function analyzeFalseNegative(ModelFeature $feature, InterviewOutcome $outcome): array
    {
        $riskFlags = $feature->risk_flags_json ?? [];
        $answersMeta = $feature->answers_meta_json ?? [];

        $patterns = [
            'overweighted_signals' => [],
            'source_channel' => $feature->source_channel,
            'industry' => $feature->industry_code,
        ];

        // This person turned out good despite risk flags - maybe those flags are overweighted
        foreach ($riskFlags as $flag) {
            $code = is_array($flag) ? ($flag['code'] ?? null) : $flag;
            if ($code) {
                $patterns['overweighted_signals'][] = "risk_flag_overweighted:{$code}";
            }
        }

        // Maybe structural penalties are too harsh
        if ($answersMeta['rf_sparse'] ?? false) {
            $patterns['overweighted_signals'][] = 'sparse_penalty_too_harsh';
        }
        if ($answersMeta['rf_incomplete'] ?? false) {
            $patterns['overweighted_signals'][] = 'incomplete_penalty_too_harsh';
        }

        // Track for weight adjustment
        $this->recordPatternOccurrence('false_negative', $patterns);

        return $patterns;
    }

    /**
     * Track source channel success/failure.
     */
    protected function trackSourceChannelSuccess(ModelFeature $feature, InterviewOutcome $outcome): void
    {
        $channel = $feature->source_channel ?? 'unknown';
        $industry = $feature->industry_code ?? 'general';
        $success = $this->calculateOutcomeScore($outcome) >= 50;

        DB::table('source_channel_metrics')->updateOrInsert(
            [
                'source_channel' => $channel,
                'industry_code' => $industry,
            ],
            [
                'total_outcomes' => DB::raw('COALESCE(total_outcomes, 0) + 1'),
                'successful_outcomes' => DB::raw('COALESCE(successful_outcomes, 0) + ' . ($success ? 1 : 0)),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Record pattern occurrence for later weight adjustment.
     */
    protected function recordPatternOccurrence(string $type, array $patterns): void
    {
        $signals = $type === 'false_positive'
            ? ($patterns['missed_signals'] ?? [])
            : ($patterns['overweighted_signals'] ?? []);

        foreach ($signals as $signal) {
            DB::table('learning_patterns')->updateOrInsert(
                [
                    'pattern_type' => $type,
                    'signal' => $signal,
                    'industry_code' => $patterns['industry'] ?? 'general',
                ],
                [
                    'occurrence_count' => DB::raw('COALESCE(occurrence_count, 0) + 1'),
                    'last_occurred_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Get accumulated learning insights.
     */
    public function getLearningInsights(?string $industry = null, int $days = 30): array
    {
        $from = now()->subDays($days)->toDateString();

        // Get error statistics
        $errorStats = DB::table('learning_events')
            ->where('created_at', '>=', $from)
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->selectRaw('
                COUNT(*) as total,
                AVG(ABS(error)) as mae,
                SUM(CASE WHEN is_false_positive THEN 1 ELSE 0 END) as false_positives,
                SUM(CASE WHEN is_false_negative THEN 1 ELSE 0 END) as false_negatives,
                AVG(predicted_score) as avg_predicted,
                AVG(actual_score) as avg_actual
            ')
            ->first();

        // Get top false positive patterns
        $fpPatterns = DB::table('learning_patterns')
            ->where('pattern_type', 'false_positive')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('occurrence_count', '>=', self::PATTERN_THRESHOLD)
            ->orderByDesc('occurrence_count')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'signal' => $p->signal,
                'count' => $p->occurrence_count,
                'recommendation' => $this->getRecommendation($p->signal, 'increase_penalty'),
            ])
            ->toArray();

        // Get top false negative patterns
        $fnPatterns = DB::table('learning_patterns')
            ->where('pattern_type', 'false_negative')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('occurrence_count', '>=', self::PATTERN_THRESHOLD)
            ->orderByDesc('occurrence_count')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'signal' => $p->signal,
                'count' => $p->occurrence_count,
                'recommendation' => $this->getRecommendation($p->signal, 'decrease_penalty'),
            ])
            ->toArray();

        // Get source channel success rates
        $sourceChannelStats = DB::table('source_channel_metrics')
            ->when($industry, fn($q) => $q->where('industry_code', $industry))
            ->where('total_outcomes', '>=', 5)
            ->orderByDesc(DB::raw('successful_outcomes * 1.0 / total_outcomes'))
            ->get()
            ->map(fn($s) => [
                'source_channel' => $s->source_channel,
                'total' => $s->total_outcomes,
                'successful' => $s->successful_outcomes,
                'success_rate' => round(($s->successful_outcomes / $s->total_outcomes) * 100, 1),
            ])
            ->toArray();

        return [
            'period_days' => $days,
            'industry' => $industry ?? 'all',
            'error_stats' => [
                'total_outcomes' => $errorStats->total ?? 0,
                'mae' => $errorStats->mae ? round($errorStats->mae, 2) : null,
                'false_positives' => $errorStats->false_positives ?? 0,
                'false_negatives' => $errorStats->false_negatives ?? 0,
                'avg_predicted' => $errorStats->avg_predicted ? round($errorStats->avg_predicted, 1) : null,
                'avg_actual' => $errorStats->avg_actual ? round($errorStats->avg_actual, 1) : null,
            ],
            'false_positive_patterns' => $fpPatterns,
            'false_negative_patterns' => $fnPatterns,
            'source_channel_performance' => $sourceChannelStats,
        ];
    }

    /**
     * Generate weight adjustment recommendations.
     */
    public function generateWeightRecommendations(?string $industry = null): array
    {
        $insights = $this->getLearningInsights($industry);
        $recommendations = [];

        // From false positives: increase penalties
        foreach ($insights['false_positive_patterns'] as $pattern) {
            if (preg_match('/risk_flag_underweighted:(\w+)/', $pattern['signal'], $m)) {
                $recommendations[] = [
                    'type' => 'increase_penalty',
                    'target' => 'risk_flag',
                    'code' => $m[1],
                    'reason' => "FP pattern: {$pattern['count']} times this flag was present but hire failed",
                    'suggested_change' => -2, // Increase penalty by 2
                ];
            }
            if (str_contains($pattern['signal'], 'sparse_answers')) {
                $recommendations[] = [
                    'type' => 'increase_penalty',
                    'target' => 'meta_penalty',
                    'code' => 'sparse_answers',
                    'reason' => "FP pattern: sparse answers correlate with bad outcomes",
                    'suggested_change' => -2,
                ];
            }
        }

        // From false negatives: decrease penalties
        foreach ($insights['false_negative_patterns'] as $pattern) {
            if (preg_match('/risk_flag_overweighted:(\w+)/', $pattern['signal'], $m)) {
                $recommendations[] = [
                    'type' => 'decrease_penalty',
                    'target' => 'risk_flag',
                    'code' => $m[1],
                    'reason' => "FN pattern: {$pattern['count']} times this flag led to reject but person succeeded",
                    'suggested_change' => 1, // Decrease penalty by 1
                ];
            }
        }

        // From source channels: add boosts for high performers
        foreach ($insights['source_channel_performance'] as $channel) {
            if ($channel['success_rate'] >= 70 && $channel['total'] >= 10) {
                $recommendations[] = [
                    'type' => 'add_boost',
                    'target' => 'source_channel',
                    'code' => $channel['source_channel'],
                    'reason' => "High success rate: {$channel['success_rate']}% from {$channel['total']} outcomes",
                    'suggested_change' => 3,
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Apply recommendations to create new weight version.
     */
    public function applyRecommendations(array $recommendations, ?string $industry = null): ?ModelWeight
    {
        if (empty($recommendations)) {
            return null;
        }

        $currentWeights = ModelWeight::latest();
        if (!$currentWeights) {
            return null;
        }

        $newWeights = $currentWeights->weights_json;

        foreach ($recommendations as $rec) {
            switch ($rec['type']) {
                case 'increase_penalty':
                case 'decrease_penalty':
                    if ($rec['target'] === 'risk_flag') {
                        $key = $rec['code'];
                        $current = $newWeights['risk_flag_penalties'][$key] ?? -3;
                        $newWeights['risk_flag_penalties'][$key] = $current + $rec['suggested_change'];
                    } elseif ($rec['target'] === 'meta_penalty') {
                        $key = $rec['code'];
                        $current = $newWeights['meta_penalties'][$key] ?? -5;
                        $newWeights['meta_penalties'][$key] = $current + $rec['suggested_change'];
                    }
                    break;

                case 'add_boost':
                    if ($rec['target'] === 'source_channel') {
                        $key = $rec['code'] . '_source';
                        $newWeights['boosts'][$key] = $rec['suggested_change'];
                    }
                    break;
            }
        }

        // Create new weight version
        $version = ModelWeight::nextVersion();
        $industryNote = $industry ? " for {$industry}" : "";

        return ModelWeight::create([
            'model_version' => $version,
            'weights_json' => $newWeights,
            'notes' => sprintf(
                "Auto-learned%s on %s. Applied %d recommendations from pattern analysis.",
                $industryNote,
                now()->toDateString(),
                count($recommendations)
            ),
            'created_at' => now(),
        ]);
    }

    /**
     * Get recommendation text from signal.
     */
    protected function getRecommendation(string $signal, string $direction): string
    {
        if (str_contains($signal, 'risk_flag')) {
            $flag = str_replace(['risk_flag_underweighted:', 'risk_flag_overweighted:'], '', $signal);
            return $direction === 'increase_penalty'
                ? "Increase penalty for {$flag}"
                : "Decrease penalty for {$flag}";
        }
        if (str_contains($signal, 'sparse')) {
            return $direction === 'increase_penalty'
                ? "Increase sparse answers penalty"
                : "Decrease sparse answers penalty";
        }
        if (str_contains($signal, 'incomplete')) {
            return $direction === 'increase_penalty'
                ? "Increase incomplete interview penalty"
                : "Decrease incomplete interview penalty";
        }
        return "Adjust weight for: {$signal}";
    }
}
