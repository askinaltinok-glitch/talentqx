<?php

namespace App\Services\Interview;

use App\Config\MaritimeRole;
use App\Models\FormInterview;
use App\Models\FormInterviewAnswer;
use App\Services\Analytics\PositionBaselineService;
use App\Services\DecisionEngine\FormInterviewDecisionEngineAdapter;
use App\Services\DecisionEngine\MaritimeDecisionEngine;
use App\Services\ML\ModelFeatureService;
use App\Services\ML\MlScoringService;
use App\Services\Policy\FormInterviewPolicyEngine;
use App\Services\PoolCandidateService;
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

        return FormInterview::create([
            'version' => $version,
            'language' => $language,
            'position_code' => $positionCode,
            'template_position_code' => $resolvedPosition,
            'industry_code' => $industryCode,
            'status' => FormInterview::STATUS_IN_PROGRESS,
            'template_json' => $templateJson,
            'template_json_sha256' => $templateJson ? hash('sha256', $templateJson) : null,
            'meta' => $meta ?: null,
        ]);
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

        return FormInterview::create([
            'version' => $version,
            'language' => $language,
            'position_code' => $namespacedCode,
            'template_position_code' => $template->position_code,
            'industry_code' => $industryCode,
            'status' => FormInterview::STATUS_IN_PROGRESS,
            'template_json' => $templateJson,
            'template_json_sha256' => $templateJson ? hash('sha256', $templateJson) : null,
            'meta' => array_merge($meta ?: [], array_filter([
                'role_code' => $roleCode,
                'department' => $department,
                'operation_type' => $operationType,
            ])),
        ]);
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

        // Step 1: DecisionEngine evaluation
        $result = $this->decisionEngine->evaluate($interview);

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
        foreach ($interview->answers as $answer) {
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

        $baseline = $this->baselineService->baseline($dims, minN: 30, maxN: 200, lastDays: 90);

        $mean = $baseline['mean'];
        $std  = $baseline['std'];
        $z    = $this->baselineService->zScore($rawFinal, $mean, $std);
        $cal  = $this->baselineService->calibratedScoreFromZ($z);

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

        return $interview;
    }
}
