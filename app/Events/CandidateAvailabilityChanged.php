<?php

namespace App\Events;

use App\Models\PoolCandidate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateAvailabilityChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PoolCandidate $candidate,
        public ?string $previousStatus,
        public string $newStatus,
    ) {}
}
