<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\MarketplaceAccessRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    /**
     * List marketplace candidates (anonymous profiles).
     * GET /v1/marketplace/candidates
     *
     * Requires premium subscription.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        // Check premium access
        if (!$company->hasMarketplaceAccess()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'premium_required',
                    'message' => 'Marketplace erişimi için premium abonelik gereklidir.',
                ],
            ], 403);
        }

        $query = Candidate::marketplaceVisible()
            ->with(['job:id,title,location', 'latestInterview.analysis'])
            // Exclude own company's candidates
            ->where('company_id', '!=', $company->id);

        // Filter by skills
        if ($request->has('skills')) {
            $skills = is_array($request->skills) ? $request->skills : explode(',', $request->skills);
            $query->where(function ($q) use ($skills) {
                foreach ($skills as $skill) {
                    $q->orWhereJsonContains('cv_parsed_data->skills', trim($skill));
                }
            });
        }

        // Filter by minimum score
        if ($request->has('min_score')) {
            $query->whereHas('latestInterview.analysis', function ($q) use ($request) {
                $q->where('overall_score', '>=', (int) $request->min_score);
            });
        }

        // Filter by experience years
        if ($request->has('min_experience')) {
            $query->whereRaw("JSON_EXTRACT(cv_parsed_data, '$.experience_years') >= ?", [(int) $request->min_experience]);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $candidates = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        // Transform to anonymous profiles
        $anonymousProfiles = $candidates->getCollection()->map(function ($candidate) use ($company) {
            $profile = $candidate->getAnonymousProfile();

            // Add request status if the company has requested this candidate
            $existingRequest = MarketplaceAccessRequest::where('requesting_company_id', $company->id)
                ->where('candidate_id', $candidate->id)
                ->latest()
                ->first();

            $profile['access_request_status'] = $existingRequest?->status;
            $profile['access_request_id'] = $existingRequest?->id;

            return $profile;
        });

        return response()->json([
            'success' => true,
            'data' => $anonymousProfiles,
            'meta' => [
                'current_page' => $candidates->currentPage(),
                'last_page' => $candidates->lastPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
            ],
        ]);
    }

    /**
     * Request access to a candidate's full profile.
     * POST /v1/marketplace/candidates/{id}/request-access
     */
    public function requestAccess(Request $request, string $id): JsonResponse
    {
        $company = $request->user()->company;

        // Check premium access
        if (!$company->hasMarketplaceAccess()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'premium_required',
                    'message' => 'Marketplace erişimi için premium abonelik gereklidir.',
                ],
            ], 403);
        }

        $candidate = Candidate::marketplaceVisible()
            ->where('company_id', '!=', $company->id)
            ->findOrFail($id);

        // Check for existing pending request
        $existingRequest = MarketplaceAccessRequest::where('requesting_company_id', $company->id)
            ->where('candidate_id', $candidate->id)
            ->where('status', MarketplaceAccessRequest::STATUS_PENDING)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'request_pending',
                    'message' => 'Bu aday için zaten bekleyen bir erişim talebiniz var.',
                    'request_id' => $existingRequest->id,
                ],
            ], 400);
        }

        // Check for existing approved request
        $approvedRequest = MarketplaceAccessRequest::where('requesting_company_id', $company->id)
            ->where('candidate_id', $candidate->id)
            ->where('status', MarketplaceAccessRequest::STATUS_APPROVED)
            ->first();

        if ($approvedRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'already_approved',
                    'message' => 'Bu aday profili için zaten erişim izniniz var.',
                    'request_id' => $approvedRequest->id,
                ],
            ], 400);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        $accessRequest = MarketplaceAccessRequest::create([
            'requesting_company_id' => $company->id,
            'requesting_user_id' => $request->user()->id,
            'candidate_id' => $candidate->id,
            'request_message' => $validated['message'] ?? null,
        ]);

        // TODO: Send notification to the candidate's company or candidate

        return response()->json([
            'success' => true,
            'data' => [
                'request_id' => $accessRequest->id,
                'status' => $accessRequest->status,
                'token_expires_at' => $accessRequest->token_expires_at->toIso8601String(),
            ],
            'message' => 'Erişim talebi başarıyla oluşturuldu.',
        ], 201);
    }

    /**
     * Get full profile of a candidate (requires approved access).
     * GET /v1/marketplace/candidates/{id}/full-profile
     */
    public function fullProfile(Request $request, string $id): JsonResponse
    {
        $company = $request->user()->company;

        // Check premium access
        if (!$company->hasMarketplaceAccess()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'premium_required',
                    'message' => 'Marketplace erişimi için premium abonelik gereklidir.',
                ],
            ], 403);
        }

        $candidate = Candidate::marketplaceVisible()
            ->where('company_id', '!=', $company->id)
            ->with(['job', 'latestInterview.analysis'])
            ->findOrFail($id);

        // Check for approved access request
        $approvedRequest = MarketplaceAccessRequest::where('requesting_company_id', $company->id)
            ->where('candidate_id', $candidate->id)
            ->where('status', MarketplaceAccessRequest::STATUS_APPROVED)
            ->first();

        if (!$approvedRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'access_not_granted',
                    'message' => 'Bu aday profili için erişim izniniz yok.',
                ],
            ], 403);
        }

        // Return full profile with PII
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $candidate->id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'status' => $candidate->status,
                'cv_match_score' => $candidate->cv_match_score,
                'cv_parsed_data' => $candidate->cv_parsed_data,
                'source' => $candidate->source,
                'created_at' => $candidate->created_at->toIso8601String(),
                'job' => $candidate->job ? [
                    'id' => $candidate->job->id,
                    'title' => $candidate->job->title,
                    'location' => $candidate->job->location,
                ] : null,
                'latest_analysis' => $candidate->getLatestAnalysis() ? [
                    'overall_score' => $candidate->getLatestAnalysis()->overall_score,
                    'competency_scores' => $candidate->getLatestAnalysis()->competency_scores,
                    'recommendation' => $candidate->getLatestAnalysis()->decision_snapshot['recommendation'] ?? null,
                    'analyzed_at' => $candidate->getLatestAnalysis()->analyzed_at?->toIso8601String(),
                ] : null,
                'access_granted_at' => $approvedRequest->responded_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * List my access requests.
     * GET /v1/marketplace/my-requests
     */
    public function myRequests(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        $query = MarketplaceAccessRequest::where('requesting_company_id', $company->id)
            ->with(['candidate:id,first_name,last_name,status,cv_match_score']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        // Transform data (hide PII for pending/rejected requests)
        $transformedRequests = $requests->getCollection()->map(function ($req) {
            $candidateData = null;

            if ($req->candidate) {
                if ($req->isApproved()) {
                    // Show full info for approved
                    $candidateData = [
                        'id' => $req->candidate->id,
                        'first_name' => $req->candidate->first_name,
                        'last_name' => $req->candidate->last_name,
                        'status' => $req->candidate->status,
                        'cv_match_score' => $req->candidate->cv_match_score,
                    ];
                } else {
                    // Anonymous for pending/rejected
                    $candidateData = $req->candidate->getAnonymousProfile();
                }
            }

            return [
                'id' => $req->id,
                'status' => $req->status,
                'request_message' => $req->request_message,
                'response_message' => $req->response_message,
                'created_at' => $req->created_at->toIso8601String(),
                'responded_at' => $req->responded_at?->toIso8601String(),
                'token_expires_at' => $req->token_expires_at->toIso8601String(),
                'candidate' => $candidateData,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedRequests,
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }
}
