<?php

namespace App\Events;

use App\Models\PoolCandidate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a candidate's contract_end_estimate is set or changed.
 * Triggers future pool recalculation and availability re-evaluation.
 */
class CandidateContractUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PoolCandidate $candidate,
        public ?string $previousEndDate,
        public ?string $newEndDate,
    ) {}
}
