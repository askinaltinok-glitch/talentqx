<?php

namespace App\Jobs;

use App\Services\Stability\StabilityRiskEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Log;

class ComputeStabilityRiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public int $tries = 3;
    public array $backoff = [30, 60];
    public int $timeout = 120;

    public function __construct(
        private string $poolCandidateId,
    ) {
        $this->onQueue('default');
        $this->captureBrand();
    }

    public function handle(StabilityRiskEngine $engine): void
    {
        $this->setBrandDatabase();
        $result = $engine->compute($this->poolCandidateId);

        if ($result) {
            Log::channel('daily')->info('[StabilityRisk] Computed', [
                'candidate_id' => $this->poolCandidateId,
                'stability_index' => $result['stability_index'],
                'risk_score' => $result['risk_score'],
                'risk_tier' => $result['risk_tier'],
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('daily')->error('[StabilityRisk] Job failed', [
            'candidate_id' => $this->poolCandidateId,
            'error' => $e->getMessage(),
        ]);
    }
}
