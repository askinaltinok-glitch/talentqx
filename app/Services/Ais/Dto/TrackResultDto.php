<?php

namespace App\Services\Ais\Dto;

use Carbon\Carbon;

class TrackResultDto
{
    public int $totalPoints;
    public int $daysCovered;
    public float $dataQuality;      // 0.0–1.0
    /** @var array<array{name: string, points: int, pctTime: float}> */
    public array $areaClusters;
    public ?Carbon $firstSeen;
    public ?Carbon $lastSeen;

    public function __construct(
        int $totalPoints,
        int $daysCovered,
        float $dataQuality,
        array $areaClusters = [],
        ?Carbon $firstSeen = null,
        ?Carbon $lastSeen = null,
    ) {
        $this->totalPoints = $totalPoints;
        $this->daysCovered = $daysCovered;
        $this->dataQuality = $dataQuality;
        $this->areaClusters = $areaClusters;
        $this->firstSeen = $firstSeen;
        $this->lastSeen = $lastSeen;
    }
}
