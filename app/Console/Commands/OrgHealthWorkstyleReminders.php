<?php

namespace App\Console\Commands;

use App\Models\OrgAssessment;
use App\Models\OrgEmployeeConsent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrgHealthWorkstyleReminders extends Command
{
    protected $signature = 'orghealth:workstyle-reminders';
    protected $description = 'Send WorkStyle yearly reminder emails (30d and 7d before due).';

    public function handle(): int
    {
        // MVP: just output candidates; integrate Mail later.
        $today = Carbon::today();

        $consentedEmployees = OrgEmployeeConsent::query()
            ->where('consent_version', 'orghealth_v1')
            ->whereNotNull('consented_at')
            ->whereNull('withdrawn_at')
            ->get(['tenant_id','employee_id']);

        $count = 0;

        foreach ($consentedEmployees as $c) {
            $latest = OrgAssessment::query()
                ->where('tenant_id', $c->tenant_id)
                ->where('employee_id', $c->employee_id)
                ->where('status', 'completed')
                ->whereNotNull('next_due_at')
                ->orderByDesc('completed_at')
                ->first();

            if (!$latest) continue;

            $due = Carbon::parse($latest->next_due_at)->startOfDay();
            $diff = $today->diffInDays($due, false);

            if (in_array($diff, [30, 7], true)) {
                // TODO: send email (MVP logs only)
                $count++;
                $this->line("Reminder candidate: tenant={$c->tenant_id} employee={$c->employee_id} due={$due->toDateString()} in {$diff}d");
            }
        }

        $this->info("Total reminder candidates: {$count}");
        return self::SUCCESS;
    }
}
