<?php

namespace App\Services\Research;

use App\Models\ResearchCompany;
use App\Models\ResearchRun;
use Illuminate\Support\Facades\Log;

class MaritimeDiscoveryAgent implements ResearchAgentInterface
{
    private CompanyClassifier $classifier;

    public function __construct(CompanyClassifier $classifier)
    {
        $this->classifier = $classifier;
    }

    public function getName(): string
    {
        return 'maritime_discovery';
    }

    /**
     * Classify unclassified companies via CompanyClassifier.
     * Sets maritime_flag, industry, sub_industry.
     * Marks as qualified if maritime + score >= threshold.
     */
    public function run(ResearchRun $run): void
    {
        $scoreThreshold = 40;

        $companies = ResearchCompany::whereNull('classification')
            ->whereIn('status', [
                ResearchCompany::STATUS_DISCOVERED,
                ResearchCompany::STATUS_ENRICHED,
            ])
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        $companiesFound = 0;

        foreach ($companies as $company) {
            try {
                $result = $this->classifier->classify($company);

                $company->update([
                    'classification' => $result,
                    'maritime_flag' => $result['is_maritime'],
                    'industry' => $result['is_maritime'] ? 'maritime' : ($company->industry ?? 'general'),
                    'sub_industry' => $result['sub_industry'],
                ]);

                // Auto-qualify maritime companies with sufficient score
                if ($result['is_maritime'] && $company->hiring_signal_score >= $scoreThreshold) {
                    $company->update(['status' => ResearchCompany::STATUS_QUALIFIED]);
                    $companiesFound++;
                } elseif ($result['is_maritime']) {
                    // Maritime but low score - mark enriched
                    if ($company->status === ResearchCompany::STATUS_DISCOVERED) {
                        $company->update(['status' => ResearchCompany::STATUS_ENRICHED]);
                    }
                    $companiesFound++;
                }
            } catch (\Exception $e) {
                Log::warning('MaritimeDiscoveryAgent: Error classifying company', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $run->update(['companies_found' => $companiesFound]);

        Log::info('MaritimeDiscoveryAgent completed', [
            'companies_classified' => $companies->count(),
            'maritime_found' => $companiesFound,
        ]);
    }
}
