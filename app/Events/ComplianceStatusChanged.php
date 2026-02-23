<?php

namespace App\Events;

use App\Models\PoolCandidate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a candidate's certificate expires or compliance status changes.
 * Triggers availability risk re-evaluation for active/upcoming assignments.
 */
class ComplianceStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PoolCandidate $candidate,
        public string $changeType, // 'cert_expired', 'cert_expiring_soon', 'medical_expired'
        public array $details = [],
    ) {}
}
