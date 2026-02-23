<?php

namespace App\Services\Certification;

use App\Models\CertificateType;
use App\Models\PoolCandidate;
use App\Models\SeafarerCertificate;
use App\Models\StcwRequirement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CertificationService — STCW & Certification Engine
 *
 * Core compliance module for maritime crew verification.
 * Handles certificate upload, verification, STCW compliance checks,
 * and risk flag generation.
 *
 * Used by: candidate intake, company matching, decision engine risk scoring,
 *          regulatory audit, MLC readiness.
 */
class CertificationService
{
    /**
     * Upload a certificate for a candidate.
     *
     * Stores certificate, hashes document if provided,
     * sets verification_status = pending.
     */
    public function uploadCertificate(string $candidateId, array $data): SeafarerCertificate
    {
        $candidate = PoolCandidate::findOrFail($candidateId);

        // Validate certificate type exists
        $certType = CertificateType::findByCode($data['certificate_type']);
        if (!$certType) {
            throw new \InvalidArgumentException("Unknown certificate type: {$data['certificate_type']}");
        }

        // Check for duplicate (same type, same candidate, not rejected)
        $existing = SeafarerCertificate::forCandidate($candidateId)
            ->where('certificate_type', $data['certificate_type'])
            ->whereNotIn('verification_status', [SeafarerCertificate::STATUS_REJECTED])
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException(
                "Certificate type '{$data['certificate_type']}' already exists for this candidate. "
                . "Reject the existing one before uploading a new version."
            );
        }

        // Upload frequency limit: max 5 per candidate per hour
        $recentUploads = SeafarerCertificate::forCandidate($candidateId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentUploads >= 5) {
            \App\Services\System\SystemEventService::log('cert_upload_flood', 'warn', 'CertificationService', "Upload frequency exceeded for candidate {$candidateId}", [
                'candidate_id' => $candidateId,
                'recent_uploads' => $recentUploads,
            ]);
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(429, 'Too many certificate uploads. Please try again later.');
        }

        // Validate: expiry_date must be after issue_date
        if (!empty($data['issued_at']) && !empty($data['expires_at'])) {
            $issuedAt = \Carbon\Carbon::parse($data['issued_at']);
            $expiresAt = \Carbon\Carbon::parse($data['expires_at']);
            if ($expiresAt->lte($issuedAt)) {
                throw new \InvalidArgumentException('Expiry date must be after issue date.');
            }
        }

        // Hash document if URL provided
        $documentHash = null;
        if (!empty($data['document_url'])) {
            $documentHash = hash('sha256', $data['document_url'] . now()->timestamp);
        }

        $certificate = SeafarerCertificate::create([
            'pool_candidate_id' => $candidateId,
            'certificate_type' => $data['certificate_type'],
            'certificate_code' => $data['certificate_code'] ?? null,
            'issuing_authority' => $data['issuing_authority'] ?? null,
            'issuing_country' => $data['issuing_country'] ?? null,
            'issued_at' => $data['issued_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'expiry_source' => !empty($data['expires_at'])
                ? SeafarerCertificate::EXPIRY_SOURCE_UPLOADED
                : SeafarerCertificate::EXPIRY_SOURCE_UNKNOWN,
            'self_declared' => $data['self_declared'] ?? false,
            'document_url' => $data['document_url'] ?? null,
            'document_hash' => $documentHash,
            'verification_status' => SeafarerCertificate::STATUS_PENDING,
        ]);

        Log::channel('daily')->info('Certificate uploaded', [
            'candidate_id' => $candidateId,
            'certificate_type' => $data['certificate_type'],
            'certificate_id' => $certificate->id,
        ]);

        // Cross-candidate fraud signals
        if (!empty($data['certificate_code'])) {
            $serialDupes = SeafarerCertificate::where('certificate_code', $data['certificate_code'])
                ->where('pool_candidate_id', '!=', $candidateId)
                ->count();

            if ($serialDupes > 0) {
                \App\Services\System\SystemEventService::alert('cert_duplicate_serial', 'CertificationService', "Certificate code '{$data['certificate_code']}' found on {$serialDupes} other candidate(s)", [
                    'certificate_id' => $certificate->id,
                    'candidate_id' => $candidateId,
                    'certificate_code' => $data['certificate_code'],
                    'duplicate_count' => $serialDupes,
                ]);
            }
        }

        if ($documentHash) {
            $hashDupes = SeafarerCertificate::where('document_hash', $documentHash)
                ->where('id', '!=', $certificate->id)
                ->count();

            if ($hashDupes > 0) {
                \App\Services\System\SystemEventService::alert('cert_duplicate_hash', 'CertificationService', "Document hash matches {$hashDupes} other certificate(s)", [
                    'certificate_id' => $certificate->id,
                    'candidate_id' => $candidateId,
                    'document_hash' => $documentHash,
                    'duplicate_count' => $hashDupes,
                ]);
            }
        }

        return $certificate;
    }

    /**
     * Verify a certificate.
     *
     * Checks expiry, sets verification status.
     */
    public function verifyCertificate(
        string $certificateId,
        ?string $verifiedBy = null,
        ?string $notes = null
    ): SeafarerCertificate {
        $certificate = SeafarerCertificate::findOrFail($certificateId);

        // Auto-reject if expired
        if ($certificate->isExpired()) {
            $certificate->markExpired();
            Log::channel('daily')->info('Certificate auto-expired during verification', [
                'certificate_id' => $certificateId,
                'expires_at' => $certificate->expires_at->toDateString(),
            ]);
            return $certificate->fresh();
        }

        $certificate->verify($verifiedBy, $notes);

        Log::channel('daily')->info('Certificate verified', [
            'certificate_id' => $certificateId,
            'verified_by' => $verifiedBy,
        ]);

        return $certificate->fresh();
    }

    /**
     * Reject a certificate.
     */
    public function rejectCertificate(
        string $certificateId,
        ?string $rejectedBy = null,
        ?string $notes = null
    ): SeafarerCertificate {
        $certificate = SeafarerCertificate::findOrFail($certificateId);
        $certificate->reject($rejectedBy, $notes);

        Log::channel('daily')->info('Certificate rejected', [
            'certificate_id' => $certificateId,
            'rejected_by' => $rejectedBy,
            'notes' => $notes,
        ]);

        return $certificate->fresh();
    }

    /**
     * Get full certification status for a candidate.
     *
     * Returns valid, missing, expired, and risk flags.
     */
    public function getCandidateCertificationStatus(string $candidateId): array
    {
        $candidate = PoolCandidate::findOrFail($candidateId);

        $certificates = SeafarerCertificate::forCandidate($candidateId)
            ->orderBy('certificate_type')
            ->get();

        $valid = [];
        $expired = [];
        $pending = [];
        $rejected = [];
        $expiringSoon = [];

        foreach ($certificates as $cert) {
            $entry = [
                'id' => $cert->id,
                'type' => $cert->certificate_type,
                'code' => $cert->certificate_code,
                'issuing_authority' => $cert->issuing_authority,
                'issued_at' => $cert->issued_at?->toDateString(),
                'expires_at' => $cert->expires_at?->toDateString(),
                'status' => $cert->verification_status,
            ];

            if ($cert->isExpired()) {
                $expired[] = $entry;
            } elseif ($cert->isValid()) {
                $valid[] = $entry;
                if ($cert->isExpiringSoon()) {
                    $expiringSoon[] = $entry;
                }
            } elseif ($cert->verification_status === SeafarerCertificate::STATUS_PENDING) {
                $pending[] = $entry;
            } elseif ($cert->verification_status === SeafarerCertificate::STATUS_REJECTED) {
                $rejected[] = $entry;
            }
        }

        // Determine missing mandatory certificates
        $allMandatoryCodes = CertificateType::active()
            ->mandatory()
            ->pluck('code')
            ->toArray();

        $heldCodes = $certificates
            ->whereNotIn('verification_status', [SeafarerCertificate::STATUS_REJECTED])
            ->pluck('certificate_type')
            ->toArray();

        $missingCodes = array_diff($allMandatoryCodes, $heldCodes);
        $missing = CertificateType::whereIn('code', $missingCodes)
            ->get()
            ->map(fn($ct) => [
                'type' => $ct->code,
                'name' => $ct->name,
                'category' => $ct->category,
            ])
            ->toArray();

        // Generate risk flags
        $riskFlags = $this->generateRiskFlags($certificates, $missingCodes);

        return [
            'candidate_id' => $candidateId,
            'total_certificates' => $certificates->count(),
            'valid_certificates' => $valid,
            'expired_certificates' => $expired,
            'expiring_soon' => $expiringSoon,
            'pending_verification' => $pending,
            'rejected' => $rejected,
            'missing_certificates' => $missing,
            'risk_flags' => $riskFlags,
        ];
    }

    /**
     * Check STCW compliance for a candidate + rank.
     *
     * Returns compliance status, missing certs, expired certs, risk score.
     */
    public function checkSTCWCompliance(string $candidateId, string $rankCode, string $vesselType = 'any'): array
    {
        $requiredCodes = StcwRequirement::getRequiredCodes($rankCode, $vesselType);

        if (empty($requiredCodes)) {
            return [
                'is_compliant' => true,
                'rank_code' => $rankCode,
                'vessel_type' => $vesselType,
                'required' => [],
                'missing' => [],
                'expired' => [],
                'risk_score' => 0,
                'note' => 'No STCW requirements defined for this rank/vessel combination.',
            ];
        }

        $certificates = SeafarerCertificate::forCandidate($candidateId)
            ->whereIn('certificate_type', $requiredCodes)
            ->get()
            ->keyBy('certificate_type');

        $missing = [];
        $expired = [];
        $valid = [];

        foreach ($requiredCodes as $code) {
            $cert = $certificates->get($code);
            $certType = CertificateType::findByCode($code);
            $certName = $certType?->name ?? $code;

            if (!$cert) {
                $missing[] = ['code' => $code, 'name' => $certName];
            } elseif ($cert->isExpired()) {
                $expired[] = [
                    'code' => $code,
                    'name' => $certName,
                    'expired_at' => $cert->expires_at?->toDateString(),
                ];
            } elseif ($cert->verification_status === SeafarerCertificate::STATUS_REJECTED) {
                $missing[] = ['code' => $code, 'name' => $certName, 'note' => 'rejected'];
            } elseif ($cert->isValid()) {
                $valid[] = $code;
            } else {
                // Pending verification — treat as not yet valid
                $missing[] = ['code' => $code, 'name' => $certName, 'note' => 'pending_verification'];
            }
        }

        $isCompliant = empty($missing) && empty($expired);

        // Risk score: 0 = fully compliant, higher = more risk
        $riskScore = 0;
        $riskScore += count($missing) * 25; // 25 per missing mandatory cert
        $riskScore += count($expired) * 15; // 15 per expired cert
        $riskScore = min($riskScore, 100);

        return [
            'is_compliant' => $isCompliant,
            'rank_code' => $rankCode,
            'vessel_type' => $vesselType,
            'required' => $requiredCodes,
            'valid' => $valid,
            'missing' => $missing,
            'expired' => $expired,
            'risk_score' => $riskScore,
        ];
    }

    /**
     * Get certification-ready candidates for a talent request.
     *
     * Returns only candidates who are STCW compliant
     * with no expired mandatory certs.
     */
    public function getCertificationReadyCandidates(
        string $rankCode,
        string $vesselType = 'any',
        int $limit = 50
    ): array {
        $requiredCodes = StcwRequirement::getRequiredCodes($rankCode, $vesselType);

        if (empty($requiredCodes)) {
            // No STCW requirements — all pool candidates qualify
            return PoolCandidate::inPool()
                ->seafarers()
                ->limit($limit)
                ->pluck('id')
                ->toArray();
        }

        // Find candidates who have all required certs valid
        $candidateIds = PoolCandidate::inPool()
            ->seafarers()
            ->pluck('id');

        $ready = [];
        foreach ($candidateIds as $candidateId) {
            $compliance = $this->checkSTCWCompliance($candidateId, $rankCode, $vesselType);
            if ($compliance['is_compliant']) {
                $ready[] = $candidateId;
            }
            if (count($ready) >= $limit) {
                break;
            }
        }

        return $ready;
    }

    /**
     * Generate certification summary for decision packet.
     *
     * Appended to candidate presentation data.
     */
    public function getCertificationSummary(string $candidateId): array
    {
        $status = $this->getCandidateCertificationStatus($candidateId);

        // Determine rank from source_meta
        $candidate = PoolCandidate::find($candidateId);
        $rank = $candidate?->source_meta['rank'] ?? null;

        $compliance = null;
        if ($rank) {
            $compliance = $this->checkSTCWCompliance($candidateId, $rank);
        }

        return [
            'certification_summary' => [
                'total' => $status['total_certificates'],
                'valid' => count($status['valid_certificates']),
                'expired' => count($status['expired_certificates']),
                'expiring_soon' => count($status['expiring_soon']),
                'missing_mandatory' => count($status['missing_certificates']),
                'pending_verification' => count($status['pending_verification']),
            ],
            'compliance_status' => $compliance ? [
                'stcw_compliant' => $compliance['is_compliant'],
                'risk_score' => $compliance['risk_score'],
                'missing_count' => count($compliance['missing']),
                'expired_count' => count($compliance['expired']),
            ] : null,
            'missing_items' => $status['missing_certificates'],
            'risk_flags' => $status['risk_flags'],
        ];
    }

    /**
     * Process nightly expiry check.
     *
     * Called by scheduled command.
     * Detects certificates expiring within 90 days.
     */
    public function processExpiryCheck(int $warningDays = 90): array
    {
        $results = [
            'newly_expired' => 0,
            'expiring_soon' => 0,
            'risk_flags_created' => 0,
        ];

        // 1. Mark actually expired certificates
        $expiredCerts = SeafarerCertificate::where('verification_status', SeafarerCertificate::STATUS_VERIFIED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expiredCerts as $cert) {
            $cert->markExpired();
            $results['newly_expired']++;

            Log::channel('daily')->warning('Certificate expired', [
                'certificate_id' => $cert->id,
                'candidate_id' => $cert->pool_candidate_id,
                'type' => $cert->certificate_type,
                'expired_at' => $cert->expires_at->toDateString(),
            ]);
        }

        // 2. Detect expiring soon + notify candidates
        $expiringSoon = SeafarerCertificate::where('verification_status', SeafarerCertificate::STATUS_VERIFIED)
            ->expiringSoon($warningDays)
            ->with('candidate')
            ->get();

        $results['expiring_soon'] = $expiringSoon->count();
        $results['notifications_sent'] = 0;

        $notificationService = app(\App\Services\CandidateNotificationService::class);

        foreach ($expiringSoon as $cert) {
            $daysLeft = (int) now()->diffInDays($cert->expires_at);
            Log::channel('daily')->info('Certificate expiring soon', [
                'certificate_id' => $cert->id,
                'candidate_id' => $cert->pool_candidate_id,
                'type' => $cert->certificate_type,
                'expires_at' => $cert->expires_at->toDateString(),
                'days_left' => $daysLeft,
            ]);

            // Send notification (dedup handled inside service)
            if ($cert->candidate) {
                $notif = $notificationService->notifyCertificateExpiring($cert->candidate, $cert, $daysLeft);
                if ($notif) {
                    $results['notifications_sent']++;
                }
            }
        }

        return $results;
    }

    /**
     * Get aggregate certification analytics for the pool.
     */
    public function getAnalytics(): array
    {
        $totalSeafarers = PoolCandidate::seafarers()->count();

        if ($totalSeafarers === 0) {
            return [
                'total_seafarers' => 0,
                'stcw_compliant_pct' => 0,
                'expired_cert_ratio' => 0,
                'missing_mandatory_ratio' => 0,
                'verification_pending' => 0,
            ];
        }

        // Count candidates with at least one expired cert
        $withExpired = DB::table('seafarer_certificates')
            ->join('pool_candidates', 'pool_candidates.id', '=', 'seafarer_certificates.pool_candidate_id')
            ->where('pool_candidates.seafarer', true)
            ->whereNotNull('seafarer_certificates.expires_at')
            ->where('seafarer_certificates.expires_at', '<', now())
            ->distinct('seafarer_certificates.pool_candidate_id')
            ->count('seafarer_certificates.pool_candidate_id');

        // Count pending verifications
        $pendingVerification = SeafarerCertificate::where('verification_status', SeafarerCertificate::STATUS_PENDING)
            ->count();

        // STCW compliance — check each in-pool seafarer
        $inPoolSeafarers = PoolCandidate::inPool()->seafarers()->get();
        $compliantCount = 0;
        $missingMandatoryCount = 0;

        foreach ($inPoolSeafarers as $candidate) {
            $rank = $candidate->source_meta['rank'] ?? null;
            if (!$rank) {
                continue;
            }
            $compliance = $this->checkSTCWCompliance($candidate->id, $rank);
            if ($compliance['is_compliant']) {
                $compliantCount++;
            }
            if (!empty($compliance['missing'])) {
                $missingMandatoryCount++;
            }
        }

        $poolCount = $inPoolSeafarers->count();

        return [
            'total_seafarers' => $totalSeafarers,
            'in_pool' => $poolCount,
            'stcw_compliant_pct' => $poolCount > 0
                ? round(($compliantCount / $poolCount) * 100, 1)
                : 0,
            'expired_cert_ratio' => round(($withExpired / $totalSeafarers) * 100, 1),
            'missing_mandatory_ratio' => $poolCount > 0
                ? round(($missingMandatoryCount / $poolCount) * 100, 1)
                : 0,
            'verification_pending' => $pendingVerification,
        ];
    }

    /**
     * Generate risk flags from certificate data.
     *
     * These feed into DecisionEngine later.
     */
    private function generateRiskFlags($certificates, array $missingCodes): array
    {
        $flags = [];

        // RF_CERT_EXPIRED
        foreach ($certificates as $cert) {
            if ($cert->isExpired()) {
                $flags[] = [
                    'code' => SeafarerCertificate::RF_CERT_EXPIRED,
                    'severity' => 'high',
                    'certificate_type' => $cert->certificate_type,
                    'expired_at' => $cert->expires_at?->toDateString(),
                    'description' => "Certificate '{$cert->certificate_type}' expired on {$cert->expires_at?->toDateString()}.",
                ];
            }
        }

        // RF_CERT_MISSING
        foreach ($missingCodes as $code) {
            $flags[] = [
                'code' => SeafarerCertificate::RF_CERT_MISSING,
                'severity' => 'medium',
                'certificate_type' => $code,
                'description' => "Mandatory certificate '{$code}' is missing.",
            ];
        }

        // RF_MEDICAL_EXPIRED
        $medical = $certificates->where('certificate_type', 'MEDICAL_FITNESS');
        foreach ($medical as $cert) {
            if ($cert->isExpired()) {
                $flags[] = [
                    'code' => SeafarerCertificate::RF_MEDICAL_EXPIRED,
                    'severity' => 'critical',
                    'certificate_type' => $cert->certificate_type,
                    'expired_at' => $cert->expires_at?->toDateString(),
                    'description' => 'Medical fitness certificate has expired. Candidate cannot embark.',
                ];
            }
        }

        // RF_CERT_FAKE_PATTERN
        // Check for suspicious patterns: same document hash across different candidates
        $hashCandidates = [];
        foreach ($certificates as $cert) {
            if ($cert->document_hash) {
                $dupes = SeafarerCertificate::where('document_hash', $cert->document_hash)
                    ->where('id', '!=', $cert->id)
                    ->count();
                if ($dupes > 0) {
                    $flags[] = [
                        'code' => SeafarerCertificate::RF_CERT_FAKE_PATTERN,
                        'severity' => 'critical',
                        'certificate_type' => $cert->certificate_type,
                        'description' => "Document hash matches {$dupes} other certificate(s). Possible forgery.",
                    ];
                }
            }
        }

        return $flags;
    }
}
