<?php

namespace App\Jobs;

use App\Services\Compliance\CompliancePackEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComputeCompliancePackJob implements ShouldQueue
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

    public function handle(CompliancePackEngine $engine): void
    {
        $result = $engine->compute($this->poolCandidateId);

        if ($result) {
            Log::channel('daily')->info('[CompliancePack] Computed', [
                'candidate_id' => $this->poolCandidateId,
                'score' => $result['score'],
                'status' => $result['status'],
                'available_sections' => $result['available_sections'],
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('daily')->error('[CompliancePack] Job failed', [
            'candidate_id' => $this->poolCandidateId,
            'error' => $e->getMessage(),
        ]);
    }
}
