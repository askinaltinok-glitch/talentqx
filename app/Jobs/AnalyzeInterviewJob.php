<?php

namespace App\Jobs;

use App\Models\Interview;
use App\Services\Interview\AnalysisEngine;
use App\Services\Interview\TranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeInterviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 60, 120];

    public function __construct(
        public Interview $interview,
        public bool $forceReanalyze = false
    ) {}

    public function handle(
        TranscriptionService $transcriptionService,
        AnalysisEngine $analysisEngine
    ): void {
        Log::info('Starting interview analysis', [
            'interview_id' => $this->interview->id,
            'force_reanalyze' => $this->forceReanalyze,
        ]);

        try {
            // Step 1: Transcribe all responses if not already done
            if (!$transcriptionService->hasAllTranscripts($this->interview)) {
                Log::info('Transcribing interview responses', [
                    'interview_id' => $this->interview->id,
                ]);
                $transcriptionService->transcribeAllResponses($this->interview);
            }

            // Step 2: Analyze the interview
            Log::info('Analyzing interview', [
                'interview_id' => $this->interview->id,
            ]);

            $analysis = $analysisEngine->analyzeInterview(
                $this->interview,
                $this->forceReanalyze
            );

            Log::info('Interview analysis completed', [
                'interview_id' => $this->interview->id,
                'analysis_id' => $analysis->id,
                'overall_score' => $analysis->overall_score,
                'recommendation' => $analysis->getRecommendation(),
            ]);

            // Step 3: Update candidate status
            $this->interview->candidate->updateStatus(
                'under_review',
                'AI analizi tamamlandi. Puan: ' . $analysis->overall_score
            );

        } catch (\Exception $e) {
            Log::error('Interview analysis failed', [
                'interview_id' => $this->interview->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeInterviewJob permanently failed', [
            'interview_id' => $this->interview->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
