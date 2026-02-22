<?php

namespace App\Services\Maritime;

use App\Exceptions\ScenarioNotFoundException;
use App\Models\CandidateCommandProfile;
use App\Models\FormInterview;
use App\Models\MaritimeScenarioResponse;
use App\Services\Interview\FormInterviewService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Profile-Driven Resolver — Orchestrator for D+E pipeline.
 *
 * Bridges Phase-1 (identity) → Phase-2 (scenarios):
 * 1. startPhase2()     — resolve class, select scenarios, create Phase-2 interview
 * 2. submitPhase2Answer() — store individual scenario responses
 * 3. completePhase2()  — score, generate deployment packet, audit log
 */
class ProfileDrivenResolver
{
    public function __construct(
        private CommandTemplateResolver $templateResolver,
        private ScenarioSelector $scenarioSelector,
        private CapabilityScoringService $scoringService,
        private ResolverAuditLogger $auditLogger,
    ) {}

    /**
     * Start Phase-2: resolve command class, select scenarios, create Phase-2 interview.
     *
     * @throws ScenarioNotFoundException if scenario bank incomplete
     * @throws \InvalidArgumentException if profile_inconsistent
     */
    public function startPhase2(FormInterview $phase1Interview): array
    {
        if (!config('maritime.resolver_v2')) {
            abort(404);
        }

        // Validate Phase-1 is completed
        if (!$phase1Interview->isCompleted() || $phase1Interview->interview_phase !== 1) {
            throw new \InvalidArgumentException('Phase-1 interview must be completed first.');
        }

        // Get candidate profile
        $profile = CandidateCommandProfile::where(
            'candidate_id',
            $phase1Interview->pool_candidate_id
                ?? $phase1Interview->meta['candidate_id']
                ?? null
        )->latest()->firstOrFail();

        // Resolve command class (may throw profile_inconsistent)
        $resolution = $this->templateResolver->resolve($profile);

        // Block if needs_review
        if ($resolution['needs_review']) {
            $phase1Interview->update([
                'needs_review' => true,
                'resolver_status' => 'blocked',
            ]);

            return [
                'success' => false,
                'error' => 'needs_review',
                'message' => 'Low confidence detection. Admin review required before Phase-2.',
                'command_class' => $resolution['command_class'],
                'confidence' => $resolution['confidence'],
                'flags' => $resolution['flags'],
            ];
        }

        // Select scenarios (hard fail if < 8/8)
        if ($resolution['blend'] && $resolution['secondary_class']) {
            $scenarios = $this->scenarioSelector->selectWithBlending(
                $resolution['command_class'],
                $resolution['secondary_class'],
            );
            $selectionReason = sprintf(
                'blended: %d primary (%s) + %d secondary (%s)',
                config('maritime.blending.primary', 6),
                $resolution['command_class'],
                config('maritime.blending.secondary', 2),
                $resolution['secondary_class'],
            );
        } else {
            $scenarios = $this->scenarioSelector->select($resolution['command_class']);
            $selectionReason = "single_class: {$resolution['command_class']}";
        }

        // Create Phase-2 interview record
        $phase2Interview = DB::transaction(function () use (
            $phase1Interview, $resolution, $scenarios, $selectionReason
        ) {
            $candidateId = $phase1Interview->pool_candidate_id
                ?? $phase1Interview->meta['candidate_id']
                ?? null;

            $phase2 = FormInterview::create([
                'pool_candidate_id' => $phase1Interview->pool_candidate_id,
                'company_id' => $phase1Interview->company_id,
                'version' => 'v2',
                'language' => $phase1Interview->language,
                'position_code' => $phase1Interview->position_code,
                'template_position_code' => $phase1Interview->template_position_code,
                'industry_code' => 'maritime',
                'status' => FormInterview::STATUS_IN_PROGRESS,
                'type' => 'maritime_scenario_v2',
                'interview_phase' => 2,
                'phase' => 2,
                'command_class_detected' => $resolution['command_class'],
                'command_profile_id' => $phase1Interview->command_profile_id,
                'linked_phase_interview_id' => $phase1Interview->id,
                'scenario_set_json' => $scenarios->pluck('scenario_code')->toArray(),
                'resolver_status' => 'pending',
                'needs_review' => false,
                'meta' => array_merge($phase1Interview->meta ?? [], [
                    'framework' => 'maritime_command',
                    'phase' => 2,
                    'candidate_id' => $candidateId,
                    'selection_reason' => $selectionReason,
                ]),
            ]);

            // Link Phase-1 → Phase-2
            $phase1Interview->update([
                'resolver_status' => 'resolved',
            ]);

            // Audit log
            $this->auditLogger->logPhase2Start(
                $phase2,
                $scenarios,
                $selectionReason,
                $candidateId,
                $phase1Interview->company_id,
            );

            return $phase2;
        });

        $result = new ResolverResult(
            commandClass: $resolution['command_class'],
            confidence: $resolution['confidence'],
            scenarios: $scenarios->all(),
            needsReview: false,
            secondaryClass: $resolution['secondary_class'],
            resolverStatus: 'pending',
            flags: $resolution['flags'],
        );

        Log::info('ProfileDrivenResolver: Phase-2 started', [
            'phase1_id' => $phase1Interview->id,
            'phase2_id' => $phase2Interview->id,
            'command_class' => $resolution['command_class'],
            'scenarios_count' => $scenarios->count(),
            'blend' => $resolution['blend'],
        ]);

        return [
            'success' => true,
            'phase2_interview' => $phase2Interview,
            'resolver_result' => $result->toArray(),
        ];
    }

    /**
     * Submit a Phase-2 scenario answer.
     */
    public function submitPhase2Answer(
        FormInterview $phase2Interview,
        int $slot,
        string $answerText,
    ): MaritimeScenarioResponse {
        if ($phase2Interview->interview_phase !== 2 || $phase2Interview->type !== 'maritime_scenario_v2') {
            throw new \InvalidArgumentException('Not a Phase-2 scenario interview.');
        }

        if ($phase2Interview->isCompleted()) {
            throw new \InvalidArgumentException('Interview already completed.');
        }

        $scenarioCodes = $phase2Interview->scenario_set_json ?? [];
        if ($slot < 1 || $slot > count($scenarioCodes)) {
            throw new \InvalidArgumentException("Invalid slot {$slot}. Expected 1-" . count($scenarioCodes));
        }

        $scenarioCode = $scenarioCodes[$slot - 1] ?? null;
        $scenario = $scenarioCode
            ? \App\Models\MaritimeScenario::where('scenario_code', $scenarioCode)->first()
            : null;

        return MaritimeScenarioResponse::updateOrCreate(
            [
                'form_interview_id' => $phase2Interview->id,
                'slot' => $slot,
            ],
            [
                'scenario_id' => $scenario?->id,
                'raw_answer_text' => $answerText,
            ]
        );
    }

    /**
     * Complete Phase-2: score capabilities, build deployment packet.
     */
    public function completePhase2(FormInterview $phase2Interview): array
    {
        if ($phase2Interview->interview_phase !== 2 || $phase2Interview->type !== 'maritime_scenario_v2') {
            throw new \InvalidArgumentException('Not a Phase-2 scenario interview.');
        }

        if ($phase2Interview->isCompleted()) {
            throw new \InvalidArgumentException('Interview already completed.');
        }

        // Load scenario responses
        $phase2Interview->load('scenarioResponses');
        $responseCount = $phase2Interview->scenarioResponses->count();

        $scenarioCodes = $phase2Interview->scenario_set_json ?? [];
        if ($responseCount < count($scenarioCodes)) {
            throw new \InvalidArgumentException(
                "Incomplete: {$responseCount}/" . count($scenarioCodes) . " scenario responses submitted."
            );
        }

        // Copy scenario responses into answers for CapabilityScoringService compatibility
        $this->syncScenarioResponsesAsAnswers($phase2Interview);

        // Score capabilities
        $capScore = $this->scoringService->score($phase2Interview);

        // Build deployment packet
        $deploymentPacket = [
            'command_class' => $phase2Interview->command_class_detected,
            'crl' => $capScore->crl,
            'capability_scores' => $capScore->getAdjustedScores(),
            'deployment_flags' => $capScore->deployment_flags,
            'scoring_version' => $capScore->scoring_version,
            'scored_at' => $capScore->scored_at?->toIso8601String(),
        ];

        // Update interview record
        $phase2Interview->update([
            'status' => FormInterview::STATUS_COMPLETED,
            'completed_at' => now(),
            'deployment_packet_json' => $deploymentPacket,
            'resolver_status' => 'resolved',
        ]);

        $candidateId = $phase2Interview->pool_candidate_id
            ?? $phase2Interview->meta['candidate_id']
            ?? null;

        // Audit log
        $this->auditLogger->logPhase2Complete(
            $phase2Interview,
            $phase2Interview->capability_profile_json,
            $deploymentPacket,
            $candidateId,
            $phase2Interview->company_id,
        );

        Log::info('ProfileDrivenResolver: Phase-2 completed', [
            'interview_id' => $phase2Interview->id,
            'command_class' => $phase2Interview->command_class_detected,
            'crl' => $capScore->crl,
        ]);

        return [
            'success' => true,
            'interview' => $phase2Interview->fresh(),
            'capability_score' => $capScore,
            'deployment_packet' => $deploymentPacket,
        ];
    }

    /**
     * Sync MaritimeScenarioResponses into FormInterviewAnswers
     * for CapabilityScoringService compatibility.
     */
    private function syncScenarioResponsesAsAnswers(FormInterview $interview): void
    {
        foreach ($interview->scenarioResponses as $response) {
            \App\Models\FormInterviewAnswer::updateOrCreate(
                [
                    'form_interview_id' => $interview->id,
                    'slot' => $response->slot,
                ],
                [
                    'competency' => $response->scenario?->primary_capability ?? 'unknown',
                    'answer_text' => $response->raw_answer_text,
                ]
            );
        }

        // Reload answers for scoring
        $interview->load('answers');
    }
}
