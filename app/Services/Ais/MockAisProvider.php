<?php

namespace App\Services\Ais;

use App\Models\Vessel;
use App\Services\Ais\Dto\TrackResultDto;
use App\Services\Ais\Dto\VesselStaticDto;
use Carbon\Carbon;

class MockAisProvider implements AisProviderInterface
{
    private const REGIONS = [
        'Mediterranean Sea',
        'Black Sea',
        'North Sea',
        'Baltic Sea',
        'Arabian Gulf',
        'South China Sea',
        'Indian Ocean',
        'Atlantic Ocean',
        'Pacific Ocean',
        'Caribbean Sea',
    ];

    public function fetchVesselStatic(string $imo): ?VesselStaticDto
    {
        $vessel = Vessel::where('imo', $imo)->first();

        if (!$vessel) {
            return null;
        }

        return new VesselStaticDto(
            imo: $vessel->imo,
            name: $vessel->name,
            mmsi: $vessel->mmsi,
            type: $vessel->type,
            flag: $vessel->flag,
            dwt: $vessel->dwt,
            gt: $vessel->gt,
            lengthM: $vessel->length_m,
            beamM: $vessel->beam_m,
            yearBuilt: $vessel->year_built,
            lastSeenAt: $vessel->last_seen_at ?? now(),
        );
    }

    public function fetchTrackPoints(string $imo, Carbon $from, Carbon $to): TrackResultDto
    {
        $seed = crc32($imo);
        mt_srand($seed);

        $days = max((int) $from->diffInDays($to), 1);

        $pointsPerDay = mt_rand(3, 8);
        $totalPoints = $days * $pointsPerDay;

        // 60-100% coverage, deterministic per IMO
        $coveragePct = 0.6 + ($seed % 40) / 100;
        $daysCovered = (int) floor($days * $coveragePct);
        $dataQuality = round($daysCovered / max($days, 1), 2);

        // Pick a region based on IMO hash
        $regionIdx = $seed % count(self::REGIONS);
        $regionName = self::REGIONS[$regionIdx];

        $areaClusters = [
            [
                'name' => $regionName,
                'points' => $totalPoints,
                'pctTime' => 1.0,
            ],
        ];

        // Deterministic first/last seen within the contract period
        $firstSeen = $from->copy()->addDays(mt_rand(0, max((int) floor($days * 0.1), 1)));
        $lastSeen = $to->copy()->subDays(mt_rand(0, max((int) floor($days * 0.1), 1)));

        // Reset random seed
        mt_srand();

        return new TrackResultDto(
            totalPoints: $totalPoints,
            daysCovered: $daysCovered,
            dataQuality: $dataQuality,
            areaClusters: $areaClusters,
            firstSeen: $firstSeen,
            lastSeen: $lastSeen,
        );
    }
}
