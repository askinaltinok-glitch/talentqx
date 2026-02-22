<?php

namespace App\Services\Maritime;

use App\Models\FormInterview;
use App\Models\ResolverAuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Append-only audit logger for resolver decisions.
 *
 * Every Phase-1 completion and Phase-2 start/completion gets logged.
 */
class ResolverAuditLogger
{
    /**
     * Log Phase-1 completion (class detection output).
     */
    public function logPhase1(
        FormInterview $interview,
        array $detectionResult,
        ?string $candidateId = null,
        ?string $companyId = null,
    ): ResolverAuditLog {
        return ResolverAuditLog::create([
            'form_interview_id' => $interview->id,
            'candidate_id' => $candidateId ?? $interview->pool_candidate_id,
            'company_id' => $companyId ?? $interview->company_id,
            'phase' => 1,
            'input_snapshot' => [
                'interview_id' => $interview->id,
                'type' => $interview->type,
                'language' => $interview->language,
            ],
            'class_detection_output' => $detectionResult,
        ]);
    }

    /**
     * Log Phase-2 start (scenario selection output).
     */
    public function logPhase2Start(
        FormInterview $interview,
        Collection $scenarios,
        string $selectionReason,
        ?string $candidateId = null,
        ?string $companyId = null,
    ): ResolverAuditLog {
        return ResolverAuditLog::create([
            'form_interview_id' => $interview->id,
            'candidate_id' => $candidateId ?? $interview->pool_candidate_id,
            'company_id' => $companyId ?? $interview->company_id,
            'phase' => 2,
            'input_snapshot' => [
                'interview_id' => $interview->id,
                'command_class' => $interview->command_class_detected,
                'phase1_interview_id' => $interview->linked_phase_interview_id,
            ],
            'scenario_set_json' => $scenarios->pluck('scenario_code')->toArray(),
            'selection_reason' => $selectionReason,
        ]);
    }

    /**
     * Log Phase-2 completion (capability output + final packet).
     */
    public function logPhase2Complete(
        FormInterview $interview,
        ?array $capabilityOutput = null,
        ?array $finalPacket = null,
        ?string $candidateId = null,
        ?string $companyId = null,
    ): ResolverAuditLog {
        return ResolverAuditLog::create([
            'form_interview_id' => $interview->id,
            'candidate_id' => $candidateId ?? $interview->pool_candidate_id,
            'company_id' => $companyId ?? $interview->company_id,
            'phase' => 2,
            'input_snapshot' => [
                'interview_id' => $interview->id,
                'command_class' => $interview->command_class_detected,
                'action' => 'complete',
            ],
            'capability_output' => $capabilityOutput,
            'final_packet' => $finalPacket,
        ]);
    }
}
