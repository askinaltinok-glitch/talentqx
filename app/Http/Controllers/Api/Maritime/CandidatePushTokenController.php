<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Controller;
use App\Models\CandidatePushToken;
use App\Models\PoolCandidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidatePushTokenController extends Controller
{
    /**
     * POST /v1/maritime/candidates/{id}/push-token
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'candidate_email' => ['required', 'email'],
            'device_type' => ['required', 'string', 'in:' . implode(',', CandidatePushToken::DEVICE_TYPES)],
            'token' => ['required', 'string', 'max:512'],
        ]);

        // Verify candidate identity
        $candidate = PoolCandidate::where('id', $id)
            ->where('email', $data['candidate_email'])
            ->firstOrFail();

        $pushToken = CandidatePushToken::updateOrCreate(
            [
                'pool_candidate_id' => $candidate->id,
                'token' => $data['token'],
            ],
            [
                'device_type' => $data['device_type'],
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Push token registered.',
            'data' => [
                'id' => $pushToken->id,
                'device_type' => $pushToken->device_type,
            ],
        ], 201);
    }

    /**
     * DELETE /v1/maritime/candidates/{id}/push-token
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $deleted = CandidatePushToken::where('pool_candidate_id', $id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted ? 'Push token removed.' : 'Token not found.',
        ]);
    }
}
