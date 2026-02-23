<?php

namespace App\Services;

use App\Models\TenantFeatureFlag;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService
{
    public function enabled(string $tenantId, string $featureKey): bool
    {
        if (!$this->globalEnabled($featureKey)) {
            return false;
        }

        $row = $this->row($tenantId, $featureKey);
        return (bool) ($row['is_enabled'] ?? false);
    }

    public function payload(string $tenantId, string $featureKey): array
    {
        if (!$this->globalEnabled($featureKey)) {
            return [];
        }

        $row = $this->row($tenantId, $featureKey);
        return (array) ($row['payload'] ?? []);
    }

    public function forget(string $tenantId, string $featureKey): void
    {
        Cache::forget($this->cacheKey($tenantId, $featureKey));
    }

    private function globalEnabled(string $featureKey): bool
    {
        return match ($featureKey) {
            'crew_synergy_engine_v2' => (bool) config('features.crew_synergy_engine_v2_global', false),
            default => false,
        };
    }

    private function row(string $tenantId, string $featureKey): array
    {
        $key = $this->cacheKey($tenantId, $featureKey);

        return Cache::remember($key, now()->addSeconds(120), function () use ($tenantId, $featureKey) {
            $ff = TenantFeatureFlag::query()
                ->where('tenant_id', $tenantId)
                ->where('feature_key', $featureKey)
                ->first();

            if (!$ff) return [];

            return [
                'is_enabled' => $ff->is_enabled,
                'payload' => $ff->payload ?? [],
                'enabled_at' => optional($ff->enabled_at)->toISOString(),
                'enabled_by' => $ff->enabled_by,
            ];
        });
    }

    private function cacheKey(string $tenantId, string $featureKey): string
    {
        return "tenant:{$tenantId}:feature:{$featureKey}";
    }
}
