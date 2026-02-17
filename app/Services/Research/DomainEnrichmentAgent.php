<?php

namespace App\Services\Research;

use App\Models\ResearchCompany;
use App\Models\ResearchRun;
use Illuminate\Support\Facades\Log;

class DomainEnrichmentAgent implements ResearchAgentInterface
{
    private CompanyClassifier $classifier;

    public function __construct(CompanyClassifier $classifier)
    {
        $this->classifier = $classifier;
    }

    public function getName(): string
    {
        return 'domain_enrichment';
    }

    /**
     * Process discovered status companies, classify via AI, update to enriched.
     */
    public function run(ResearchRun $run): void
    {
        $companies = ResearchCompany::status(ResearchCompany::STATUS_DISCOVERED)
            ->whereNull('classification')
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        $enriched = 0;

        foreach ($companies as $company) {
            try {
                $result = $this->classifier->classify($company);

                $company->update([
                    'classification' => $result,
                    'maritime_flag' => $result['is_maritime'],
                    'industry' => $result['is_maritime'] ? 'maritime' : ($company->industry ?? 'general'),
                    'sub_industry' => $result['sub_industry'],
                    'status' => ResearchCompany::STATUS_ENRICHED,
                    'enriched_at' => now(),
                ]);

                $enriched++;
            } catch (\Exception $e) {
                Log::warning('DomainEnrichmentAgent: Error enriching company', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $run->update(['companies_found' => $enriched]);

        Log::info('DomainEnrichmentAgent completed', [
            'companies_processed' => $companies->count(),
            'enriched' => $enriched,
        ]);
    }
}
