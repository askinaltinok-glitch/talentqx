<?php

namespace App\Services\Fleet;

use App\Models\VesselRegistryCache;

class ManualRegistryProvider implements VesselRegistryProvider
{
    public function lookupByImo(string $imo): ?array
    {
        $cached = VesselRegistryCache::where('imo', $imo)->first();
        if (!$cached) {
            return null;
        }

        return [
            'imo' => $cached->imo,
            'name' => $cached->name,
            'flag' => $cached->flag,
            'vessel_type' => $cached->vessel_type,
            'year_built' => $cached->year_built,
            'dwt' => $cached->dwt,
            'gt' => $cached->gt,
        ];
    }

    /**
     * Upsert a vessel into the registry cache (source=manual).
     */
    public function upsertFromManual(string $imo, array $data): VesselRegistryCache
    {
        return VesselRegistryCache::updateOrCreate(
            ['imo' => $imo],
            array_merge(
                array_filter($data, fn($v) => $v !== null),
                ['source' => 'manual', 'updated_at' => now()]
            )
        );
    }
}
