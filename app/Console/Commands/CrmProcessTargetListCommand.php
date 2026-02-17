<?php

namespace App\Console\Commands;

use App\Models\ResearchCompany;
use App\Services\Mail\MailTriggerService;
use App\Services\Research\ResearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CrmProcessTargetListCommand extends Command
{
    protected $signature = 'crm:process-target-list {--dry-run : Show what would happen without making changes}';
    protected $description = 'Process target list companies: classify, enrich, push to CRM, and enroll in sequences';

    public function handle(ResearchService $researchService, MailTriggerService $triggerService): int
    {
        $companies = ResearchCompany::where('target_list', true)
            ->whereIn('status', [
                ResearchCompany::STATUS_DISCOVERED,
                ResearchCompany::STATUS_ENRICHED,
                ResearchCompany::STATUS_QUALIFIED,
            ])
            ->orderByDesc('hiring_signal_score')
            ->get();

        $this->info("Found {$companies->count()} target companies to process.");

        if ($companies->isEmpty()) {
            return self::SUCCESS;
        }

        $dryRun = $this->option('dry-run');
        $pushed = 0;
        $triggered = 0;

        foreach ($companies as $company) {
            $this->line("Processing: {$company->name} ({$company->domain}) — status: {$company->status}");

            if ($dryRun) {
                continue;
            }

            try {
                // Step 1: Classify if not yet enriched
                if ($company->status === ResearchCompany::STATUS_DISCOVERED) {
                    $researchService->classifyCompany($company);
                    $company->refresh();
                }

                // Step 2: Auto-qualify if maritime or score >= 50
                if (in_array($company->status, [ResearchCompany::STATUS_DISCOVERED, ResearchCompany::STATUS_ENRICHED])) {
                    $company->update([
                        'status' => ResearchCompany::STATUS_QUALIFIED,
                        'hiring_signal_score' => max($company->hiring_signal_score, 60),
                    ]);
                }

                // Step 3: Push to CRM
                if ($company->status === ResearchCompany::STATUS_QUALIFIED) {
                    $crmCompany = $company->pushToCrm();

                    if ($crmCompany) {
                        $pushed++;

                        // Step 4: Fire new_company trigger for auto-enrollment
                        $lead = $crmCompany->leads()->latest()->first();
                        if ($lead) {
                            $fires = $triggerService->fire('new_company', [
                                'lead_id' => $lead->id,
                                'industry_code' => $lead->industry_code,
                                'preferred_language' => $lead->preferred_language ?? 'en',
                            ]);
                            if ($fires > 0) {
                                $triggered++;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->warn("  Error: {$e->getMessage()}");
                Log::warning('CrmProcessTargetList: Error', [
                    'company' => $company->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. Pushed: {$pushed}, Triggers fired: {$triggered}");
        return self::SUCCESS;
    }
}
