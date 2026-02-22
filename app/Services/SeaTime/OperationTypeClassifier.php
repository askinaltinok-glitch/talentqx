<?php

namespace App\Services\SeaTime;

use App\Models\CandidateContract;

class OperationTypeClassifier
{
    private const RIVER_TYPES = [
        CandidateContract::VESSEL_RIVER,
    ];

    public static function classify(?string $vesselType): string
    {
        if ($vesselType && in_array($vesselType, self::RIVER_TYPES, true)) {
            return 'river';
        }

        return 'sea';
    }
}
