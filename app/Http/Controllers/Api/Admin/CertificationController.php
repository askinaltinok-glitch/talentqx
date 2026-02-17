<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateType;
use App\Models\SeafarerCertificate;
use App\Services\Certification\CertificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CertificationController — Admin endpoints for certificate management.
 *
 * Verify/reject certificates, check candidate compliance,
 * get certification analytics, and filter certification-ready candidates.
 */
class CertificationController extends Controller
{
    public function __construct(
        private CertificationService $certificationService
    ) {}

    /**
     * GET /v1/admin/certificate-types
     *
     * List all certificate types, optionally grouped by category.
     */
    public function types(Request $request): JsonResponse
    {
        $query = CertificateType::active()->orderBy('sort_order');

        if ($request->filled('category')) {
            $query->category($request->input('category'));
        }

        $types = $query->get();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * POST /v1/admin/certificates/{id}/verify
     *
     * Verify a certificate.
     */
    public function verify(string $id, Request $request): JsonResponse
    {
        try {
            $certificate = $this->certificationService->verifyCertificate(
                $id,
                $request->input('verified_by'),
                $request->input('notes')
            );

            return response()->json([
                'success' => true,
                'message' => 'Certificate verified.',
                'data' => $certificate,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found.',
            ], 404);
        }
    }

    /**
     * POST /v1/admin/certificates/{id}/reject
     *
     * Reject a certificate.
     */
    public function reject(string $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Rejection reason is required.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $certificate = $this->certificationService->rejectCertificate(
                $id,
                $request->input('rejected_by'),
                $request->input('notes')
            );

            return response()->json([
                'success' => true,
                'message' => 'Certificate rejected.',
                'data' => $certificate,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found.',
            ], 404);
        }
    }

    /**
     * GET /v1/admin/candidates/{id}/certification-status
     *
     * Full certification status for a candidate.
     */
    public function candidateStatus(string $id): JsonResponse
    {
        try {
            $status = $this->certificationService->getCandidateCertificationStatus($id);

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate not found.',
            ], 404);
        }
    }

    /**
     * GET /v1/admin/candidates/{id}/stcw-compliance
     *
     * STCW compliance check for a candidate.
     */
    public function stcwCompliance(string $id, Request $request): JsonResponse
    {
        $rank = $request->input('rank');
        $vesselType = $request->input('vessel_type', 'any');

        if (!$rank) {
            // Try to get rank from candidate source_meta
            $candidate = \App\Models\PoolCandidate::findOrFail($id);
            $rank = $candidate->source_meta['rank'] ?? null;

            if (!$rank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rank is required. Provide ?rank= or ensure candidate has a rank in source_meta.',
                ], 422);
            }
        }

        $compliance = $this->certificationService->checkSTCWCompliance($id, $rank, $vesselType);

        return response()->json([
            'success' => true,
            'data' => $compliance,
        ]);
    }

    /**
     * GET /v1/admin/candidates/{id}/certification-summary
     *
     * Decision packet extension — certification summary.
     */
    public function certificationSummary(string $id): JsonResponse
    {
        try {
            $summary = $this->certificationService->getCertificationSummary($id);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate not found.',
            ], 404);
        }
    }

    /**
     * GET /v1/admin/talent-requests/{id}/certification-ready
     *
     * Get candidates who are certification-ready for a talent request.
     */
    public function certificationReady(string $id): JsonResponse
    {
        $talentRequest = \App\Models\TalentRequest::findOrFail($id);

        $rankCode = $talentRequest->position_code ?? null;
        $vesselType = $talentRequest->vessel_type ?? 'any';

        if (!$rankCode) {
            return response()->json([
                'success' => false,
                'message' => 'Talent request has no position code.',
            ], 422);
        }

        $candidateIds = $this->certificationService->getCertificationReadyCandidates(
            $rankCode,
            $vesselType,
            50
        );

        return response()->json([
            'success' => true,
            'data' => [
                'talent_request_id' => $id,
                'rank_code' => $rankCode,
                'vessel_type' => $vesselType,
                'certification_ready_count' => count($candidateIds),
                'candidate_ids' => $candidateIds,
            ],
        ]);
    }

    /**
     * GET /v1/admin/certificates
     *
     * List all certificates with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SeafarerCertificate::query()
            ->with(['candidate:id,first_name,last_name,email']);

        if ($request->filled('status')) {
            $query->status($request->input('status'));
        }

        if ($request->filled('certificate_type')) {
            $query->where('certificate_type', $request->input('certificate_type'));
        }

        if ($request->filled('candidate_id')) {
            $query->forCandidate($request->input('candidate_id'));
        }

        if ($request->boolean('expired_only')) {
            $query->expired();
        }

        if ($request->boolean('expiring_soon')) {
            $query->expiringSoon(90);
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $certificates = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $certificates->items(),
            'meta' => [
                'current_page' => $certificates->currentPage(),
                'last_page' => $certificates->lastPage(),
                'per_page' => $certificates->perPage(),
                'total' => $certificates->total(),
            ],
        ]);
    }

    /**
     * GET /v1/admin/certification-analytics
     *
     * Certification analytics for the candidate pool.
     */
    public function analytics(): JsonResponse
    {
        $analytics = $this->certificationService->getAnalytics();

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }
}
