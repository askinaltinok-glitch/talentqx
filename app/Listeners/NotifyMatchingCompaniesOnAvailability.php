<?php

namespace App\Listeners;

use App\Events\CandidateAvailabilityChanged;
use App\Models\FleetVessel;
use App\Models\VesselManningRequirement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyMatchingCompaniesOnAvailability implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(CandidateAvailabilityChanged $event): void
    {
        $candidate = $event->candidate;

        // Only process meaningful transitions
        if (!in_array($event->newStatus, ['available', 'soon_available'])) {
            return;
        }

        Log::info('CandidateAvailabilityChanged: processing', [
            'candidate_id' => $candidate->id,
            'from' => $event->previousStatus,
            'to' => $event->newStatus,
        ]);

        // Find fleet vessels with open manning gaps
        // This is a best-effort match — we don't filter by rank since PoolCandidate
        // doesn't have a rank field. Companies will see updated recommendations next
        // time they view crew planning.
        $vesselsWithGaps = FleetVessel::where('status', 'active')
            ->whereHas('manningRequirements')
            ->with('company:id,name')
            ->limit(50)
            ->get();

        $notifiedCompanies = [];

        foreach ($vesselsWithGaps as $vessel) {
            $companyId = $vessel->company_id;
            if (in_array($companyId, $notifiedCompanies)) {
                continue;
            }

            // Check if this vessel has actual open gaps
            $requirements = $vessel->manningRequirements()->get();
            $activeCount = $vessel->assignments()
                ->whereIn('status', ['planned', 'onboard'])
                ->count();

            $totalRequired = $requirements->sum('required_count');
            if ($activeCount >= $totalRequired) {
                continue;
            }

            // Log the notification (actual email sending can be added later)
            Log::info('CandidateAvailabilityChanged: company notifiable', [
                'candidate_id' => $candidate->id,
                'company_id' => $companyId,
                'company_name' => $vessel->company?->name,
                'vessel_id' => $vessel->id,
                'vessel_name' => $vessel->name,
            ]);

            $notifiedCompanies[] = $companyId;
        }
    }
}
