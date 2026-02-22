<?php

namespace App\Services\Ais;

use App\Services\Ais\Dto\TrackResultDto;
use App\Services\Ais\Dto\VesselStaticDto;
use Carbon\Carbon;

interface AisProviderInterface
{
    public function fetchVesselStatic(string $imo): ?VesselStaticDto;

    public function fetchTrackPoints(string $imo, Carbon $from, Carbon $to): TrackResultDto;
}
