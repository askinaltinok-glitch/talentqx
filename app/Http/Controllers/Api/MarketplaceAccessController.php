<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\MarketplaceAccessRespondedMail;
use App\Models\MarketplaceAccessRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Marketplace Access Controller (Public)
 *
 * Handles public token-based access request approval/rejection.
 * No authentication required - uses access tokens.
 */
class MarketplaceAccessController extends Controller
{
    /**
     * GET /marketplace-access/{token}
     * Get access request details by token (public).
     */
    public function show(string $token): JsonResponse
    {
        $accessRequest = MarketplaceAccessRequest::with([
            'requestingCompany:id,name',
            'requestingUser:id,first_name,last_name,email',
            'candidate' => function ($q) {
                $q->select('id', 'first_name', 'last_name', 'status', 'cv_match_score');
            },
            'candidate.job:id,title',
            'candidate.interview.analysis:id,interview_id,overall_score',
        ])
            ->where('access_token', $token)
            ->first();

        if (!$accessRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REQUEST_NOT_FOUND',
                    'message' => 'Erişim talebi bulunamadı veya geçersiz token.',
                ],
            ], 404);
        }

        // Check if token is expired
        if ($accessRequest->isTokenExpired()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => 'Bu erişim talebi için yanıt süresi dolmuş.',
                ],
            ], 410);
        }

        $candidate = $accessRequest->candidate;
        $analysis = $candidate?->interview?->analysis;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $accessRequest->id,
                'status' => $accessRequest->status,
                'requesting_company' => [
                    'name' => $accessRequest->requestingCompany->name,
                ],
                'requesting_user' => [
                    'name' => trim($accessRequest->requestingUser->first_name . ' ' . $accessRequest->requestingUser->last_name),
                    'email' => $accessRequest->requestingUser->email,
                ],
                'request_message' => $accessRequest->request_message,
                'created_at' => $accessRequest->created_at->toIso8601String(),
                'token_expires_at' => $accessRequest->token_expires_at->toIso8601String(),
                'candidate' => $candidate ? [
                    'id' => $candidate->id,
                    'name' => trim($candidate->first_name . ' ' . $candidate->last_name),
                    'status' => $candidate->status,
                    'job_title' => $candidate->job?->title,
                    'overall_score' => $analysis?->overall_score,
                ] : null,
            ],
        ]);
    }

    /**
     * POST /marketplace-access/{token}/approve
     * Approve access request by token (public).
     */
    public function approve(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        $accessRequest = MarketplaceAccessRequest::where('access_token', $token)->first();

        if (!$accessRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REQUEST_NOT_FOUND',
                    'message' => 'Erişim talebi bulunamadı veya geçersiz token.',
                ],
            ], 404);
        }

        // Check if token is expired
        if ($accessRequest->isTokenExpired()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => 'Bu erişim talebi için yanıt süresi dolmuş.',
                ],
            ], 410);
        }

        // Check if already responded
        if (!$accessRequest->isPending()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_RESPONDED',
                    'message' => 'Bu erişim talebine zaten yanıt verilmiş.',
                ],
            ], 400);
        }

        $accessRequest->approve($validated['message'] ?? null);

        Log::info('Marketplace access request approved', [
            'request_id' => $accessRequest->id,
            'requesting_company_id' => $accessRequest->requesting_company_id,
            'candidate_id' => $accessRequest->candidate_id,
        ]);

        // Notify requesting company about approval
        $requesterEmail = $accessRequest->requestingUser?->email;
        if ($requesterEmail) {
            Mail::to($requesterEmail)->queue(new MarketplaceAccessRespondedMail($accessRequest, 'approved'));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $accessRequest->status,
                'responded_at' => $accessRequest->responded_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /marketplace-access/{token}/reject
     * Reject access request by token (public).
     */
    public function reject(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        $accessRequest = MarketplaceAccessRequest::where('access_token', $token)->first();

        if (!$accessRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REQUEST_NOT_FOUND',
                    'message' => 'Erişim talebi bulunamadı veya geçersiz token.',
                ],
            ], 404);
        }

        // Check if token is expired
        if ($accessRequest->isTokenExpired()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => 'Bu erişim talebi için yanıt süresi dolmuş.',
                ],
            ], 410);
        }

        // Check if already responded
        if (!$accessRequest->isPending()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_RESPONDED',
                    'message' => 'Bu erişim talebine zaten yanıt verilmiş.',
                ],
            ], 400);
        }

        $accessRequest->reject($validated['message'] ?? null);

        Log::info('Marketplace access request rejected', [
            'request_id' => $accessRequest->id,
            'requesting_company_id' => $accessRequest->requesting_company_id,
            'candidate_id' => $accessRequest->candidate_id,
        ]);

        // Notify requesting company about rejection
        $requesterEmail = $accessRequest->requestingUser?->email;
        if ($requesterEmail) {
            Mail::to($requesterEmail)->queue(new MarketplaceAccessRespondedMail($accessRequest, 'rejected'));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $accessRequest->status,
                'responded_at' => $accessRequest->responded_at->toIso8601String(),
            ],
        ]);
    }
}
