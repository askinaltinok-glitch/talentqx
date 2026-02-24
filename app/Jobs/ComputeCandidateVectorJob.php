<?php

namespace App\Jobs;

use App\Services\Maritime\CandidateVectorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Log;

class ComputeCandidateVectorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        public string $candidateId,
        public ?string $trigger = null,
    ) {
        $this->onQueue('default');
        $this->captureBrand();
    }

    public function handle(CandidateVectorService $service): void
    {
        $this->setBrandDatabase();
        $result = $service->computeVector($this->candidateId);

        if ($result) {
            Log::info('ComputeCandidateVectorJob: vector computed', [
                'candidate_id' => $this->candidateId,
                'composite' => $result->composite_score,
                'trigger' => $this->trigger,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('ComputeCandidateVectorJob: failed', [
            'candidate_id' => $this->candidateId,
            'trigger' => $this->trigger,
            'error' => $e->getMessage(),
        ]);
    }
}
