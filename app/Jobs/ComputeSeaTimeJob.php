<?php

namespace App\Jobs;

use App\Services\SeaTime\SeaTimeCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComputeSeaTimeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60];
    public int $timeout = 120;

    public function __construct(
        private string $poolCandidateId,
    ) {
        $this->onQueue('default');
    }

    public function handle(SeaTimeCalculator $calculator): void
    {
        $result = $calculator->compute($this->poolCandidateId);

        if ($result) {
            Log::channel('daily')->info('[SeaTime] Computation completed', [
                'candidate_id' => $this->poolCandidateId,
                'total_sea_days' => $result['total_sea_days'],
                'contracts' => $result['total_contracts'],
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('daily')->error('[SeaTime] Computation job failed', [
            'candidate_id' => $this->poolCandidateId,
            'error' => $e->getMessage(),
        ]);
    }
}
