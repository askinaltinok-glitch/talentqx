<?php

namespace App\Http\Controllers\Api\Maritime\Concerns;

use App\Models\PoolCandidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait VerifiesCandidateToken
{
    /**
     * Resolve candidate by UUID and verify the signed public_token from ?t= param.
     * Returns [candidate, null] on success, or [null, errorResponse] on failure.
     *
     * Verification strategy:
     * 1. If token enforcement is disabled (grace period), skip verification.
     * 2. Prefer hash-based verification: hash(incoming_token) == stored_hash.
     * 3. Legacy fallback: hash_equals(stored_plaintext, incoming_token).
     *
     * @return array{0: ?PoolCandidate, 1: ?JsonResponse}
     */
    protected function resolveAndVerifyCandidate(string $id, ?Request $request = null): array
    {
        $candidate = PoolCandidate::find($id);

        if (!$candidate) {
            return [null, response()->json(['success' => false, 'message' => 'Candidate not found.'], 404)];
        }

        // Grace period: skip token verification if enforcement is off
        if (!config('maritime.token_enforcement', true)) {
            return [$candidate, null];
        }

        $token = $request?->query('t') ?? request()->query('t');

        if (!$token) {
            return [null, response()->json(['success' => false, 'message' => 'Invalid or missing token.'], 403)];
        }

        // Primary: hash-based verification (DB stores hash, URL carries plaintext)
        if ($candidate->public_token_hash) {
            $incomingHash = hash('sha256', $token);
            if (hash_equals($candidate->public_token_hash, $incomingHash)) {
                return [$candidate, null];
            }
        }

        // Legacy fallback: plaintext comparison (will be removed after full migration)
        if ($candidate->public_token && hash_equals($candidate->public_token, $token)) {
            return [$candidate, null];
        }

        return [null, response()->json(['success' => false, 'message' => 'Invalid or missing token.'], 403)];
    }
}
