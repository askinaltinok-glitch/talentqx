<?php

namespace App\Services\Maritime;

/**
 * Centralized calibration config resolver for all maritime decision thresholds.
 *
 * Follows the StabilityConfig pattern:
 *   - Constructor accepts ?string $fleetType
 *   - get() checks fleet profiles first, falls back to base config
 *   - No DB table for v1 — config-only (DB deferred to v2)
 *   - Precedence: fleet profile → base config
 */
class CalibrationConfig
{
    private array $profiles;
    private array $maritime;
    private ?string $fleetType;

    public function __construct(?string $fleetType = null)
    {
        $this->maritime  = config('maritime', []);
        $this->profiles  = $this->maritime['calibration']['fleet_profiles'] ?? [];
        $this->fleetType = $fleetType;
    }

    // ── Decision Thresholds ─────────────────────────

    public function competencyReviewThreshold(): int
    {
        $v = $this->getOverride('competency', 'review_threshold')
            ?? $this->maritime['competency']['review_threshold']
            ?? 45;
        return max(20, min(80, (int) $v));
    }

    public function rejectOnCriticalFlag(): bool
    {
        return (bool) ($this->getOverride('competency', 'reject_on_critical_flag')
            ?? $this->maritime['competency']['reject_on_critical_flag']
            ?? true);
    }

    public function technicalReviewBelow(): float
    {
        $v = $this->getOverride('exec_summary_thresholds', 'technical_review_below')
            ?? $this->maritime['exec_summary_thresholds']['technical_review_below']
            ?? 0.4;
        return max(0.1, min(0.8, (float) $v));
    }

    public function isCorrelationEnabled(): bool
    {
        return (bool) ($this->getOverride('features', 'correlation_v1')
            ?? $this->maritime['correlation_v1']
            ?? false);
    }

    // ── Correlation Thresholds ──────────────────────

    public function correlationThresholds(): array
    {
        $base = $this->maritime['correlation'] ?? [];
        $override = $this->profiles[$this->fleetType]['correlation'] ?? [];
        return array_merge($base, $override);
    }

    // ── Competency Dimension Weights ────────────────

    public function competencyDimensionWeights(): ?array
    {
        $override = $this->profiles[$this->fleetType]['competency']['dimension_weights'] ?? null;
        if (!$override) {
            return null; // null = use defaults from competency config
        }

        // Merge: fleet overrides specific dimensions, rest from base
        $base = $this->maritime['competency']['dimension_weights'] ?? [];
        $merged = array_merge($base, $override);

        // Guardrail: normalize to sum=1.0
        $sum = array_sum($merged);
        if ($sum > 0 && (abs($sum - 1.0) > 0.05)) {
            $merged = array_map(fn($w) => round($w / $sum, 4), $merged);
        }

        return $merged;
    }

    // ── Predictive Risk ─────────────────────────────

    /**
     * Return merged predictive config: fleet profile overrides + base config.
     * Used by PredictiveRiskEngine and RiskTrendAnalyzer.
     */
    public function predictiveConfig(): array
    {
        $base = $this->maritime['predictive'] ?? [];
        $override = $this->profiles[$this->fleetType]['predictive'] ?? [];
        return array_merge($base, $override);
    }

    // ── Internal ────────────────────────────────────

    private function getOverride(string $section, string $key): mixed
    {
        if (!$this->fleetType) {
            return null;
        }
        return $this->profiles[$this->fleetType][$section][$key] ?? null;
    }

    public function getFleetType(): ?string
    {
        return $this->fleetType;
    }

    public function withFleet(?string $fleet): self
    {
        return new self($fleet);
    }
}
