<?php

namespace App\Services\Ais;

use App\Models\CandidateContract;

class VesselTypeNormalizer
{
    private const MAP = [
        // Bulk carriers
        'bulk carrier'      => CandidateContract::VESSEL_BULK_CARRIER,
        'bulk'              => CandidateContract::VESSEL_BULK_CARRIER,
        'ore carrier'       => CandidateContract::VESSEL_BULK_CARRIER,
        'ore/bulk'          => CandidateContract::VESSEL_BULK_CARRIER,

        // Tankers
        'tanker'            => CandidateContract::VESSEL_TANKER,
        'crude oil tanker'  => CandidateContract::VESSEL_TANKER,
        'oil tanker'        => CandidateContract::VESSEL_TANKER,
        'product tanker'    => CandidateContract::VESSEL_TANKER,

        // Container
        'container'         => CandidateContract::VESSEL_CONTAINER,
        'container ship'    => CandidateContract::VESSEL_CONTAINER,
        'containership'     => CandidateContract::VESSEL_CONTAINER,

        // General cargo
        'general cargo'     => CandidateContract::VESSEL_GENERAL_CARGO,
        'cargo'             => CandidateContract::VESSEL_GENERAL_CARGO,
        'multi-purpose'     => CandidateContract::VESSEL_GENERAL_CARGO,
        'multipurpose'      => CandidateContract::VESSEL_GENERAL_CARGO,

        // Ro-Ro
        'ro-ro'             => CandidateContract::VESSEL_RORO,
        'roro'              => CandidateContract::VESSEL_RORO,
        'ro-ro cargo'       => CandidateContract::VESSEL_RORO,
        'vehicles carrier'  => CandidateContract::VESSEL_RORO,

        // Passenger
        'passenger'         => CandidateContract::VESSEL_PASSENGER,
        'passenger ship'    => CandidateContract::VESSEL_PASSENGER,
        'cruise'            => CandidateContract::VESSEL_PASSENGER,
        'ferry'             => CandidateContract::VESSEL_PASSENGER,

        // Offshore
        'offshore'          => CandidateContract::VESSEL_OFFSHORE,
        'offshore supply'   => CandidateContract::VESSEL_OFFSHORE,
        'platform supply'   => CandidateContract::VESSEL_OFFSHORE,
        'anchor handling'   => CandidateContract::VESSEL_OFFSHORE,
        'fpso'              => CandidateContract::VESSEL_OFFSHORE,
        'drillship'         => CandidateContract::VESSEL_OFFSHORE,

        // LNG/LPG
        'lng'               => CandidateContract::VESSEL_LNG_LPG,
        'lpg'               => CandidateContract::VESSEL_LNG_LPG,
        'lng carrier'       => CandidateContract::VESSEL_LNG_LPG,
        'lpg carrier'       => CandidateContract::VESSEL_LNG_LPG,
        'gas carrier'       => CandidateContract::VESSEL_LNG_LPG,
        'lng/lpg'           => CandidateContract::VESSEL_LNG_LPG,

        // Chemical
        'chemical'          => CandidateContract::VESSEL_CHEMICAL,
        'chemical tanker'   => CandidateContract::VESSEL_CHEMICAL,
        'chemical/oil'      => CandidateContract::VESSEL_CHEMICAL,

        // Car carrier
        'car carrier'       => CandidateContract::VESSEL_CAR_CARRIER,
        'pctc'              => CandidateContract::VESSEL_CAR_CARRIER,
        'vehicle carrier'   => CandidateContract::VESSEL_CAR_CARRIER,
    ];

    /**
     * Close-match pairs: types that are "related" but not exact.
     */
    private const CLOSE_PAIRS = [
        CandidateContract::VESSEL_TANKER    => [CandidateContract::VESSEL_CHEMICAL, CandidateContract::VESSEL_LNG_LPG],
        CandidateContract::VESSEL_CHEMICAL  => [CandidateContract::VESSEL_TANKER, CandidateContract::VESSEL_LNG_LPG],
        CandidateContract::VESSEL_LNG_LPG   => [CandidateContract::VESSEL_TANKER, CandidateContract::VESSEL_CHEMICAL],
        CandidateContract::VESSEL_RORO      => [CandidateContract::VESSEL_CAR_CARRIER],
        CandidateContract::VESSEL_CAR_CARRIER => [CandidateContract::VESSEL_RORO],
        CandidateContract::VESSEL_GENERAL_CARGO => [CandidateContract::VESSEL_BULK_CARRIER, CandidateContract::VESSEL_CONTAINER],
    ];

    public static function normalize(?string $raw): string
    {
        if (!$raw) {
            return CandidateContract::VESSEL_OTHER;
        }

        $key = strtolower(trim($raw));

        return self::MAP[$key] ?? CandidateContract::VESSEL_OTHER;
    }

    /**
     * Check if two normalized types are a close match.
     */
    public static function isCloseMatch(string $typeA, string $typeB): bool
    {
        if ($typeA === $typeB) {
            return true;
        }

        return in_array($typeB, self::CLOSE_PAIRS[$typeA] ?? [], true);
    }
}
