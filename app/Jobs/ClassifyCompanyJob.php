<?php

namespace App\Jobs;

use App\Models\ResearchCompany;
use App\Services\Research\CompanyClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClassifyCompanyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        public ResearchCompany $company,
    ) {}

    public function handle(CompanyClassifier $classifier): void
    {
        $result = $classifier->classify($this->company);

        $this->company->update([
            'classification' => $result,
            'maritime_flag' => $result['is_maritime'],
            'industry' => $result['is_maritime'] ? 'maritime' : ($this->company->industry ?? 'general'),
            'sub_industry' => $result['sub_industry'],
            'enriched_at' => now(),
        ]);

        // Auto-qualify maritime companies
        if ($result['is_maritime'] && $this->company->status === ResearchCompany::STATUS_DISCOVERED) {
            $this->company->update(['status' => ResearchCompany::STATUS_ENRICHED]);
        }

        Log::info('Company classified', [
            'company_id' => $this->company->id,
            'is_maritime' => $result['is_maritime'],
            'confidence' => $result['confidence'],
        ]);
    }
}
