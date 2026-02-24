<?php

namespace App\Jobs;

use App\Services\Decision\PredictiveRiskEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Log;

class ComputePredictiveRiskJob implements ShouldQueue
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

    public function handle(PredictiveRiskEngine $engine): void
    {
        $this->setBrandDatabase();
        $result = $engine->compute($this->poolCandidateId);

        if ($result) {
            Log::channel('daily')->info('[PredictiveRisk] Computed', [
                'candidate_id' => $this->poolCandidateId,
                'predictive_risk_index' => $result['predictive_risk_index'],
                'predictive_tier' => $result['predictive_tier'],
                'trend_direction' => $result['trend_direction'],
                'pattern_count' => count($result['triggered_patterns']),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('daily')->error('[PredictiveRisk] Job failed', [
            'candidate_id' => $this->poolCandidateId,
            'error' => $e->getMessage(),
        ]);
    }
}
