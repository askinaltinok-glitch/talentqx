<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\MarketplaceAccessRequestedMail;
use App\Models\Candidate;
use App\Models\MarketplaceAccessRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Marketplace Controller
 *
 * Handles marketplace candidate listing and access requests.
 * All endpoints require authentication and premium subscription.
 *
 * NOTE: Marketplace is DISABLED in v1 via MARKETPLACE_ENABLED feature flag.
 */
class MarketplaceController extends Controller
{
    /**
     * Check if marketplace feature is enabled.
     */
    private function isMarketplaceEnabled(): bool
    {
        return config('app.marketplace_enabled', false);
    }

    /**
     * Return 503 Service Unavailable when marketplace is disabled.
     */
    private function marketplaceDisabledResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'MARKETPLACE_UNAVAILABLE',
                'message' => 'Aday Havuzu özelliği şu anda aktif değildir. Yakında hizmetinizde olacak.',
            ],
        ], 503);
    }

    /**
     * Check if user has marketplace access (platform admin or premium subscription).
     */
    private function hasMarketplaceAccess(Request $request): bool
    {
        $user = $request->user();

        // Platform admins always have access
        if ($user->is_platform_admin) {
            return true;
        }

        // Check company's premium/marketplace access
        return $user->company && $user->company->hasMarketplaceAccess();
    }

    /**
     * GET /marketplace/candidates
     * List candidates from other companies (anonymized profiles).
     * Platform admins see full info with company details.
     */
    public function listCandidates(Request $request): JsonResponse
    {
        $user = $request->user();

        // Platform admin can always access, others need feature flag
        if (!$user->is_platform_admin && !$this->isMarketplaceEnabled()) {
            return $this->marketplaceDisabledResponse();
        }

        // Check marketplace access (platform admin or premium)
        if (!$this->hasMarketplaceAccess($request)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MARKETPLACE_ACCESS_REQUIRED',
                    'message' => 'Premium abonelik ve marketplace erişimi gereklidir.',
                ],
            ], 403);
        }

        $perPage = min($request->input('per_page', 100), 200);

        // Get candidates - for admin include company info
        $query = Candidate::with([
            'job:id,title,location,company_id',
            'job.company:id,name',
            'job.branch:id,name',
            'latestInterview.analysis'
        ]);

        // Only filter by completed interviews if not admin requesting all
        if (!$user->is_platform_admin || !$request->boolean('include_all')) {
            $query->whereHas('interviews', function ($q) {
                $q->where('status', 'completed');
            });
        }

        // For non-admin users, exclude their own company's candidates
        if (!$user->is_platform_admin && $user->company_id) {
            $query->whereHas('job', function ($q) use ($user) {
                $q->where('company_id', '!=', $user->company_id);
            });
        }

        // Filters
        if ($request->filled('min_score')) {
            $minScore = (int) $request->input('min_score');
            $query->whereHas('latestInterview.analysis', function ($q) use ($minScore) {
                $q->where('overall_score', '>=', $minScore);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('company_id')) {
            $query->whereHas('job', function ($q) use ($request) {
                $q->where('company_id', $request->input('company_id'));
            });
        }

        $candidates = $query->orderByDesc('created_at')->paginate($perPage);

        // Platform admin gets full info with company details
        if ($user->is_platform_admin) {
            $fullCandidates = $candidates->getCollection()->map(function ($candidate) {
                return $this->adminCandidateView($candidate);
            });

            return response()->json([
                'success' => true,
                'data' => $fullCandidates,
                'meta' => [
                    'current_page' => $candidates->currentPage(),
                    'per_page' => $candidates->perPage(),
                    'total' => $candidates->total(),
                    'last_page' => $candidates->lastPage(),
                ],
            ]);
        }

        // Transform to anonymous profiles for non-admins
        $anonymizedCandidates = $candidates->getCollection()->map(function ($candidate) use ($user) {
            return $this->anonymizeCandidate($candidate, $user->company_id);
        });

        return response()->json([
            'success' => true,
            'data' => $anonymizedCandidates,
            'meta' => [
                'current_page' => $candidates->currentPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
                'last_page' => $candidates->lastPage(),
            ],
        ]);
    }

    /**
     * Full candidate view for platform admins.
     */
    private function adminCandidateView(Candidate $candidate): array
    {
        $analysis = $candidate->latestInterview?->analysis;
        $job = $candidate->job;
        $company = $job?->company;
        $branch = $job?->branch;

        return [
            'id' => $candidate->id,
            'first_name' => $candidate->first_name,
            'last_name' => $candidate->last_name,
            'full_name' => trim($candidate->first_name . ' ' . $candidate->last_name),
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'status' => $candidate->status,
            'cv_match_score' => $candidate->cv_match_score,
            'source' => $candidate->source,
            'created_at' => $candidate->created_at->toIso8601String(),
            // Job & Company info
            'job_id' => $job?->id,
            'job_title' => $job?->title,
            'job_location' => $job?->location,
            'company_id' => $company?->id,
            'company_name' => $company?->name,
            'branch_name' => $branch?->name,
            // Combined company info for display
            'company_display' => $this->buildCompanyDisplay($company, $branch, $job),
            // Analysis
            'overall_score' => $analysis?->overall_score,
            'recommendation' => $analysis?->decision_snapshot['recommendation'] ?? null,
            'interview_completed_at' => $candidate->latestInterview?->completed_at?->toIso8601String(),
        ];
    }

    /**
     * Build company display string: "Şirket Adı - Şube - İlan"
     */
    private function buildCompanyDisplay(?object $company, ?object $branch, ?object $job): string
    {
        $parts = [];

        if ($company?->name) {
            $parts[] = $company->name;
        }

        if ($branch?->name) {
            $parts[] = $branch->name;
        }

        if ($job?->title) {
            $parts[] = $job->title;
        }

        return implode(' - ', $parts) ?: '-';
    }

    /**
     * POST /marketplace/candidates/{id}/request-access
     * Request access to a candidate's full profile.
     * Note: Platform admins don't need to request access - they have full access.
     */
    public function requestAccess(Request $request, string $candidateId): JsonResponse
    {
        $user = $request->user();

        // Platform admin already has full access, no need to request
        if ($user->is_platform_admin) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_HAS_ACCESS',
                    'message' => 'Platform yöneticisi olarak tüm adaylara zaten erişiminiz var.',
                ],
            ], 400);
        }

        // Feature flag check for non-admins
        if (!$this->isMarketplaceEnabled()) {
            return $this->marketplaceDisabledResponse();
        }

        // Check marketplace access (premium)
        if (!$this->hasMarketplaceAccess($request)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MARKETPLACE_ACCESS_REQUIRED',
                    'message' => 'Premium abonelik ve marketplace erişimi gereklidir.',
                ],
            ], 403);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        // Find candidate
        $candidate = Candidate::with('job.company')->find($candidateId);

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANDIDATE_NOT_FOUND',
                    'message' => 'Aday bulunamadı.',
                ],
            ], 404);
        }

        $owningCompanyId = $candidate->job->company_id;

        // Can't request access to own company's candidates
        if ($owningCompanyId === $user->company_id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'OWN_CANDIDATE',
                    'message' => 'Kendi şirketinizin adaylarına erişim talep edemezsiniz.',
                ],
            ], 400);
        }

        // Check for existing pending request
        $existingRequest = MarketplaceAccessRequest::where('requesting_company_id', $user->company_id)
            ->where('candidate_id', $candidateId)
            ->where('status', MarketplaceAccessRequest::STATUS_PENDING)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REQUEST_EXISTS',
                    'message' => 'Bu aday için zaten bekleyen bir erişim talebiniz var.',
                ],
            ], 400);
        }

        // Check for existing approved request (still valid)
        $approvedRequest = MarketplaceAccessRequest::where('requesting_company_id', $user->company_id)
            ->where('candidate_id', $candidateId)
            ->where('status', MarketplaceAccessRequest::STATUS_APPROVED)
            ->first();

        if ($approvedRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_APPROVED',
                    'message' => 'Bu adaya zaten erişiminiz var.',
                ],
            ], 400);
        }

        // Create access request
        $accessRequest = MarketplaceAccessRequest::create([
            'requesting_company_id' => $user->company_id,
            'requesting_user_id' => $user->id,
            'candidate_id' => $candidateId,
            'owning_company_id' => $owningCompanyId,
            'status' => MarketplaceAccessRequest::STATUS_PENDING,
            'request_message' => $validated['message'] ?? null,
            'access_token' => MarketplaceAccessRequest::generateToken(),
            'token_expires_at' => now()->addDays(7), // 7 days to respond
        ]);

        Log::info('Marketplace access requested', [
            'request_id' => $accessRequest->id,
            'requesting_company_id' => $user->company_id,
            'candidate_id' => $candidateId,
            'owning_company_id' => $owningCompanyId,
        ]);

        // Notify platform admins about the new access request
        $adminEmail = config('mail.admin_address', 'admin@octopus-ai.net');
        Mail::to($adminEmail)->queue(new MarketplaceAccessRequestedMail($accessRequest));

        try {
            app(\App\Services\AdminNotificationService::class)->notifyEmailSent(
                'marketplace_access_requested',
                $adminEmail,
                "Marketplace access request: {$accessRequest->id}",
                ['request_id' => $accessRequest->id]
            );
        } catch (\Throwable) {}

        return response()->json([
            'success' => true,
            'data' => [
                'request_id' => $accessRequest->id,
                'status' => $accessRequest->status,
                'token_expires_at' => $accessRequest->token_expires_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * GET /marketplace/candidates/{id}/full-profile
     * Get full profile of a candidate.
     * Platform admins have direct access, others need approved access request.
     */
    public function getFullProfile(Request $request, string $candidateId): JsonResponse
    {
        $user = $request->user();

        // Platform admin can always access, others need feature flag
        if (!$user->is_platform_admin && !$this->isMarketplaceEnabled()) {
            return $this->marketplaceDisabledResponse();
        }

        // Check marketplace access (platform admin or premium)
        if (!$this->hasMarketplaceAccess($request)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MARKETPLACE_ACCESS_REQUIRED',
                    'message' => 'Premium abonelik ve marketplace erişimi gereklidir.',
                ],
            ], 403);
        }

        // Find candidate with full data
        $candidate = Candidate::with(['job:id,title,location,company_id', 'job.company:id,name', 'job.branch:id,name', 'interview.analysis'])
            ->find($candidateId);

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANDIDATE_NOT_FOUND',
                    'message' => 'Aday bulunamadı.',
                ],
            ], 404);
        }

        // Platform admin has full access to everything
        if ($user->is_platform_admin) {
            return response()->json([
                'success' => true,
                'data' => $this->fullCandidateProfile($candidate),
            ]);
        }

        $owningCompanyId = $candidate->job->company_id;

        // Own company's candidate - full access
        if ($owningCompanyId === $user->company_id) {
            return response()->json([
                'success' => true,
                'data' => $this->fullCandidateProfile($candidate),
            ]);
        }

        // Check for approved access
        $approvedRequest = MarketplaceAccessRequest::where('requesting_company_id', $user->company_id)
            ->where('candidate_id', $candidateId)
            ->where('status', MarketplaceAccessRequest::STATUS_APPROVED)
            ->first();

        if (!$approvedRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ACCESS_NOT_APPROVED',
                    'message' => 'Bu adayın tam profiline erişiminiz yok. Önce erişim talep edin.',
                ],
            ], 403);
        }

        Log::info('Marketplace full profile accessed', [
            'request_id' => $approvedRequest->id,
            'requesting_company_id' => $user->company_id,
            'candidate_id' => $candidateId,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->fullCandidateProfile($candidate, $approvedRequest->responded_at),
        ]);
    }

    /**
     * GET /marketplace/my-requests
     * List my access requests.
     */
    public function myRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        // Platform admin can always access
        if (!$user->is_platform_admin && !$this->isMarketplaceEnabled()) {
            return $this->marketplaceDisabledResponse();
        }

        $user = $request->user();
        $perPage = min($request->input('per_page', 20), 50);

        $query = MarketplaceAccessRequest::with([
            'candidate' => function ($q) {
                $q->select('id', 'status', 'cv_match_score', 'source', 'created_at');
            },
            'candidate.job:id,title,location',
            'candidate.interview.analysis:id,interview_id,overall_score',
        ])
            ->where('requesting_company_id', $user->company_id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $requests = $query->paginate($perPage);

        $transformedRequests = $requests->getCollection()->map(function ($req) {
            $candidate = $req->candidate;
            $analysis = $candidate?->interview?->analysis;

            return [
                'id' => $req->id,
                'status' => $req->status,
                'request_message' => $req->request_message,
                'response_message' => $req->response_message,
                'created_at' => $req->created_at->toIso8601String(),
                'responded_at' => $req->responded_at?->toIso8601String(),
                'token_expires_at' => $req->token_expires_at->toIso8601String(),
                'candidate' => $candidate ? [
                    'id' => $candidate->id,
                    'status' => $candidate->status,
                    'cv_match_score' => $candidate->cv_match_score,
                    'overall_score' => $analysis?->overall_score,
                    'job_title' => $candidate->job?->title,
                    'job_location' => $candidate->job?->location,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedRequests,
            'meta' => [
                'current_page' => $requests->currentPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
                'last_page' => $requests->lastPage(),
            ],
        ]);
    }

    /**
     * Anonymize candidate data for marketplace listing.
     */
    private function anonymizeCandidate(Candidate $candidate, ?string $requestingCompanyId): array
    {
        $analysis = $candidate->latestInterview?->analysis;

        // Check if there's an existing request (only if company_id is available)
        $accessRequest = null;
        if ($requestingCompanyId) {
            $accessRequest = MarketplaceAccessRequest::where('requesting_company_id', $requestingCompanyId)
                ->where('candidate_id', $candidate->id)
                ->latest()
                ->first();
        }

        return [
            'id' => $candidate->id,
            'status' => $candidate->status,
            'cv_match_score' => $candidate->cv_match_score,
            'source' => $candidate->source,
            'created_at' => $candidate->created_at->toIso8601String(),
            // Job info (anonymized company)
            'job_title' => $candidate->job?->title,
            'job_location' => $candidate->job?->location,
            // Analysis summary
            'overall_score' => $analysis?->overall_score,
            'recommendation' => $analysis?->decision_snapshot['recommendation'] ?? null,
            // Access request status
            'access_request_status' => $accessRequest?->status,
            'access_request_id' => $accessRequest?->id,
        ];
    }

    /**
     * Get full candidate profile data.
     */
    private function fullCandidateProfile(Candidate $candidate, ?\DateTimeInterface $accessGrantedAt = null): array
    {
        $analysis = $candidate->interview?->analysis;

        return [
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
            'latest_analysis' => $analysis ? [
                'overall_score' => $analysis->overall_score,
                'competency_scores' => $analysis->competency_scores ?? [],
                'recommendation' => $analysis->decision_snapshot['recommendation'] ?? null,
                'analyzed_at' => $analysis->analyzed_at?->toIso8601String(),
            ] : null,
            'access_granted_at' => $accessGrantedAt?->format('c'),
        ];
    }
}
