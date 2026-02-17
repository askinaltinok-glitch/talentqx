<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Certification\CertificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CertificateController — Candidate-facing certificate endpoints.
 *
 * Allows candidates to upload their certificates
 * and check their certification status.
 */
class CertificateController extends Controller
{
    public function __construct(
        private CertificationService $certificationService
    ) {}

    /**
     * Allowed domains for certificate document URLs.
     */
    private const ALLOWED_URL_DOMAINS = [
        'talentqx.com',
        'app.talentqx.com',
        's3.amazonaws.com',
        's3.eu-central-1.amazonaws.com',
        'storage.googleapis.com',
        'blob.core.windows.net',
    ];

    /**
     * POST /v1/certificates/upload
     *
     * Upload a certificate for a candidate.
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'candidate_id' => 'required|uuid|exists:pool_candidates,id',
            'certificate_type' => 'required|string|max:32',
            'certificate_code' => 'nullable|string|max:64',
            'issuing_authority' => 'nullable|string|max:128',
            'issuing_country' => 'nullable|string|size:2',
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:issued_at',
            'document_url' => ['nullable', 'url:https', 'max:512'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Domain whitelist for document URLs
        $url = $request->input('document_url');
        if ($url) {
            $host = parse_url($url, PHP_URL_HOST);
            $allowed = false;
            foreach (self::ALLOWED_URL_DOMAINS as $domain) {
                if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => ['document_url' => ['Document URL must be hosted on an approved storage domain.']],
                ], 422);
            }
        }

        try {
            $certificate = $this->certificationService->uploadCertificate(
                $request->input('candidate_id'),
                $validator->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Certificate uploaded successfully.',
                'data' => $certificate,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /v1/certificates/{candidateId}
     *
     * Get certification status for a candidate.
     */
    public function status(string $candidateId): JsonResponse
    {
        try {
            $status = $this->certificationService->getCandidateCertificationStatus($candidateId);

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
}
