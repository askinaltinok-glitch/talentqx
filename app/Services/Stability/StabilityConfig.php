<?php

namespace App\Services\Stability;

/**
 * Centralized config resolver for Stability & Risk Engine v1.1.
 *
 * Reads from config('maritime.stability') with optional fleet profile overrides.
 * No hardcoded values — everything flows through this class.
 */
class StabilityConfig
{
    private array $config;
    private ?string $fleetType;

    public function __construct(?string $fleetType = null)
    {
        $this->config = config('maritime.stability', []);
        $this->fleetType = $fleetType;
    }

    /**
     * Get a config value, with fleet profile override support.
     * Fleet profile values override base values.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Check fleet profile first
        if ($this->fleetType) {
            $fleetValue = $this->config['fleet_profiles'][$this->fleetType][$key] ?? null;
            if ($fleetValue !== null) {
                return $fleetValue;
            }
        }

        return $this->config[$key] ?? $default;
    }

    /**
     * Get a nested config value using dot notation within a key.
     */
    public function getNested(string $key, string $subKey, mixed $default = null): mixed
    {
        // Fleet override for nested arrays (e.g. factor_weights.short_ratio)
        if ($this->fleetType) {
            $fleetSection = $this->config['fleet_profiles'][$this->fleetType][$key] ?? null;
            if (is_array($fleetSection) && isset($fleetSection[$subKey])) {
                return $fleetSection[$subKey];
            }
        }

        $section = $this->config[$key] ?? [];
        return $section[$subKey] ?? $default;
    }

    /**
     * Get factor weights, merging fleet overrides into base weights.
     */
    public function getFactorWeights(): array
    {
        $base = $this->config['factor_weights'] ?? [];

        if ($this->fleetType) {
            $fleetWeights = $this->config['fleet_profiles'][$this->fleetType]['factor_weights'] ?? [];
            $base = array_merge($base, $fleetWeights);
        }

        return $base;
    }

    /**
     * Get risk tier thresholds, with fleet override.
     */
    public function getRiskTierThresholds(): array
    {
        if ($this->fleetType) {
            $fleetTiers = $this->config['fleet_profiles'][$this->fleetType]['risk_tier_thresholds'] ?? null;
            if ($fleetTiers) {
                return $fleetTiers;
            }
        }

        return $this->config['risk_tier_thresholds'] ?? [
            'critical' => 0.75,
            'high' => 0.50,
            'medium' => 0.25,
        ];
    }

    /**
     * Get the short contract threshold for a specific rank.
     * Falls back to global default if rank not in rank-specific map.
     */
    public function getShortContractMonths(?string $canonicalRank = null): float
    {
        if ($canonicalRank) {
            $byRank = $this->config['short_contract_months_by_rank'] ?? [];
            if (isset($byRank[$canonicalRank])) {
                return (float) $byRank[$canonicalRank];
            }
        }

        return (float) $this->get('short_contract_months', 6);
    }

    /**
     * Get temporal decay config.
     */
    public function getTemporalDecay(): array
    {
        return $this->config['temporal_decay'] ?? [
            'recent_months' => 36,
            'old_months' => 60,
            'recent_weight' => 1.5,
            'old_weight' => 0.5,
            'default_weight' => 1.0,
        ];
    }

    /**
     * Get vessel diversity config.
     */
    public function getVesselDiversity(): array
    {
        return $this->config['vessel_diversity'] ?? [
            'min_types_for_bonus' => 2,
            'max_types_for_bonus' => 5,
            'min_tenure_months' => 6,
            'max_score' => 1.0,
        ];
    }

    public function getFleetType(): ?string
    {
        return $this->fleetType;
    }

    /**
     * Create a new instance with a specific fleet type.
     */
    public function withFleet(?string $fleetType): self
    {
        return new self($fleetType);
    }
}
