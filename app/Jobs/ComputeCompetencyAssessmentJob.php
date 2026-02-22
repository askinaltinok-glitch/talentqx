<?php

namespace App\Jobs;

use App\Services\Competency\CompetencyEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComputeCompetencyAssessmentJob implements ShouldQueue
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

    public function handle(CompetencyEngine $engine): void
    {
        $result = $engine->compute($this->poolCandidateId);

        if ($result) {
            Log::channel('daily')->info('[CompetencyEngine] Computed', [
                'candidate_id' => $this->poolCandidateId,
                'score_total' => $result['score_total'],
                'status' => $result['status'],
                'flag_count' => count($result['flags']),
                'questions_evaluated' => $result['questions_evaluated'],
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('daily')->error('[CompetencyEngine] Job failed', [
            'candidate_id' => $this->poolCandidateId,
            'error' => $e->getMessage(),
        ]);
    }
}
