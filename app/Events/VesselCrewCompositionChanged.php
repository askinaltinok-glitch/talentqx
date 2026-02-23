<?php

namespace App\Events;

use App\Models\FleetVessel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when crew composition changes on a vessel (assignment added/removed/changed).
 * Triggers synergy recalculation for existing crew members.
 */
class VesselCrewCompositionChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public FleetVessel $vessel,
        public string $changeType, // 'assignment_added', 'assignment_removed', 'assignment_changed'
        public ?string $candidateId = null,
        public ?string $rankCode = null,
    ) {}
}
