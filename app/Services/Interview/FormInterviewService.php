<?php

namespace App\Services\Interview;

use App\Config\MaritimeRole;
use App\Jobs\SendCandidateEmailJob;
use App\Models\CandidateTimelineEvent;
use App\Models\FormInterview;
use App\Models\FormInterviewAnswer;
use App\Services\Analytics\PositionBaselineService;
use App\Services\DecisionEngine\FormInterviewDecisionEngineAdapter;
use App\Services\DecisionEngine\MaritimeDecisionEngine;
use App\Services\ML\ModelFeatureService;
use App\Services\ML\MlScoringService;
use App\Services\Policy\FormInterviewPolicyEngine;
use App\Services\PoolCandidateService;
use App\Services\Behavioral\BehavioralScoringService;
use App\Services\Brand\BrandResolver;
use App\Services\System\SystemEventService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FormInterviewService
{
    public function __construct(
        private readonly InterviewTemplateService $templateService,
        private readonly FormInterviewDecisionEngineAdapter $decisionEngine,
        private readonly PositionBaselineService $baselineService,
        private readonly FormInterviewPolicyEngine $policyEngine,
        private readonly PoolCandidateService $poolCandidateService,
        private readonly ModelFeatureService $modelFeatureService,
        private readonly MlScoringService $mlScoringService,
        private readonly MaritimeDecisionEngine $maritimeDecisionEngine,
        private readonly BehavioralScoringService $behavioralScoringService,
    ) {}

    /**
     * Create a new form interview session
     */
    public function create(
        string $version,
        string $language,
        ?string $positionCode,
        array $meta = [],
        ?string $industryCode = null,
        ?string $roleCode = null,
        ?string $department = null,
        ?string $operationType = null
    ): FormInterview {
        $isMaritime = $industryCode === 'maritime';

        if ($isMaritime) {
            // Auto-derive roleCode from positionCode if not explicitly provided
            if (!$roleCode && $positionCode) {
                if ($positionCode === '__generic__') {
                    // Generic maritime → use deck department as default
                    $roleCode = 'able_seaman';
                } else {
                    $normalized = MaritimeRole::normalize($positionCode);
                    $roleCode = $normalized ?: $positionCode;
                }
            }
            return $this->createMaritime($version, $language, $roleCode, $department, $meta, $industryCode, $operationType);
        }

        $positionCode = $positionCode ?: '__generic__';

        // Get template with fallback
        $template = $this->templateService->getTemplate($version, $language, $positionCode);
        $resolvedPosition = $template->position_code; // could be __generic__ if fallback

        // Get raw template JSON (exact string snapshot, no re-encoding)
        $templateJson = $template->getRawOriginal('template_json') ?? $template->template_json;

        // Resolve brand from industry
        $brandCode = BrandResolver::codeFromIndustry($industryCode)
            ?? config('brands.default', 'octopus');
        $brand = BrandResolver::resolve($brandCode);

        $interview = FormInterview::create([
            'version' => $version,
            'language' => $language,
            'position_code' => $positionCode,
            'template_position_code' => $resolvedPosition,
            'industry_code' => $industryCode,
            'platform_code' => $brandCode,
            'brand_domain' => $brand['domain'] ?? null,
            'status' => FormInterview::STATUS_IN_PROGRESS,
            'template_json' => $templateJson,
            'template_json_sha256' => $templateJson ? hash('sha256', $templateJson) : null,
            'meta' => $meta ?: null,
        ]);

        // Timeline: interview started
        $this->emitInterviewStartedEvent($interview);

        return $interview;
    }

    /**
     * Maritime interview creation with department-isolated template resolution.
     *
     * @throws \RuntimeException if no template found (caller converts to 422)
     */
    private function createMaritime(
        string $version,
        string $language,
        ?string $roleCode,
        ?string $department,
        array $meta,
        string $industryCode,
        ?string $operationType = null
    ): FormInterview {
        // Derive department from role if not explicitly given
        if (!$department && $roleCode) {
            $department = $this->templateService->departmentForRole($roleCode);
        }

        if (!$roleCode || !$department) {
            // Log failed resolve
            $this->logTemplateResolveFailed($version, $language, $roleCode, $department, 'missing_role_or_department');
            throw new \RuntimeException('department_required');
        }

        $namespacedCode = "{$department}_{$roleCode}";

        $template = $this->templateService->getMaritimeTemplate($version, $language, $department, $roleCode, $operationType);

        if (!$template) {
            $this->logTemplateResolveFailed($version, $language, $roleCode, $department, 'no_template_found');
            throw new \RuntimeException('no_template_for_role_department');
        }

        $templateJson = $template->getRawOriginal('template_json') ?? $template->template_json;

        // Resolve brand from industry
        $brandCode = BrandResolver::codeFromIndustry($industryCode)
            ?? config('brands.default', 'octopus');
        $brand = BrandResolver::resolve($brandCode);

        $interview = FormInterview::create([
            'version' => $version,
            'language' => $language,
            'position_code' => $namespacedCode,
            'template_position_code' => $template->position_code,
            'industry_code' => $industryCode,
            'platform_code' => $brandCode,
            'brand_domain' => $brand['domain'] ?? null,
            'status' => FormInterview::STATUS_IN_PROGRESS,
            'template_json' => $templateJson,
            'template_json_sha256' => $templateJson ? hash('sha256', $templateJson) : null,
            'meta' => array_merge($meta ?: [], array_filter([
                'role_code' => $roleCode,
                'department' => $department,
                'operation_type' => $operationType,
            ])),
        ]);

        // Timeline: interview started
        $this->emitInterviewStartedEvent($interview);

        return $interview;
    }

    /**
     * Log template resolution failure to system_events
     */
    private function logTemplateResolveFailed(
        string $version,
        string $language,
        ?string $roleCode,
        ?string $department,
        string $reason
    ): void {
        try {
            if (class_exists(\App\Models\SystemEvent::class)) {
                \App\Models\SystemEvent::create([
                    'type' => 'template_resolve_failed',
                    'severity' => 'warn',
                    'source' => 'interview',
                    'message' => "Template resolve failed: {$reason}",
                    'meta' => [
                        'version' => $version,
                        'language' => $language,
                        'role_code' => $roleCode,
                        'department' => $department,
                        'industry_code' => 'maritime',
                        'brand' => 'octopus',
                        'attempted_codes' => $roleCode && $department
                            ? ["{$department}_{$roleCode}", "{$department}___generic__"]
                            : [],
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to log template_resolve_failed event', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Upsert answers for a form interview
     * Updates existing answers by slot or creates new ones
     */
    public function upsertAnswers(FormInterview $interview, array $answers): void
    {
        DB::transaction(function () use ($interview, $answers) {
            foreach ($answers as $answer) {
                FormInterviewAnswer::updateOrCreate(
                    [
                        'form_interview_id' => $interview->id,
                        'slot' => (int) $answer['slot'],
                    ],
                    [
                        'competency' => (string) $answer['competency'],
                        'answer_text' => (string) $answer['answer_text'],
                    ]
                );
            }
        });

        // Step: Behavioral Engine v1 — incremental update per answer
        if ($interview->industry_code === 'maritime'
            && config('maritime.behavioral_v1')
            && config('maritime.behavioral_incremental')
        ) {
            try {
                foreach ($answers as $answerData) {
                    $answerModel = FormInterviewAnswer::where('form_interview_id', $interview->id)
                        ->where('slot', (int) $answerData['slot'])
                        ->first();
                    if ($answerModel) {
                        $this->behavioralScoringService->updateIncremental($interview, $answerModel);
                    }
                }
            } catch (\Throwable $e) {
                Log::channel('single')->warning('BehavioralEngine incremental failed', [
                    'interview_id' => $interview->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Complete the interview and calculate scores using DecisionEngine
     *
     * Processing Pipeline:
     * 1. DecisionEngine evaluate → raw scores
     * 2. Calibration Layer → z-score + calibrated score
     * 3. Policy Engine → final decision with guardrails
     */
    public function completeAndScore(FormInterview $interview): FormInterview
    {
        // Clean workflow guard: interview must have a valid (non-expired) invitation
        if (config('maritime.clean_workflow_v1') && $interview->industry_code === 'maritime') {
            $invitation = \App\Models\InterviewInvitation::where('form_interview_id', $interview->id)->first();
            if (!$invitation || $invitation->status === \App\Models\InterviewInvitation::STATUS_EXPIRED) {
                throw new \RuntimeException('Cannot score interview without valid invitation.');
            }
        }

        // Pre-flight: Load and validate answers
        $interview->load('answers');
        $answerCount = $interview->answers->count();
        $answersWithText = $interview->answers->filter(fn($a) => !empty(trim($a->answer_text ?? '')))->count();

        // DEBUG: Log answer state before evaluation
        Log::channel('single')->info('FormInterview::completeAndScore PRE', [
            'interview_id' => $interview->id,
            'position' => $interview->template_position_code,
            'answer_count' => $answerCount,
            'answers_with_text' => $answersWithText,
        ]);

        // Step 1: DecisionEngine evaluation (AI or heuristic)
        $scoringMethod = 'heuristic';

        if (config('features.form_interview_ai_analysis_v1')) {
            $aiResult = app(FormInterviewAnalysisService::class)->analyze($interview);
            if ($aiResult !== null) {
                $result = $aiResult;
                $scoringMethod = 'ai';
            } else {
                // AI failed → heuristic fallback
                $result = $this->decisionEngine->evaluate($interview);
                $scoringMethod = 'heuristic_fallback';
            }
        } else {
            $result = $this->decisionEngine->evaluate($interview);
        }

        // CONTRACT ENFORCE: final_score 0..100 int
        $rawFinal = (int) round((float)($result['final_score'] ?? 0));
        $rawFinal = max(0, min(100, $rawFinal));

        $rawDecision = (string) ($result['decision'] ?? 'HOLD');
        $rawReason = (string) ($result['decision_reason'] ?? '');

        // DEBUG: Log DecisionEngine output
        $competencyScores = $result['competency_scores'] ?? [];
        $nonZeroCompetencies = count(array_filter($competencyScores, fn($s) => $s > 0));

        Log::channel('single')->info('FormInterview::completeAndScore POST-ENGINE', [
            'interview_id' => $interview->id,
            'raw_final_score' => $rawFinal,
            'base_score' => $result['base_score'] ?? null,
            'risk_penalty' => $result['risk_penalty'] ?? 0,
            'red_flag_penalty' => $result['red_flag_penalty'] ?? 0,
            'competency_count' => count($competencyScores),
            'non_zero_competencies' => $nonZeroCompetencies,
            'risk_flags_count' => count($result['risk_flags'] ?? []),
        ]);

        // CONTRACT WARNING: Flag suspicious low scores
        if ($rawFinal <= 10 && $answersWithText >= 4) {
            Log::channel('single')->warning('FormInterview::completeAndScore ANOMALY', [
                'interview_id' => $interview->id,
                'raw_final_score' => $rawFinal,
                'answers_with_text' => $answersWithText,
                'competency_scores' => $competencyScores,
                'message' => 'Low score despite having answers - investigate DecisionEngine',
            ]);
        }

        // Step 1.5: Persist per-answer scores (0-5 scale)
        // If AI provided question_analyses, use AI scores; otherwise heuristic length-based
        $aiQuestionScores = [];
        if ($scoringMethod === 'ai' && !empty($result['question_analyses'])) {
            foreach ($result['question_analyses'] as $qa) {
                $slot = $qa['question_order'] ?? null;
                if ($slot !== null && isset($qa['score'])) {
                    $aiQuestionScores[$slot] = max(0, min(5, (int) $qa['score']));
                }
            }
        }

        foreach ($interview->answers as $answer) {
            if (isset($aiQuestionScores[$answer->slot])) {
                $score = $aiQuestionScores[$answer->slot];
            } else {
                $text = trim($answer->answer_text ?? '');
                $len = mb_strlen($text, 'UTF-8');
                $score = match (true) {
                    $len === 0 => 0,
                    $len < 30  => 1,
                    $len < 80  => 2,
                    $len < 180 => 3,
                    $len < 350 => 4,
                    default    => 5,
                };
            }
            DB::table('form_interview_answers')
                ->where('id', $answer->id)
                ->update(['score' => $score]);
        }

        // Step 2: Calibration Layer v2
        // Baseline with fallback chain: position+industry → position → generic
        $dims = [
            'version' => $interview->version,
            'language' => $interview->language,
            'position_code' => $interview->template_position_code ?? $interview->position_code,
            'industry_code' => $interview->industry_code, // nullable
        ];

        $minN = 30;
        $baseline = $this->baselineService->baseline($dims, minN: $minN, maxN: 200, lastDays: 90);

        $mean = $baseline['mean'];
        $std  = $baseline['std'];

        // Skip z-score calibration when baseline pool is too small (n < minN).
        // Small pools produce unreliable statistics and can cause false rejections.
        if (($baseline['n'] ?? 0) >= $minN) {
            $z   = $this->baselineService->zScore($rawFinal, $mean, $std);
            $cal = $this->baselineService->calibratedScoreFromZ($z);
        } else {
            $z   = null;
            $cal = null; // will fallback to rawFinal downstream
        }

        // Log calibration details
        Log::channel('single')->info('FormInterview::completeAndScore CALIBRATION', [
            'interview_id' => $interview->id,
            'baseline_n' => $baseline['n'],
            'baseline_fallback_level' => $baseline['baseline_fallback_level'] ?? 'unknown',
            'baseline_dims_used' => $baseline['baseline_dims_used'] ?? null,
            'mean' => $mean,
            'std' => $std,
            'z_score' => $z,
            'calibrated_score' => $cal,
        ]);

        // Step 3: Policy Engine (final decision authority)
        $policy = $this->policyEngine->decide($interview, $result, $cal);

        // Persist all layers
        $interview->update([
            // DecisionEngine competency data
            'competency_scores' => $result['competency_scores'],
            'risk_flags'        => $result['risk_flags'],

            // Raw DecisionEngine outputs (audit trail)
            'raw_final_score'       => $rawFinal,
            'raw_decision'          => $rawDecision,
            'raw_decision_reason'   => $rawReason,

            // Calibration snapshot (v2 with fallback metadata)
            'position_mean_score'   => $mean,
            'position_std_dev_score'=> $std,
            'z_score'               => $z,
            'calibrated_score'      => $cal ?? $rawFinal, // veri azsa fallback
            'calibration_version'   => 'v2',

            // Policy output (final truth for the system)
            'final_score'       => (int) $policy['final_score'],
            'decision'          => (string) $policy['decision'],
            'decision_reason'   => (string) $policy['reason'],
            'policy_code'       => (string) $policy['policy_code'],
            'policy_version'    => 'v1',

            // Scoring method in meta
            'meta' => array_merge($interview->meta ?? [], ['scoring_method' => $scoringMethod]),

            // Status
            'status'       => FormInterview::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // Step 4: Candidate Supply Engine - Auto-pooling
        // If interview is linked to a pool candidate, update their status
        $interview = $interview->fresh(['answers']);
        $this->poolCandidateService->handleInterviewCompletion($interview);

        // Step 5: Learning Core - Feature Store
        // Store features for ML training dataset
        try {
            $this->modelFeatureService->upsertForInterview($interview);
        } catch (\Throwable $e) {
            Log::channel('single')->warning('ModelFeatureService failed', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Step 6: Learning Core - ML Prediction
        // Generate and store outcome prediction
        try {
            $this->mlScoringService->predictAndStore($interview);
        } catch (\Throwable $e) {
            Log::channel('single')->warning('MlScoringService failed', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Step 7: Maritime Decision Engine (additive — non-blocking)
        if ($interview->industry_code === 'maritime') {
            try {
                $maritimeDecision = $this->maritimeDecisionEngine->evaluate($interview);
                $interview->update(['decision_summary_json' => $maritimeDecision]);

                SystemEventService::log(
                    'decision_engine_applied',
                    'info',
                    'ml',
                    "Maritime decision: {$maritimeDecision['decision']} (score: {$maritimeDecision['final_score']})",
                    [
                        'interview_id' => $interview->id,
                        'decision' => $maritimeDecision['decision'],
                        'final_score' => $maritimeDecision['final_score'],
                        'confidence_pct' => $maritimeDecision['confidence_pct'],
                        'industry_code' => 'maritime',
                        'brand' => 'octopus',
                        'trigger' => 'completion',
                    ]
                );
            } catch (\Throwable $e) {
                Log::channel('single')->warning('MaritimeDecisionEngine failed', [
                    'interview_id' => $interview->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Step 7b: Company Competency Model scoring (non-fatal)
        if (config('features.competency_model_v1') && $interview->company_id) {
            try {
                $companyFit = app(\App\Services\CompanyCompetencyService::class)->computeCompanyFit($interview);
                if ($companyFit) {
                    $interview->update([
                        'company_fit_score' => $companyFit['company_fit_score'],
                        'company_competency_scores' => $companyFit['company_competency_scores'],
                    ]);
                }
            } catch (\Throwable $e) {
                Log::channel('single')->warning('Company competency scoring failed', [
                    'interview_id' => $interview->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Step 8: Behavioral Engine v1 — finalize profile
        if ($interview->industry_code === 'maritime' && config('maritime.behavioral_v1')) {
            try {
                $this->behavioralScoringService->finalize($interview);
            } catch (\Throwable $e) {
                Log::channel('single')->warning('BehavioralEngine finalize failed', [
                    'interview_id' => $interview->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Step 9: Send "Mülakat Tamamlandı" email to candidate
        if ($interview->pool_candidate_id) {
            $positionLabel = $interview->position_code
                ? ucwords(str_replace('_', ' ', $interview->position_code))
                : null;

            SendCandidateEmailJob::dispatchSafe(
                $interview->pool_candidate_id,
                'interview_completed',
                $interview->id,
                $positionLabel,
            );
        }

        // Step 10: Timeline — interview completed
        if ($interview->pool_candidate_id) {
            try {
                CandidateTimelineEvent::record(
                    $interview->pool_candidate_id,
                    CandidateTimelineEvent::TYPE_INTERVIEW_COMPLETED,
                    CandidateTimelineEvent::SOURCE_SYSTEM,
                    [
                        'interview_id' => $interview->id,
                        'position_code' => $interview->position_code,
                        'decision' => $interview->decision,
                        'final_score' => $interview->calibrated_score ?? $interview->final_score,
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('Timeline event failed (interview_completed)', ['error' => $e->getMessage()]);
            }

            // Step 11: CRI recompute (async, fail-open)
            try {
                \App\Jobs\RecomputeCriJob::dispatch($interview->pool_candidate_id, 'interview_completed');
            } catch (\Throwable) {}

            // Step 12: Competency assessment (async, fail-open)
            if (config('maritime.competency_v1') && config('maritime.competency_auto_compute')) {
                try {
                    \App\Jobs\ComputeCompetencyAssessmentJob::dispatch($interview->pool_candidate_id)
                        ->delay(now()->addSeconds(30));
                } catch (\Throwable) {}
            }

            // Step 13: Candidate scoring vector (async, fail-open)
            if (config('maritime.vector_v1')) {
                try {
                    \App\Jobs\ComputeCandidateVectorJob::dispatch(
                        $interview->pool_candidate_id,
                        'interview_completed'
                    )->delay(now()->addSeconds(60));
                } catch (\Throwable) {}
            }
        }

        return $interview;
    }

    /**
     * Emit interview_started timeline event (fail-open).
     */
    private function emitInterviewStartedEvent(FormInterview $interview): void
    {
        if (!$interview->pool_candidate_id) {
            return;
        }

        try {
            CandidateTimelineEvent::record(
                $interview->pool_candidate_id,
                CandidateTimelineEvent::TYPE_INTERVIEW_STARTED,
                CandidateTimelineEvent::SOURCE_SYSTEM,
                [
                    'interview_id' => $interview->id,
                    'position_code' => $interview->position_code,
                    'industry_code' => $interview->industry_code,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Timeline event failed (interview_started)', ['error' => $e->getMessage()]);
        }
    }
}
