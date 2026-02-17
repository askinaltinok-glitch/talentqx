<?php

namespace App\Console\Commands;

use App\Models\ModelWeight;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MlTuneCommand extends Command
{
    protected $signature = 'ml:tune 
        {--window=90 : Number of days to look back}
        {--industry= : Filter by industry code}
        {--min-samples=30 : Minimum samples required}';

    protected $description = 'Tune ML model weights based on outcome data';

    public function handle(): int
    {
        $window = (int) $this->option('window');
        $industry = $this->option('industry');
        $minSamples = (int) $this->option('min-samples');

        $this->info("ML Weight Tuning");
        $this->info("Window: {$window} days");
        $this->info("Industry: " . ($industry ?: 'all'));

        // Get training data: features + outcomes
        $query = DB::table('model_features as mf')
            ->join('interview_outcomes as io', 'io.form_interview_id', '=', 'mf.form_interview_id')
            ->where('mf.created_at', '>=', now()->subDays($window)->toDateString())
            ->whereNotNull('io.outcome_score');

        if ($industry) {
            $query->where('mf.industry_code', $industry);
        }

        $data = $query->select([
            'mf.risk_flags_json',
            'mf.answers_meta_json',
            'mf.source_channel',
            'mf.industry_code',
            'mf.calibrated_score',
            'io.outcome_score',
        ])->get();

        $this->info("Found {$data->count()} samples with outcomes");

        if ($data->count() < $minSamples) {
            $this->warn("Not enough samples (minimum: {$minSamples}). Skipping tune.");
            return Command::FAILURE;
        }

        // Load current weights as base
        $currentWeights = ModelWeight::latest();
        $baseWeights = $currentWeights ? $currentWeights->weights_json : $this->getDefaultWeights();

        // Analyze correlations and adjust weights
        $newWeights = $this->tuneWeights($data, $baseWeights);

        // Calculate improvement metrics
        $metrics = $this->evaluateWeights($data, $baseWeights, $newWeights);

        $this->info("Tuning Results:");
        $this->info("  Base MAE: " . round($metrics['base_mae'], 2));
        $this->info("  New MAE: " . round($metrics['new_mae'], 2));
        $this->info("  Improvement: " . round($metrics['improvement_pct'], 1) . "%");

        // Only save if improvement > 0
        if ($metrics['improvement_pct'] <= 0) {
            $this->warn("No improvement detected. Keeping current weights.");
            return Command::SUCCESS;
        }

        // Save new weights
        $version = ModelWeight::nextVersion();
        ModelWeight::create([
            'model_version' => $version,
            'weights_json' => $newWeights,
            'notes' => sprintf(
                "Auto-tuned on %s. Window: %d days, Samples: %d, Industry: %s, MAE improvement: %.1f%%",
                now()->toDateString(),
                $window,
                $data->count(),
                $industry ?: 'all',
                $metrics['improvement_pct']
            ),
            'created_at' => now(),
        ]);

        $this->info("Saved new weights as version: {$version}");

        return Command::SUCCESS;
    }

    /**
     * Tune weights based on outcome correlation.
     */
    protected function tuneWeights($data, array $baseWeights): array
    {
        $newWeights = $baseWeights;

        // Analyze risk flag impact
        $riskFlagImpact = $this->analyzeRiskFlagImpact($data);
        
        foreach ($riskFlagImpact as $flag => $impact) {
            // Adjust penalty based on correlation with bad outcomes
            // If flag correlates with low outcome, increase penalty
            $currentPenalty = $baseWeights['risk_flag_penalties'][$flag] ?? -3;
            $adjustment = $impact['correlation'] * -5; // Scale factor
            $newWeights['risk_flag_penalties'][$flag] = round(
                max(-20, min(-1, $currentPenalty + $adjustment)),
                1
            );
        }

        // Analyze meta penalties
        $metaImpact = $this->analyzeMetaImpact($data);
        
        if (isset($metaImpact['sparse'])) {
            $currentPenalty = $baseWeights['meta_penalties']['sparse_answers'] ?? -5;
            $adjustment = $metaImpact['sparse']['correlation'] * -3;
            $newWeights['meta_penalties']['sparse_answers'] = round(
                max(-15, min(-1, $currentPenalty + $adjustment)),
                1
            );
        }

        if (isset($metaImpact['incomplete'])) {
            $currentPenalty = $baseWeights['meta_penalties']['incomplete_interview'] ?? -10;
            $adjustment = $metaImpact['incomplete']['correlation'] * -5;
            $newWeights['meta_penalties']['incomplete_interview'] = round(
                max(-20, min(-1, $currentPenalty + $adjustment)),
                1
            );
        }

        // Analyze source channel boosts
        $sourceImpact = $this->analyzeSourceImpact($data);
        
        foreach ($sourceImpact as $source => $impact) {
            if ($impact['correlation'] > 0.1) {
                // Positive correlation = boost
                $boost = round(min(5, $impact['correlation'] * 10), 1);
                $newWeights['boosts'][$source . '_source'] = $boost;
            }
        }

        return $newWeights;
    }

    /**
     * Analyze risk flag correlation with outcomes.
     */
    protected function analyzeRiskFlagImpact($data): array
    {
        $flagStats = [];

        foreach ($data as $row) {
            $flags = json_decode($row->risk_flags_json ?? '[]', true) ?: [];
            $outcomeScore = $row->outcome_score;

            foreach ($flags as $flag) {
                $code = is_array($flag) ? ($flag['code'] ?? null) : $flag;
                if (!$code) continue;

                if (!isset($flagStats[$code])) {
                    $flagStats[$code] = ['scores' => [], 'count' => 0];
                }
                $flagStats[$code]['scores'][] = $outcomeScore;
                $flagStats[$code]['count']++;
            }
        }

        $result = [];
        $overallMean = $data->avg('outcome_score');

        foreach ($flagStats as $flag => $stats) {
            if ($stats['count'] < 5) continue; // Need minimum samples

            $flagMean = array_sum($stats['scores']) / count($stats['scores']);
            // Negative correlation means flag predicts lower outcomes
            $correlation = ($overallMean - $flagMean) / max(1, $overallMean) * -1;

            $result[$flag] = [
                'count' => $stats['count'],
                'mean_outcome' => round($flagMean, 1),
                'correlation' => round($correlation, 3),
            ];
        }

        return $result;
    }

    /**
     * Analyze meta feature impact.
     */
    protected function analyzeMetaImpact($data): array
    {
        $sparseScores = [];
        $incompleteScores = [];
        $normalScores = [];

        foreach ($data as $row) {
            $meta = json_decode($row->answers_meta_json ?? '{}', true) ?: [];
            $outcomeScore = $row->outcome_score;

            if ($meta['rf_sparse'] ?? false) {
                $sparseScores[] = $outcomeScore;
            }
            if ($meta['rf_incomplete'] ?? false) {
                $incompleteScores[] = $outcomeScore;
            }
            if (!($meta['rf_sparse'] ?? false) && !($meta['rf_incomplete'] ?? false)) {
                $normalScores[] = $outcomeScore;
            }
        }

        $normalMean = count($normalScores) > 0 ? array_sum($normalScores) / count($normalScores) : 50;
        $result = [];

        if (count($sparseScores) >= 5) {
            $sparseMean = array_sum($sparseScores) / count($sparseScores);
            $result['sparse'] = [
                'count' => count($sparseScores),
                'mean_outcome' => round($sparseMean, 1),
                'correlation' => round(($normalMean - $sparseMean) / max(1, $normalMean), 3),
            ];
        }

        if (count($incompleteScores) >= 5) {
            $incompleteMean = array_sum($incompleteScores) / count($incompleteScores);
            $result['incomplete'] = [
                'count' => count($incompleteScores),
                'mean_outcome' => round($incompleteMean, 1),
                'correlation' => round(($normalMean - $incompleteMean) / max(1, $normalMean), 3),
            ];
        }

        return $result;
    }

    /**
     * Analyze source channel impact.
     */
    protected function analyzeSourceImpact($data): array
    {
        $sourceStats = [];
        $overallMean = $data->avg('outcome_score');

        foreach ($data as $row) {
            $source = $row->source_channel ?? 'unknown';
            if (!isset($sourceStats[$source])) {
                $sourceStats[$source] = ['scores' => [], 'count' => 0];
            }
            $sourceStats[$source]['scores'][] = $row->outcome_score;
            $sourceStats[$source]['count']++;
        }

        $result = [];
        foreach ($sourceStats as $source => $stats) {
            if ($stats['count'] < 5) continue;

            $sourceMean = array_sum($stats['scores']) / count($stats['scores']);
            $correlation = ($sourceMean - $overallMean) / max(1, $overallMean);

            $result[$source] = [
                'count' => $stats['count'],
                'mean_outcome' => round($sourceMean, 1),
                'correlation' => round($correlation, 3),
            ];
        }

        return $result;
    }

    /**
     * Evaluate weights against data.
     */
    protected function evaluateWeights($data, array $baseWeights, array $newWeights): array
    {
        $baseErrors = [];
        $newErrors = [];

        foreach ($data as $row) {
            $actual = $row->outcome_score;
            $basePredicted = $this->predictWithWeights($row, $baseWeights);
            $newPredicted = $this->predictWithWeights($row, $newWeights);

            $baseErrors[] = abs($actual - $basePredicted);
            $newErrors[] = abs($actual - $newPredicted);
        }

        $baseMae = array_sum($baseErrors) / count($baseErrors);
        $newMae = array_sum($newErrors) / count($newErrors);
        $improvement = (($baseMae - $newMae) / max(1, $baseMae)) * 100;

        return [
            'base_mae' => $baseMae,
            'new_mae' => $newMae,
            'improvement_pct' => $improvement,
        ];
    }

    /**
     * Predict outcome with given weights.
     */
    protected function predictWithWeights(object $row, array $weights): int
    {
        $base = $row->calibrated_score ?? 50;

        // Risk flag penalties
        $riskPenalty = 0;
        $flags = json_decode($row->risk_flags_json ?? '[]', true) ?: [];
        foreach ($flags as $flag) {
            $code = is_array($flag) ? ($flag['code'] ?? null) : $flag;
            if ($code) {
                $riskPenalty += $weights['risk_flag_penalties'][$code] ?? $weights['risk_flag_penalties']['default'] ?? -3;
            }
        }

        // Meta penalties
        $metaPenalty = 0;
        $meta = json_decode($row->answers_meta_json ?? '{}', true) ?: [];
        if ($meta['rf_sparse'] ?? false) {
            $metaPenalty += $weights['meta_penalties']['sparse_answers'] ?? -5;
        }
        if ($meta['rf_incomplete'] ?? false) {
            $metaPenalty += $weights['meta_penalties']['incomplete_interview'] ?? -10;
        }

        // Boosts
        $boost = 0;
        $source = $row->source_channel ?? '';
        if (isset($weights['boosts'][$source . '_source'])) {
            $boost += $weights['boosts'][$source . '_source'];
        }
        if ($row->industry_code === 'maritime' && isset($weights['boosts']['maritime_industry'])) {
            $boost += $weights['boosts']['maritime_industry'];
        }

        return max(0, min(100, (int) round($base + $riskPenalty + $metaPenalty + $boost)));
    }

    /**
     * Get default weights.
     */
    protected function getDefaultWeights(): array
    {
        return [
            'risk_flag_penalties' => ['default' => -3],
            'meta_penalties' => [
                'sparse_answers' => -5,
                'incomplete_interview' => -10,
            ],
            'boosts' => [
                'maritime_industry' => 3,
                'referral_source' => 2,
            ],
            'thresholds' => ['good' => 50],
        ];
    }
}
