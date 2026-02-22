<?php

namespace App\Services\Maritime;

use App\Models\AuditLog;
use App\Models\CandidatePhaseReview;
use Illuminate\Support\Facades\DB;

class PhaseReviewService
{
    public function upsert(
        string $candidateId,
        string $phaseKey,
        string $status,
        ?string $reviewNotes,
        ?string $adminId
    ): CandidatePhaseReview {
        return DB::transaction(function () use ($candidateId, $phaseKey, $status, $reviewNotes, $adminId) {
            $existing = CandidatePhaseReview::where('candidate_id', $candidateId)
                ->where('phase_key', $phaseKey)
                ->first();

            $oldStatus = $existing?->status;

            $review = CandidatePhaseReview::updateOrCreate(
                [
                    'candidate_id' => $candidateId,
                    'phase_key' => $phaseKey,
                ],
                [
                    'status' => $status,
                    'review_notes' => $reviewNotes,
                    'reviewed_by' => in_array($status, ['approved', 'rejected']) ? $adminId : ($existing->reviewed_by ?? null),
                    'reviewed_at' => in_array($status, ['approved', 'rejected']) ? now() : ($existing->reviewed_at ?? null),
                ]
            );

            if ($oldStatus !== $status) {
                AuditLog::create([
                    'user_id' => $adminId,
                    'action' => 'phase.review_changed',
                    'entity_type' => 'candidate_phase_review',
                    'entity_id' => $review->id,
                    'old_values' => ['status' => $oldStatus],
                    'new_values' => [
                        'status' => $status,
                        'phase_key' => $phaseKey,
                        'candidate_id' => $candidateId,
                        'review_notes' => $reviewNotes,
                    ],
                ]);
            }

            return $review;
        });
    }
}
