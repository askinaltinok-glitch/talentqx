<?php

namespace App\Console\Commands;

use App\Models\CrmLead;
use App\Services\Mail\MailTriggerService;
use Illuminate\Console\Command;

class CrmCheckStaleLeadsCommand extends Command
{
    protected $signature = 'crm:check-stale-leads';
    protected $description = 'Check for leads with no recent activity and fire no_reply triggers';

    public function handle(MailTriggerService $triggerService): int
    {
        $thresholds = [3, 10]; // Days since last activity
        $total = 0;

        foreach ($thresholds as $days) {
            $staleLeads = CrmLead::active()
                ->where(function ($q) use ($days) {
                    $q->where('last_activity_at', '<=', now()->subDays($days))
                      ->orWhereNull('last_activity_at');
                })
                ->where('last_contacted_at', '<=', now()->subDays($days))
                ->whereNotNull('last_contacted_at')
                ->get();

            foreach ($staleLeads as $lead) {
                $daysSince = $lead->last_activity_at
                    ? (int) now()->diffInDays($lead->last_activity_at)
                    : $days;

                // Only fire if days match threshold (avoid double-firing)
                if ($daysSince >= $days && $daysSince < $days + 6) {
                    $fired = $triggerService->fire('no_reply', [
                        'lead_id' => $lead->id,
                        'industry_code' => $lead->industry_code,
                        'preferred_language' => $lead->preferred_language,
                        'days_since_activity' => $daysSince,
                        'stage' => $lead->stage,
                    ]);

                    if ($fired > 0) {
                        $total++;
                    }
                }
            }
        }

        $this->info("Processed {$total} stale lead trigger(s).");
        return self::SUCCESS;
    }
}
