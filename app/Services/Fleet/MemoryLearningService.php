<?php

namespace App\Services\Fleet;

use App\Models\CrewOutcome;
use App\Models\SynergyWeightSet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemoryLearningService
{
    /**
     * Retrain synergy weights based on outcome data.
     * Can be global or company-scoped.
     *
     * NO hard auto-hire/reject — only adjusts pillar weights + confidence notes.
     */
    public function retrain(string $scope = 'global', ?string $companyId = null, int $windowDays = 90): array
    {
        $result = [
            'status' => 'skipped',
            'reason' => null,
            'sample_size' => 0,
            'deltas' => null,
            'weights' => null,
        ];

        try {
            // Gather outcomes within training window
            $query = CrewOutcome::withoutTenantScope()
                ->where('created_at', '>=', now()->subDays($windowDays));

            if ($scope === 'company' && $companyId) {
                $query->where('company_id', $companyId);
            }

            $outcomes = $query->get();
            $sampleSize = $outcomes->count();
            $result['sample_size'] = $sampleSize;

            // Minimum sample size gate
            if ($sampleSize < SynergyWeightSet::MIN_SAMPLE_SIZE) {
                $result['reason'] = "Insufficient data: {$sampleSize} outcomes (minimum: " . SynergyWeightSet::MIN_SAMPLE_SIZE . ")";
                return $result;
            }

            // Current weights
            $currentWeights = $this->getCurrentWeights($scope, $companyId);

            // Analyze outcome patterns to suggest weight adjustments
            $analysis = $this->analyzeOutcomes($outcomes);
            $suggestedWeights = $this->computeWeightSuggestions($currentWeights, $analysis);
            $deltas = $this->computeDeltas($currentWeights, $suggestedWeights);

            // Build audit log
            $auditEntry = [
                'timestamp' => now()->toIso8601String(),
                'scope' => $scope,
                'company_id' => $companyId,
                'window_days' => $windowDays,
                'sample_size' => $sampleSize,
                'outcome_distribution' => $analysis['distribution'],
                'previous_weights' => $currentWeights,
                'new_weights' => $suggestedWeights,
                'deltas' => $deltas,
                'rationale' => $analysis['rationale'],
            ];

            // Store the new weight set
            $weightSet = SynergyWeightSet::updateOrCreate(
                [
                    'scope' => $scope,
                    'company_id' => $scope === 'company' ? $companyId : null,
                ],
                [
                    'weights_json' => $suggestedWeights,
                    'deltas_json' => $deltas,
                    'audit_log_json' => $auditEntry,
                    'last_training_window' => "{$windowDays}d",
                    'sample_size' => $sampleSize,
                ]
            );

            $result['status'] = 'trained';
            $result['weights'] = $suggestedWeights;
            $result['deltas'] = $deltas;

            Log::channel('single')->info('MemoryLearningService: retrained', $auditEntry);

            return $result;
        } catch (\Throwable $e) {
            Log::channel('single')->error('MemoryLearningService::retrain failed', [
                'scope' => $scope,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            $result['status'] = 'error';
            $result['reason'] = $e->getMessage();
            return $result;
        }
    }

    /**
     * Get current training status for display.
     */
    public function getStatus(string $scope = 'global', ?string $companyId = null): array
    {
        $weightSet = $scope === 'company' && $companyId
            ? SynergyWeightSet::forCompany($companyId)
            : SynergyWeightSet::where('scope', 'global')->first();

        // Count outcomes in last 90 days
        $query = CrewOutcome::withoutTenantScope()
            ->where('created_at', '>=', now()->subDays(90));
        if ($scope === 'company' && $companyId) {
            $query->where('company_id', $companyId);
        }
        $recentOutcomes = $query->count();

        $status = 'not_enough_data';
        if ($recentOutcomes >= SynergyWeightSet::MIN_SAMPLE_SIZE) {
            $status = $weightSet ? 'learning_active' : 'ready_to_train';
        }

        return [
            'status' => $status,
            'sample_size' => $recentOutcomes,
            'min_required' => SynergyWeightSet::MIN_SAMPLE_SIZE,
            'current_weights' => $this->getCurrentWeights($scope, $companyId),
            'last_trained_at' => $weightSet?->updated_at?->toIso8601String(),
            'last_training_window' => $weightSet?->last_training_window,
            'deltas' => $weightSet?->deltas_json,
            'scope' => $scope,
        ];
    }

    /**
     * Resolve effective weights for synergy scoring.
     * Company override > Global learned > Config default.
     */
    public function resolveWeights(?string $companyId = null): array
    {
        // 1. Company override
        if ($companyId) {
            $companySet = SynergyWeightSet::forCompany($companyId);
            if ($companySet && $companySet->isTrainable()) {
                return $companySet->weights_json;
            }
        }

        // 2. Global learned
        $globalSet = SynergyWeightSet::where('scope', 'global')
            ->where('sample_size', '>=', SynergyWeightSet::MIN_SAMPLE_SIZE)
            ->first();
        if ($globalSet) {
            return $globalSet->weights_json;
        }

        // 3. Config default
        return config('maritime.synergy_v2.component_weights', [
            'captain_fit' => 0.25,
            'team_balance' => 0.20,
            'vessel_fit' => 0.30,
            'operational_risk' => 0.25,
        ]);
    }

    // ─── Private ──────────────────────────────────────────────────────

    private function getCurrentWeights(string $scope, ?string $companyId): array
    {
        if ($scope === 'company' && $companyId) {
            $set = SynergyWeightSet::forCompany($companyId);
            if ($set) return $set->weights_json;
        }

        $globalSet = SynergyWeightSet::where('scope', 'global')->first();
        if ($globalSet) return $globalSet->weights_json;

        return config('maritime.synergy_v2.component_weights', [
            'captain_fit' => 0.25,
            'team_balance' => 0.20,
            'vessel_fit' => 0.30,
            'operational_risk' => 0.25,
        ]);
    }

    /**
     * Analyze outcomes to understand which pillar correlates with good/bad results.
     */
    private function analyzeOutcomes($outcomes): array
    {
        $distribution = [
            'early_termination' => 0,
            'conflict_reported' => 0,
            'safety_incident' => 0,
            'performance_high' => 0,
            'retention_success' => 0,
        ];

        $captainLinked = 0;
        $totalSeverity = 0;

        foreach ($outcomes as $o) {
            $distribution[$o->outcome_type] = ($distribution[$o->outcome_type] ?? 0) + 1;
            $totalSeverity += $o->severity;
            if ($o->captain_candidate_id) $captainLinked++;
        }

        $total = $outcomes->count();
        $positiveRate = ($distribution['performance_high'] + $distribution['retention_success']) / max(1, $total);
        $negativeRate = ($distribution['early_termination'] + $distribution['conflict_reported'] + $distribution['safety_incident']) / max(1, $total);
        $captainLinkRate = $captainLinked / max(1, $total);
        $avgSeverity = $total > 0 ? $totalSeverity / $total : 0;

        $rationale = [];

        if ($negativeRate > 0.5) {
            $rationale[] = "High negative outcome rate ({$negativeRate}): consider increasing operational_risk weight.";
        }
        if ($captainLinkRate > 0.3) {
            $rationale[] = "Captain-linked outcome rate ({$captainLinkRate}): captain_fit pillar has strong signal.";
        }
        if ($distribution['conflict_reported'] > $total * 0.2) {
            $rationale[] = "Conflict rate above 20%: team_balance pillar should carry more weight.";
        }

        return [
            'distribution' => $distribution,
            'positive_rate' => round($positiveRate, 3),
            'negative_rate' => round($negativeRate, 3),
            'captain_link_rate' => round($captainLinkRate, 3),
            'avg_severity' => round($avgSeverity, 1),
            'rationale' => $rationale,
        ];
    }

    /**
     * Compute suggested weight adjustments based on outcome analysis.
     * Conservative: max ±0.05 per pillar per training cycle.
     */
    private function computeWeightSuggestions(array $current, array $analysis): array
    {
        $suggested = $current;
        $maxDelta = 0.05;

        // If captain-linked outcomes are frequent, boost captain_fit
        if ($analysis['captain_link_rate'] > 0.3) {
            $suggested['captain_fit'] = min(0.40, $suggested['captain_fit'] + $maxDelta);
        }

        // If conflict rate is high, boost team_balance
        $conflictRate = ($analysis['distribution']['conflict_reported'] ?? 0) /
            max(1, array_sum($analysis['distribution']));
        if ($conflictRate > 0.2) {
            $suggested['team_balance'] = min(0.35, $suggested['team_balance'] + $maxDelta);
        }

        // If safety incidents, boost operational_risk
        $safetyRate = ($analysis['distribution']['safety_incident'] ?? 0) /
            max(1, array_sum($analysis['distribution']));
        if ($safetyRate > 0.1) {
            $suggested['operational_risk'] = min(0.40, $suggested['operational_risk'] + $maxDelta);
        }

        // Normalize to sum to 1.0
        $total = array_sum($suggested);
        if ($total > 0) {
            foreach ($suggested as $key => $val) {
                $suggested[$key] = round($val / $total, 3);
            }
        }

        return $suggested;
    }

    /**
     * Compute explainable deltas.
     */
    private function computeDeltas(array $previous, array $suggested): array
    {
        $deltas = [];
        foreach ($suggested as $key => $val) {
            $prev = $previous[$key] ?? $val;
            $delta = round($val - $prev, 4);
            $deltas[$key] = [
                'previous' => $prev,
                'new' => $val,
                'delta' => $delta,
                'direction' => $delta > 0 ? 'increased' : ($delta < 0 ? 'decreased' : 'unchanged'),
            ];
        }
        return $deltas;
    }
}
