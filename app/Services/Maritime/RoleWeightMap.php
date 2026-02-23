<?php

namespace App\Services\Maritime;

use Illuminate\Support\Arr;

class RoleWeightMap
{
    public function resolve(string $roleKey, array $tenantOverride = []): array
    {
        // 1) tenant override (payload) role-based
        $tenantRole = Arr::get($tenantOverride, "roles.{$roleKey}.weights");
        if (is_array($tenantRole) && !empty($tenantRole)) {
            return $this->normalize($tenantRole);
        }

        // 2) global config role weights
        $roleWeights = config("maritime.synergy_weights.roles.{$roleKey}.weights");
        if (is_array($roleWeights) && !empty($roleWeights)) {
            return $this->normalize($roleWeights);
        }

        // 3) defaults
        $defaults = (array) config('maritime.synergy_weights.defaults.weights', []);
        return $this->normalize($defaults);
    }

    public function computeWeightedScore(array $traitScores01, array $weights): float
    {
        $missingDefault = (float) config('maritime.synergy_weights.missing_dimension_default_score', 0.5);

        $sum = 0.0;
        foreach ($weights as $dim => $w) {
            $score = array_key_exists($dim, $traitScores01)
                ? (float) $traitScores01[$dim]
                : $missingDefault;

            if ($score < 0) $score = 0;
            if ($score > 1) $score = 1;

            $sum += $score * (float) $w;
        }

        if ($sum < 0) $sum = 0;
        if ($sum > 1) $sum = 1;

        return $sum;
    }

    private function normalize(array $weights): array
    {
        $sumTo1 = (bool) config('maritime.synergy_weights.normalization.sum_to_1', true);
        if (!$sumTo1) return $weights;

        $total = 0.0;
        foreach ($weights as $k => $v) {
            $val = (float) $v;
            if ($val < 0) $val = 0;
            $weights[$k] = $val;
            $total += $val;
        }

        if ($total <= 0) {
            return (array) config('maritime.synergy_weights.defaults.weights', []);
        }

        foreach ($weights as $k => $v) {
            $weights[$k] = $v / $total;
        }

        return $weights;
    }
}
