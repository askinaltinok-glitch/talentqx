<?php

namespace App\Http\Controllers\Api\Maritime;

use App\Http\Controllers\Api\Maritime\Concerns\VerifiesCandidateToken;
use App\Http\Controllers\Controller;
use App\Models\PoolCandidate;
use App\Services\Maritime\CertificateLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * CandidateLifecycleController
 *
 * Candidate-facing lifecycle dashboard endpoints.
 * All endpoints use token-based auth via ?t= query parameter.
 * NEVER expose raw scoring, decision thresholds, or admin data.
 */
class CandidateLifecycleController extends Controller
{
    use VerifiesCandidateToken;

    /**
     * GET /v1/maritime/candidates/{id}/lifecycle
     *
     * Enriched lifecycle status endpoint for the candidate dashboard.
     */
    public function status(Request $request, string $id): JsonResponse
    {
        [$candidate, $error] = $this->resolveAndVerifyCandidate($id, $request);
        if ($error) return $error;

        // Update last_seen_at
        $candidate->updateQuietly(['last_seen_at' => now()]);

        // Cache certificate summary for 60s
        $certSummary = Cache::remember(
            "candidate_cert_summary:{$id}",
            60,
            fn() => $this->buildCertificateSummary($candidate)
        );

        // Lifecycle dates
        $latestCompletedInterview = $candidate->formInterviews()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        // Membership / subscription
        $membership = $candidate->membership;
        $membershipTier = $membership?->getEffectiveTier() ?? 'free';

        // Availability
        $availability = [
            'status' => $candidate->availability_status ?? 'unknown',
            'contract_end_estimate' => $candidate->contract_end_estimate?->toDateString(),
            'updated_at' => $candidate->availability_updated_at?->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'candidate' => [
                    'id' => $candidate->id,
                    'full_name' => $candidate->first_name . ' ' . $candidate->last_name,
                    'preferred_language' => $candidate->preferred_language,
                    'email_verified_at' => $candidate->email_verified_at?->toIso8601String(),
                    'created_at' => $candidate->created_at->toIso8601String(),
                ],
                'lifecycle' => [
                    'applied_at' => $candidate->created_at->toIso8601String(),
                    'interview_completed_at' => $latestCompletedInterview?->completed_at?->toIso8601String(),
                    'logbook_activated_at' => $candidate->logbook_activated_at?->toIso8601String(),
                    'free_until' => $membership?->expires_at?->toIso8601String(),
                    'subscription' => [
                        'status' => $membershipTier,
                        'next_renewal_at' => $membership?->expires_at?->toIso8601String(),
                        'quota_resets_at' => null, // future: quota system
                    ],
                ],
                'compliance' => [
                    'certificate_summary' => $certSummary,
                ],
                'activity' => [
                    'last_seen_at' => now()->toIso8601String(),
                    'weekly_job_pulse_enabled' => false, // future: job pulse feature
                ],
                'availability' => $availability,
            ],
        ]);
    }

    /**
     * POST /v1/maritime/candidates/{id}/availability
     *
     * Update candidate availability status.
     */
    public function updateAvailability(Request $request, string $id): JsonResponse
    {
        [$candidate, $error] = $this->resolveAndVerifyCandidate($id, $request);
        if ($error) return $error;

        $validated = $request->validate([
            'status' => 'required|string|in:available,on_contract,soon_available,unknown',
            'contract_end_estimate' => 'nullable|date|after:today',
        ]);

        $candidate->update([
            'availability_status' => $validated['status'],
            'availability_updated_at' => now(),
            'contract_end_estimate' => $validated['contract_end_estimate'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $candidate->availability_status,
                'contract_end_estimate' => $candidate->contract_end_estimate?->toDateString(),
                'updated_at' => $candidate->availability_updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /v1/maritime/candidates/{id}/logbook-activate
     *
     * Mark logbook as activated (first interaction).
     */
    public function logbookActivate(Request $request, string $id): JsonResponse
    {
        [$candidate, $error] = $this->resolveAndVerifyCandidate($id, $request);
        if ($error) return $error;

        if (!$candidate->logbook_activated_at) {
            $candidate->update(['logbook_activated_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'logbook_activated_at' => $candidate->logbook_activated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Build certificate compliance summary (candidate-safe).
     */
    private function buildCertificateSummary(PoolCandidate $candidate): array
    {
        $certificates = $candidate->certificates;

        if ($certificates->isEmpty()) {
            return [
                'total' => 0,
                'ok' => 0,
                'yellow' => 0,
                'red' => 0,
                'next_expiring' => [],
            ];
        }

        $certService = app(CertificateLifecycleService::class);
        $enriched = $certService->enrichWithRiskLevels($certificates);

        $ok = 0;
        $yellow = 0;
        $red = 0;
        $expiringList = [];

        foreach ($enriched as $cert) {
            $risk = $cert['risk_level'] ?? 'unknown';

            if ($risk === 'valid') {
                $ok++;
            } elseif (in_array($risk, ['expiring_soon', 'critical'])) {
                $yellow++;
                $expiringList[] = [
                    'type' => $cert['certificate_type'],
                    'expires_at' => $cert['expires_at'],
                    'days_remaining' => $cert['days_remaining'],
                    'risk_level' => $risk,
                    'expiry_source' => $cert['expiry_source'] ?? 'unknown',
                ];
            } elseif ($risk === 'expired') {
                $red++;
                $expiringList[] = [
                    'type' => $cert['certificate_type'],
                    'expires_at' => $cert['expires_at'],
                    'days_remaining' => $cert['days_remaining'],
                    'risk_level' => $risk,
                    'expiry_source' => $cert['expiry_source'] ?? 'unknown',
                ];
            } else {
                $ok++; // unknown treated as OK for candidate view
            }
        }

        // Sort by days_remaining ascending, take top 3
        usort($expiringList, fn($a, $b) => ($a['days_remaining'] ?? PHP_INT_MAX) <=> ($b['days_remaining'] ?? PHP_INT_MAX));
        $expiringList = array_slice($expiringList, 0, 3);

        return [
            'total' => count($enriched),
            'ok' => $ok,
            'yellow' => $yellow,
            'red' => $red,
            'next_expiring' => $expiringList,
        ];
    }
}
