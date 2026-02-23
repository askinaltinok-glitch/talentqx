<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Api\Maritime\Concerns\VerifiesCandidateToken;
use App\Http\Controllers\Controller;
use App\Models\CandidateContract;
use App\Models\CrewFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrewFeedbackPublicController extends Controller
{
    use VerifiesCandidateToken;

    /**
     * Seafarer submits vessel/company rating after contract ends.
     */
    public function store(Request $request, string $id): JsonResponse
    {
        [$candidate, $error] = $this->resolveAndVerifyCandidate($id, $request);
        if ($error) {
            return $error;
        }

        $request->validate([
            'candidate_contract_id' => 'required|uuid',
            'rating_overall'        => 'required|integer|min:1|max:5',
            'rating_competence'     => 'nullable|integer|min:1|max:5',
            'rating_teamwork'       => 'nullable|integer|min:1|max:5',
            'rating_reliability'    => 'nullable|integer|min:1|max:5',
            'rating_communication'  => 'nullable|integer|min:1|max:5',
            'comment'               => 'nullable|string|max:2000',
            'is_anonymous'          => 'nullable|boolean',
        ]);

        // Verify contract belongs to this candidate AND has ended
        $contract = CandidateContract::where('id', $request->input('candidate_contract_id'))
            ->where('pool_candidate_id', $candidate->id)
            ->first();

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found or does not belong to this candidate',
            ], 404);
        }

        if ($contract->isOngoing()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot submit feedback for an ongoing contract',
            ], 422);
        }

        // Check for duplicate (DB unique constraint also enforces this)
        $existing = CrewFeedback::where('candidate_contract_id', $contract->id)
            ->where('feedback_type', CrewFeedback::TYPE_SEAFARER_RATES_VESSEL)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback already submitted for this contract',
            ], 409);
        }

        $feedback = CrewFeedback::create([
            'candidate_contract_id' => $contract->id,
            'pool_candidate_id'     => $candidate->id,
            'vessel_id'             => $contract->vessel_id,
            'feedback_type'         => CrewFeedback::TYPE_SEAFARER_RATES_VESSEL,
            'rating_overall'        => $request->input('rating_overall'),
            'rating_competence'     => $request->input('rating_competence'),
            'rating_teamwork'       => $request->input('rating_teamwork'),
            'rating_reliability'    => $request->input('rating_reliability'),
            'rating_communication'  => $request->input('rating_communication'),
            'comment'               => $request->input('comment'),
            'is_anonymous'          => $request->boolean('is_anonymous', false),
            'status'                => CrewFeedback::STATUS_PENDING,
        ]);

        // Auto-flag suspicious feedback
        if ($feedback->isSuspicious()) {
            $feedback->update(['status' => CrewFeedback::STATUS_FLAGGED]);
        }

        return response()->json([
            'success' => true,
            'data' => $feedback,
        ], 201);
    }
}
