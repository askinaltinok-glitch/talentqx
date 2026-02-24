<?php

namespace App\Jobs;

use App\Models\Interview;
use App\Models\InterviewAnalysis;
use App\Services\AI\LLMProviderFactory;
use App\Services\Copilot\GuardrailResult;
use App\Services\Interview\AnalysisEngine;
use App\Services\Interview\TranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Log;

class AnalyzeInterviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public $tries = 3;
    public $backoff = [30, 60, 120];

    public function __construct(
        public Interview $interview,
        public bool $forceReanalyze = false,
        public ?string $provider = null
    ) {
        $this->captureBrand();
    }

    public function handle(
        TranscriptionService $transcriptionService
    ): void {
        $this->setBrandDatabase();
        // Get company ID from interview to use correct AI provider
        $companyId = $this->interview->job?->company_id;

        // Use explicitly selected provider if provided, otherwise use company/platform settings
        $llmProvider = LLMProviderFactory::create($this->provider, $companyId);
        $analysisEngine = new AnalysisEngine($llmProvider);

        $providerInfo = $llmProvider->getModelInfo();
        Log::info('Starting interview analysis', [
            'interview_id' => $this->interview->id,
            'force_reanalyze' => $this->forceReanalyze,
            'requested_provider' => $this->provider,
            'ai_provider' => $providerInfo['provider'],
            'ai_model' => $providerInfo['model'],
            'company_id' => $companyId,
        ]);

        try {
            // Step 1: Transcribe all responses if not already done
            if (!$transcriptionService->hasAllTranscripts($this->interview)) {
                Log::info('Transcribing interview responses', [
                    'interview_id' => $this->interview->id,
                ]);
                $transcriptionService->transcribeAllResponses($this->interview);
            }

            // Step 1.5: P0 - Check for minimum valid answers before AI analysis
            $validAnswerCount = $this->countValidAnswers($this->interview);

            if ($validAnswerCount < GuardrailResult::MIN_ANSWERED_QUESTIONS) {
                Log::warning("Insufficient evidence for interview", [
                    'interview_id' => $this->interview->id,
                    'valid_answers' => $validAnswerCount,
                    'required' => GuardrailResult::MIN_ANSWERED_QUESTIONS,
                ]);

                // Save analysis with insufficient_evidence status (no AI call)
                InterviewAnalysis::updateOrCreate(
                    ['interview_id' => $this->interview->id],
                    [
                        'overall_score' => null,
                        'competency_scores' => [],
                        'behavior_analysis' => [],
                        'red_flag_analysis' => [],
                        'culture_fit' => null,
                        'decision_snapshot' => [
                            'status' => 'insufficient_evidence',
                            'blocked_reason_code' => GuardrailResult::STATUS_INSUFFICIENT_EVIDENCE,
                            'valid_answer_count' => $validAnswerCount,
                            'required_answer_count' => GuardrailResult::MIN_ANSWERED_QUESTIONS,
                        ],
                        'question_analyses' => [],
                        'analyzed_at' => now(),
                    ]
                );

                $this->interview->candidate->updateStatus(
                    'insufficient_data',
                    'Yetersiz veri: Degerlendirme icin en az ' . GuardrailResult::MIN_ANSWERED_QUESTIONS . ' detayli cevap gerekli.'
                );

                Log::info("Interview {$this->interview->id} marked as insufficient_evidence");
                return;
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

    /**
     * Count responses that meet minimum length requirement.
     *
     * @param Interview $interview
     * @return int
     */
    private function countValidAnswers(Interview $interview): int
    {
        $validCount = 0;

        foreach ($interview->responses as $response) {
            $text = trim($response->transcript ?? '');
            if (mb_strlen($text) >= GuardrailResult::MIN_ANSWER_CHARS) {
                $validCount++;
            }
        }

        return $validCount;
    }
}
