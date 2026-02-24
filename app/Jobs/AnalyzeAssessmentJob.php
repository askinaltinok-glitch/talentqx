<?php

namespace App\Jobs;

use App\Models\AssessmentResult;
use App\Models\AssessmentSession;
use App\Services\Assessment\AssessmentAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Log;

class AnalyzeAssessmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public $tries = 3;
    public $backoff = [30, 60, 120];

    public function __construct(
        public AssessmentSession $session
    ) {
        $this->captureBrand();
    }

    public function handle(AssessmentAnalysisService $analysisService): void
    {
        $this->setBrandDatabase();
        Log::info('Starting assessment analysis', [
            'session_id' => $this->session->id,
            'employee_id' => $this->session->employee_id,
            'template_id' => $this->session->template_id,
        ]);

        try {
            // Check if already analyzed
            if ($this->session->result) {
                Log::info('Assessment already analyzed, skipping', [
                    'session_id' => $this->session->id,
                ]);
                return;
            }

            // Analyze the assessment
            $result = $analysisService->analyze($this->session);

            Log::info('Assessment analysis completed', [
                'session_id' => $this->session->id,
                'result_id' => $result->id,
                'overall_score' => $result->overall_score,
                'risk_level' => $result->risk_level,
                'level_label' => $result->level_label,
            ]);

        } catch (\Exception $e) {
            Log::error('Assessment analysis failed', [
                'session_id' => $this->session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeAssessmentJob permanently failed', [
            'session_id' => $this->session->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
