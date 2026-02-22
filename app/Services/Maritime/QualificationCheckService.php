<?php

namespace App\Services\Maritime;

use App\Models\AuditLog;
use App\Models\CandidateQualificationCheck;
use Illuminate\Support\Facades\DB;

class QualificationCheckService
{
    public function upsert(
        string $candidateId,
        string $qualificationKey,
        string $status,
        ?string $notes,
        ?string $evidenceUrl,
        ?string $adminId
    ): CandidateQualificationCheck {
        return DB::transaction(function () use ($candidateId, $qualificationKey, $status, $notes, $evidenceUrl, $adminId) {
            $existing = CandidateQualificationCheck::where('candidate_id', $candidateId)
                ->where('qualification_key', $qualificationKey)
                ->first();

            $oldStatus = $existing?->status;

            $check = CandidateQualificationCheck::updateOrCreate(
                [
                    'candidate_id' => $candidateId,
                    'qualification_key' => $qualificationKey,
                ],
                [
                    'status' => $status,
                    'notes' => $notes,
                    'evidence_url' => $evidenceUrl,
                    'verified_by' => in_array($status, ['verified', 'rejected']) ? $adminId : ($existing->verified_by ?? null),
                    'verified_at' => in_array($status, ['verified', 'rejected']) ? now() : ($existing->verified_at ?? null),
                ]
            );

            if ($oldStatus !== $status) {
                AuditLog::create([
                    'user_id' => $adminId,
                    'action' => 'qualification.status_changed',
                    'entity_type' => 'candidate_qualification_check',
                    'entity_id' => $check->id,
                    'old_values' => ['status' => $oldStatus],
                    'new_values' => [
                        'status' => $status,
                        'qualification_key' => $qualificationKey,
                        'candidate_id' => $candidateId,
                        'notes' => $notes,
                    ],
                ]);
            }

            return $check;
        });
    }
}
