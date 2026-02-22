<?php

namespace App\Services\Ais\Dto;

use Carbon\Carbon;

class VesselStaticDto
{
    public string $imo;
    public ?string $mmsi;
    public string $name;
    public ?string $type;
    public ?string $flag;
    public ?int $dwt;
    public ?int $gt;
    public ?float $lengthM;
    public ?float $beamM;
    public ?int $yearBuilt;
    public ?Carbon $lastSeenAt;

    public function __construct(
        string $imo,
        string $name,
        ?string $mmsi = null,
        ?string $type = null,
        ?string $flag = null,
        ?int $dwt = null,
        ?int $gt = null,
        ?float $lengthM = null,
        ?float $beamM = null,
        ?int $yearBuilt = null,
        ?Carbon $lastSeenAt = null,
    ) {
        $this->imo = $imo;
        $this->name = $name;
        $this->mmsi = $mmsi;
        $this->type = $type;
        $this->flag = $flag;
        $this->dwt = $dwt;
        $this->gt = $gt;
        $this->lengthM = $lengthM;
        $this->beamM = $beamM;
        $this->yearBuilt = $yearBuilt;
        $this->lastSeenAt = $lastSeenAt;
    }
}
