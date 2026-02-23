<?php

namespace App\Services\Fleet;

interface VesselRegistryProvider
{
    /**
     * Lookup vessel info by IMO number.
     * Returns associative array [name, flag, vessel_type, year_built, dwt, gt] or null.
     */
    public function lookupByImo(string $imo): ?array;
}
