<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Services\Fleet\CaptainProfilingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaptainProfileController extends Controller
{
    public function __construct(
        private CaptainProfilingService $profiling,
    ) {}

    /**
     * GET /v1/portal/captains/{candidateId}/profile
     */
    public function show(Request $request, string $candidateId): JsonResponse
    {
        $profile = $this->profiling->getProfile($candidateId);

        if (!$profile) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No captain profile available for this candidate.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'candidate_id' => $profile->candidate_id,
                'style_vector' => $profile->style_vector_json,
                'command_profile' => $profile->command_profile_json,
                'evidence_counts' => $profile->evidence_counts_json,
                'confidence' => $profile->confidence,
                'last_computed_at' => $profile->last_computed_at?->toIso8601String(),
            ],
        ]);
    }
}
