<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccessRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceAccessController extends Controller
{
    /**
     * Show access request details by token.
     * GET /v1/marketplace-access/{token}
     *
     * Public endpoint - no auth required.
     */
    public function show(string $token): JsonResponse
    {
        $accessRequest = MarketplaceAccessRequest::findByToken($token);

        if (!$accessRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'token_invalid',
                    'message' => 'Geçersiz erişim token\'ı.',
                ],
            ], 404);
        }

        // Check if token is expired
        if (!$accessRequest->isTokenValid()) {
            // Mark as expired if still pending
            if ($accessRequest->isPending()) {
                $accessRequest->markExpired();
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'token_expired',
                    'message' => 'Bu erişim talebi süresi dolmuş.',
                    'expired_at' => $accessRequest->token_expires_at->toIso8601String(),
                ],
            ], 410);
        }

        // Check if already processed
        if (!$accessRequest->isPending()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'already_processed',
                    'message' => 'Bu erişim talebi zaten işlenmiş.',
                    'status' => $accessRequest->status,
                    'processed_at' => $accessRequest->responded_at?->toIso8601String(),
                ],
            ], 400);
        }

        $accessRequest->load(['requestingCompany:id,name', 'requestingUser:id,name,email', 'candidate']);

        // Get anonymous profile of the candidate
        $candidateProfile = $accessRequest->candidate?->getAnonymousProfile();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $accessRequest->id,
                'status' => $accessRequest->status,
                'requesting_company' => [
                    'name' => $accessRequest->requestingCompany->name,
                ],
                'requesting_user' => [
                    'name' => $accessRequest->requestingUser->name,
                    'email' => $accessRequest->requestingUser->email,
                ],
                'request_message' => $accessRequest->request_message,
                'created_at' => $accessRequest->created_at->toIso8601String(),
                'token_expires_at' => $accessRequest->token_expires_at->toIso8601String(),
                'candidate' => $candidateProfile,
            ],
        ]);
    }

    /**
     * Approve access request by token.
     * POST /v1/marketplace-access/{token}/approve
     *
     * Public endpoint - no auth required.
     */
    public function approve(Request $request, string $token): JsonResponse
    {
        $accessRequest = MarketplaceAccessRequest::findByToken($token);

        if (!$accessRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'token_invalid',
                    'message' => 'Geçersiz erişim token\'ı.',
                ],
            ], 404);
        }

        // Check if token is expired
        if (!$accessRequest->isTokenValid()) {
            if ($accessRequest->isPending()) {
                $accessRequest->markExpired();
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'token_expired',
                    'message' => 'Bu erişim talebi süresi dolmuş.',
                    'expired_at' => $accessRequest->token_expires_at->toIso8601String(),
                ],
            ], 410);
        }

        // Check if already processed
        if (!$accessRequest->isPending()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'already_processed',
                    'message' => 'Bu erişim talebi zaten işlenmiş.',
                    'status' => $accessRequest->status,
                    'processed_at' => $accessRequest->responded_at?->toIso8601String(),
                ],
            ], 400);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        $accessRequest->approve($validated['message'] ?? null);

        // TODO: Send notification to the requesting company

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $accessRequest->status,
                'responded_at' => $accessRequest->responded_at->toIso8601String(),
            ],
            'message' => 'Erişim talebi onaylandı.',
        ]);
    }

    /**
     * Reject access request by token.
     * POST /v1/marketplace-access/{token}/reject
     *
     * Public endpoint - no auth required.
     */
    public function reject(Request $request, string $token): JsonResponse
    {
        $accessRequest = MarketplaceAccessRequest::findByToken($token);

        if (!$accessRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'token_invalid',
                    'message' => 'Geçersiz erişim token\'ı.',
                ],
            ], 404);
        }

        // Check if token is expired
        if (!$accessRequest->isTokenValid()) {
            if ($accessRequest->isPending()) {
                $accessRequest->markExpired();
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'token_expired',
                    'message' => 'Bu erişim talebi süresi dolmuş.',
                    'expired_at' => $accessRequest->token_expires_at->toIso8601String(),
                ],
            ], 410);
        }

        // Check if already processed
        if (!$accessRequest->isPending()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'already_processed',
                    'message' => 'Bu erişim talebi zaten işlenmiş.',
                    'status' => $accessRequest->status,
                    'processed_at' => $accessRequest->responded_at?->toIso8601String(),
                ],
            ], 400);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        $accessRequest->reject($validated['message'] ?? null);

        // TODO: Send notification to the requesting company

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $accessRequest->status,
                'responded_at' => $accessRequest->responded_at->toIso8601String(),
            ],
            'message' => 'Erişim talebi reddedildi.',
        ]);
    }
}
