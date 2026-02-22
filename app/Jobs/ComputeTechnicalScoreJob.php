<?php

namespace App\Jobs;

use App\Services\RankStcw\TechnicalScoreCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComputeTechnicalScoreJob implements ShouldQueue
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

    public function handle(TechnicalScoreCalculator $calculator): void
    {
        $result = $calculator->compute($this->poolCandidateId);

        if ($result) {
            Log::channel('daily')->info('[RankSTCW] Technical score computed', [
                'candidate_id' => $this->poolCandidateId,
                'technical_score' => $result['technical_score'],
                'rank_days_weight' => $result['rank_days_weight'],
                'vessel_match_weight' => $result['vessel_match_weight'],
                'certification_weight' => $result['certification_weight'],
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('daily')->error('[RankSTCW] Technical score job failed', [
            'candidate_id' => $this->poolCandidateId,
            'error' => $e->getMessage(),
        ]);
    }
}
