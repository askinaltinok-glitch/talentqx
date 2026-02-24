<?php

namespace App\Jobs;

use App\Services\Trust\CrewReliabilityCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Log;

class RecomputeCriJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public int $tries = 3;
    public array $backoff = [30, 60];
    public int $timeout = 60;

    public function __construct(
        public string $poolCandidateId,
        public string $trigger = 'manual',
    ) {
        $this->onQueue('default');
        $this->captureBrand();
    }

    public function handle(CrewReliabilityCalculator $calculator): void
    {
        $this->setBrandDatabase();
        try {
            $calculator->compute($this->poolCandidateId);

            Log::channel('daily')->info('[CRI] Recomputed', [
                'candidate_id' => $this->poolCandidateId,
                'trigger' => $this->trigger,
            ]);
        } catch (\Throwable $e) {
            Log::channel('daily')->error('[CRI] Recompute failed', [
                'candidate_id' => $this->poolCandidateId,
                'trigger' => $this->trigger,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
