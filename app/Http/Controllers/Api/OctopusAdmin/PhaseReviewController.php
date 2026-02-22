<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Services\Maritime\PhaseReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhaseReviewController extends Controller
{
    public function __construct(
        private readonly PhaseReviewService $service,
    ) {}

    public function review(string $candidateId, string $phaseKey, Request $request): JsonResponse
    {
        $allowedPhases = config('maritime.decision_panel.phase_whitelist', ['standard_competency']);

        if (!in_array($phaseKey, $allowedPhases)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phase key. Allowed: ' . implode(', ', $allowedPhases),
            ], 422);
        }

        $data = $request->validate([
            'status' => 'required|string|in:not_started,in_progress,completed,approved,rejected',
            'review_notes' => 'nullable|string|max:2000',
        ]);

        $review = $this->service->upsert(
            $candidateId,
            $phaseKey,
            $data['status'],
            $data['review_notes'] ?? null,
            $request->user()?->id,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $review->id,
                'candidate_id' => $review->candidate_id,
                'phase_key' => $review->phase_key,
                'status' => $review->status,
                'review_notes' => $review->review_notes,
                'reviewed_by' => $review->reviewed_by,
                'reviewed_at' => $review->reviewed_at?->toIso8601String(),
                'updated_at' => $review->updated_at?->toIso8601String(),
            ],
        ]);
    }
}
