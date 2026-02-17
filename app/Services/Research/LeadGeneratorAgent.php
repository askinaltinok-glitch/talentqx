<?php

namespace App\Services\Research;

use App\Models\ResearchCompany;
use App\Models\ResearchRun;
use App\Services\Mail\MailTriggerService;
use Illuminate\Support\Facades\Log;

class LeadGeneratorAgent implements ResearchAgentInterface
{
    public function getName(): string
    {
        return 'lead_generator';
    }

    /**
     * Query qualified companies with score >= threshold.
     * Push to CRM for each, track leads_created.
     */
    public function run(ResearchRun $run): void
    {
        $scoreThreshold = 50;

        $companies = ResearchCompany::status(ResearchCompany::STATUS_QUALIFIED)
            ->minScore($scoreThreshold)
            ->orderByDesc('hiring_signal_score')
            ->limit(50)
            ->get();

        $leadsCreated = 0;

        foreach ($companies as $company) {
            try {
                $crmCompany = $company->pushToCrm();

                if ($crmCompany) {
                    $leadsCreated++;

                    // Fire new_company trigger for auto-enrollment in welcome sequences
                    try {
                        $lead = $crmCompany->leads()->latest()->first();
                        if ($lead) {
                            $triggerService = app(MailTriggerService::class);
                            $triggerService->fire('new_company', [
                                'lead_id' => $lead->id,
                                'industry_code' => $lead->industry_code,
                                'preferred_language' => $lead->preferred_language ?? 'en',
                            ]);
                        }
                    } catch (\Exception $te) {
                        Log::warning('LeadGeneratorAgent: Trigger failed', ['error' => $te->getMessage()]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('LeadGeneratorAgent: Error pushing company to CRM', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $run->update(['leads_created' => $leadsCreated]);

        Log::info('LeadGeneratorAgent completed', [
            'companies_processed' => $companies->count(),
            'leads_created' => $leadsCreated,
        ]);
    }
}
