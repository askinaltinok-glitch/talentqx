<?php

namespace App\Services\Ais;

use App\Services\Ais\Dto\TrackResultDto;
use App\Services\Ais\Dto\VesselStaticDto;
use Carbon\Carbon;

class HttpAisProvider implements AisProviderInterface
{
    public function fetchVesselStatic(string $imo): ?VesselStaticDto
    {
        throw new AisProviderNotConfiguredException('HTTP AIS provider is not yet configured.');
    }

    public function fetchTrackPoints(string $imo, Carbon $from, Carbon $to): TrackResultDto
    {
        throw new AisProviderNotConfiguredException('HTTP AIS provider is not yet configured.');
    }
}
