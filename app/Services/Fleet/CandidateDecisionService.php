<?php

namespace App\Services\Fleet;

use App\Models\CandidateTrustProfile;
use App\Models\FleetVessel;
use App\Models\PoolCandidate;
use App\Services\Fleet\VesselRequirementProfileService;
use App\Services\Maritime\RoleFitEngine;
use App\Services\Maritime\RoleWeightMap;
use App\Services\Maritime\SynergyEngineResolver;
use Illuminate\Support\Facades\Log;

/**
 * CandidateDecisionService — Unified Scoring Entry Point
 *
 * Merges 4 scoring pillars into a single final_score for crew shortlisting:
 *
 *   final_score = 0.40 × availability_fit
 *               + 0.25 × competency_fit
 *               + 0.20 × synergy_soft_fit
 *               + 0.15 × compliance_readiness
 *
 * This is the ONLY place where final ranking is computed.
 * All consumers (crew planning, decision room, shortlist) call this.
 */
class CandidateDecisionService
{
    // Pillar weights (must sum to 1.0)
    // Legacy: availability(0.40) + competency(0.25) + synergy(0.20) + compliance(0.15)
    // Clean workflow v1: competency(0.40) + behavioral(0.25) + synergy(0.20) + reliability(0.15)
    private const W_AVAILABILITY = 0.40;
    private const W_COMPETENCY   = 0.25;
    private const W_SYNERGY      = 0.20;
    private const W_COMPLIANCE   = 0.15;

    // Availability scoring (0..1 scale)
    private const AVAIL_SCORE = [
        'available'      => 1.0,
        'soon_available' => 0.7,  // adjusted by days
        'on_contract'    => 0.2,
        'unknown'        => 0.3,
    ];

    private const URGENCY_BOOST_DAYS = 15;

    private RoleWeightMap $roleWeights;
    private VesselRequirementProfileService $profileService;
    private RoleFitEngine $roleFitEngine;

    public function __construct()
    {
        $this->roleWeights = new RoleWeightMap();
        $this->profileService = new VesselRequirementProfileService();
        $this->roleFitEngine = new RoleFitEngine();
    }

    /**
     * Compute unified final score for a candidate on a vessel for a given rank.
     *
     * @param PoolCandidate $candidate
     * @param FleetVessel   $vessel
     * @param string        $rankCode
     * @param string|null   $tenantId  For synergy V2 feature flag resolution
     * @return array{final_score: float, pillars: array, meta: array}
     */
    public function computeFinalScore(
        PoolCandidate $candidate,
        FleetVessel $vessel,
        string $rankCode,
        ?string $tenantId = null,
    ): array {
        // Try vessel-profile-aware scoring first
        $profile = $this->profileService->resolve($vessel);

        if ($profile) {
            return $this->computeProfileScore($candidate, $vessel, $rankCode, $tenantId, $profile);
        }

        return $this->computeLegacyScore($candidate, $vessel, $rankCode, $tenantId);
    }

    /**
     * Profile-aware scoring — uses vessel requirement template weights and cert/experience fit.
     */
    private function computeProfileScore(
        PoolCandidate $candidate,
        FleetVessel $vessel,
        string $rankCode,
        ?string $tenantId,
        array $profile,
    ): array {
        $now = now()->startOfDay();
        $weights = $this->profileService->resolveWeights($profile);

        $wCert   = $weights['cert_fit'] ?? 0.25;
        $wExp    = $weights['experience_fit'] ?? 0.25;
        $wBehav  = $weights['behavior_fit'] ?? 0.25;
        $wAvail  = $weights['availability_fit'] ?? 0.25;

        $certFit  = $this->computeCertFit($candidate, $profile);
        $expFit   = $this->computeExperienceFit($candidate, $vessel, $profile);
        $behavFit = $this->computeBehaviorFit($candidate, $vessel, $rankCode, $tenantId, $profile);
        $availFit = $this->computeAvailabilityFit($candidate, $now);

        $finalScore = $wCert  * $certFit['score']
                    + $wExp   * $expFit['score']
                    + $wBehav * $behavFit['score']
                    + $wAvail * $availFit['score'];

        $finalScore = max(0.0, min(1.0, $finalScore));

        // Hard-block enforcement: missing mandatory endorsement certs → BLOCKED
        $hardBlocked = $certFit['hard_blocked'] ?? [];
        $isBlocked = !empty($hardBlocked);
        if ($isBlocked) {
            $finalScore = min($finalScore, 0.20);
        }

        $typeKey = $this->profileService->resolveTypeKey($vessel->vessel_type ?? '');

        // Role-fit evaluation
        $roleFit = $this->computeRoleFit($candidate, $rankCode);
        $isRoleMismatch = $roleFit['mismatch_level'] === 'strong';

        // Label priority: 1) blocked  2) role_mismatch  3) score-based
        $label = $isBlocked
            ? 'blocked'
            : ($isRoleMismatch ? 'role_mismatch' : $this->scoreLabel($finalScore));

        return [
            'final_score' => round($finalScore, 4),
            'final_score_pct' => (int) round($finalScore * 100),
            'label' => $label,
            'is_blocked' => $isBlocked,
            'is_role_mismatch' => $isRoleMismatch,
            'blockers' => $hardBlocked,
            'pillars' => [
                'cert_fit' => [
                    'score' => round($certFit['score'], 3),
                    'weight' => $wCert,
                    'matched' => $certFit['matched'],
                    'missing' => $certFit['missing'],
                    'expired' => $certFit['expired'],
                    'total_required' => $certFit['total_required'],
                    'hard_blocked' => $certFit['hard_blocked'],
                ],
                'experience_fit' => [
                    'score' => round($expFit['score'], 3),
                    'weight' => $wExp,
                    'vessel_type_months' => $expFit['vessel_type_months'],
                    'total_sea_months' => $expFit['total_sea_months'],
                    'source' => $expFit['source'],
                ],
                'behavior_fit' => array_filter([
                    'score' => round($behavFit['score'], 3),
                    'weight' => $wBehav,
                    'engine' => $behavFit['engine'],
                    'below_threshold_dims' => $behavFit['below_threshold_dims'],
                    'synergy_meta' => $behavFit['synergy_meta'] ?? null,
                ], fn ($v) => $v !== null),
                'availability' => [
                    'score' => round($availFit['score'], 3),
                    'weight' => $wAvail,
                    'status' => $availFit['status'],
                    'days_to_available' => $availFit['days_to_available'],
                ],
            ],
            'role_fit' => [
                'score' => $roleFit['role_fit_score'],
                'mismatch_level' => $roleFit['mismatch_level'],
                'flags' => $roleFit['mismatch_flags'],
                'inferred_role_key' => $roleFit['inferred_role_key'],
                'suggestions' => $roleFit['suggestions'],
            ],
            'meta' => [
                'scoring_mode' => 'vessel_profile',
                'vessel_type_key' => $typeKey,
                'weights' => $weights,
                'candidate_id' => $candidate->id,
                'vessel_id' => $vessel->id,
                'rank_code' => $rankCode,
                'formula' => "cert_fit({$wCert}) + experience_fit({$wExp}) + behavior_fit({$wBehav}) + availability({$wAvail})",
                'computed_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Legacy scoring — original 4-pillar hardcoded weights (backward compatible).
     */
    private function computeLegacyScore(
        PoolCandidate $candidate,
        FleetVessel $vessel,
        string $rankCode,
        ?string $tenantId,
    ): array {
        $now = now()->startOfDay();

        $availFit      = $this->computeAvailabilityFit($candidate, $now);
        $compFit       = $this->computeCompetencyFit($candidate);
        $synergyFit    = $this->computeSynergyFit($candidate, $vessel, $rankCode, $tenantId);
        $complianceFit = $this->computeComplianceFit($candidate);

        $finalScore = self::W_AVAILABILITY * $availFit['score']
                    + self::W_COMPETENCY   * $compFit['score']
                    + self::W_SYNERGY      * $synergyFit['score']
                    + self::W_COMPLIANCE   * $complianceFit['score'];

        $finalScore = max(0.0, min(1.0, $finalScore));

        // Role-fit evaluation
        $roleFit = $this->computeRoleFit($candidate, $rankCode);
        $isRoleMismatch = $roleFit['mismatch_level'] === 'strong';

        // Label priority: 1) blocked (none in legacy)  2) role_mismatch  3) score-based
        $label = $isRoleMismatch ? 'role_mismatch' : $this->scoreLabel($finalScore);

        return [
            'final_score' => round($finalScore, 4),
            'final_score_pct' => (int) round($finalScore * 100),
            'label' => $label,
            'is_blocked' => false,
            'is_role_mismatch' => $isRoleMismatch,
            'blockers' => [],
            'pillars' => [
                'availability' => [
                    'score' => round($availFit['score'], 3),
                    'weight' => self::W_AVAILABILITY,
                    'status' => $availFit['status'],
                    'days_to_available' => $availFit['days_to_available'],
                ],
                'competency' => [
                    'score' => round($compFit['score'], 3),
                    'weight' => self::W_COMPETENCY,
                    'source' => $compFit['source'],
                ],
                'synergy' => array_filter([
                    'score' => round($synergyFit['score'], 3),
                    'weight' => self::W_SYNERGY,
                    'engine' => $synergyFit['engine'],
                    'synergy_meta' => $synergyFit['synergy_meta'] ?? null,
                ], fn ($v) => $v !== null),
                'compliance' => [
                    'score' => round($complianceFit['score'], 3),
                    'weight' => self::W_COMPLIANCE,
                    'cert_status' => $complianceFit['cert_status'],
                ],
            ],
            'role_fit' => [
                'score' => $roleFit['role_fit_score'],
                'mismatch_level' => $roleFit['mismatch_level'],
                'flags' => $roleFit['mismatch_flags'],
                'inferred_role_key' => $roleFit['inferred_role_key'],
                'suggestions' => $roleFit['suggestions'],
            ],
            'meta' => [
                'scoring_mode' => 'legacy',
                'candidate_id' => $candidate->id,
                'vessel_id' => $vessel->id,
                'rank_code' => $rankCode,
                'formula' => config('maritime.clean_workflow_v1')
                    ? 'competency(0.40) + behavioral(0.25) + synergy(0.20) + reliability(0.15)'
                    : 'availability(0.40) + competency(0.25) + synergy(0.20) + compliance(0.15)',
                'synergy_dry_run' => ($synergyFit['engine'] === 'v2_dry_run'),
                'computed_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Batch score multiple candidates for a vessel+rank. Sorted by final_score desc.
     */
    public function rankCandidates(
        array $candidateIds,
        FleetVessel $vessel,
        string $rankCode,
        ?string $tenantId = null,
        int $limit = 10,
    ): array {
        $candidates = PoolCandidate::whereIn('id', $candidateIds)->get();
        $results = [];

        foreach ($candidates as $candidate) {
            try {
                $scored = $this->computeFinalScore($candidate, $vessel, $rankCode, $tenantId);
                $scored['candidate'] = [
                    'id' => $candidate->id,
                    'name' => trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? '')),
                    'nationality' => $candidate->nationality,
                ];
                $results[] = $scored;
            } catch (\Throwable $e) {
                Log::warning('CandidateDecisionService: scoring failed', [
                    'candidate_id' => $candidate->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        usort($results, fn ($a, $b) => $b['final_score'] <=> $a['final_score']);

        return array_slice($results, 0, $limit);
    }

    // ─── Pillar Implementations ──────────────────────────────────────

    private function computeAvailabilityFit(PoolCandidate $candidate, $now): array
    {
        $status = $candidate->availability_status ?? 'unknown';
        $contractEnd = $candidate->contract_end_estimate;
        $daysToAvailable = null;

        $score = self::AVAIL_SCORE[$status] ?? 0.3;

        if ($status === 'available') {
            $daysToAvailable = 0;
        } elseif ($status === 'soon_available' && $contractEnd) {
            $daysToAvailable = max(0, (int) $now->diffInDays($contractEnd, false));
            // Closer = higher score (linear interpolation from 0.5 to 0.9)
            $score = $daysToAvailable <= self::URGENCY_BOOST_DAYS
                ? 0.9
                : max(0.5, 0.9 - ($daysToAvailable - self::URGENCY_BOOST_DAYS) / 200);
        } elseif ($status === 'on_contract' && $contractEnd) {
            $daysToAvailable = max(0, (int) $now->diffInDays($contractEnd, false));
            // On contract but with known end: partial credit
            $score = $daysToAvailable <= 30 ? 0.5 : 0.2;
        }

        return [
            'score' => $score,
            'status' => $status,
            'days_to_available' => $daysToAvailable,
        ];
    }

    private function computeCompetencyFit(PoolCandidate $candidate): array
    {
        // Try trust profile first (has aggregated scores)
        $trust = CandidateTrustProfile::where('pool_candidate_id', $candidate->id)->first();

        if ($trust && $trust->competency_score !== null) {
            return [
                'score' => min(1.0, max(0.0, $trust->competency_score / 100)),
                'source' => 'trust_profile',
            ];
        }

        // Fallback to interview score
        $interview = $candidate->latestCompletedInterview();
        if ($interview) {
            $interviewScore = $interview->calibrated_score ?? $interview->final_score ?? 0;
            return [
                'score' => min(1.0, max(0.0, $interviewScore / 100)),
                'source' => 'interview',
            ];
        }

        // No data
        return [
            'score' => 0.5, // neutral when no data
            'source' => 'insufficient_data',
        ];
    }

    private function computeSynergyFit(
        PoolCandidate $candidate,
        FleetVessel $vessel,
        string $rankCode,
        ?string $tenantId,
    ): array {
        if (!$tenantId) {
            return ['score' => 0.5, 'engine' => 'none', 'synergy_meta' => null];
        }

        try {
            $resolver = app(SynergyEngineResolver::class);
            $resolution = $resolver->resolve($tenantId, $rankCode);

            if ($resolution['engine'] === 'v2') {
                // Extract behavioral traits from trust profile
                $trust = CandidateTrustProfile::where('pool_candidate_id', $candidate->id)->first();
                $traitScores = $this->extractTraitScores01($trust);

                $baseFit01 = ($this->computeCompetencyFit($candidate))['score'];

                $result = $resolver->run($tenantId, $rankCode, [
                    'candidate_id' => $candidate->id,
                    'vessel_id' => $vessel->id,
                    'role_key' => $rankCode,
                    'trait_scores_01' => $traitScores,
                    'hard_fit_01' => $baseFit01,
                ]);

                $isDryRun = (bool) ($result['meta']['dry_run'] ?? false);
                $v2Score = $result['synergy_01'] ?? 0.5;

                // Build synergy metadata for decision packet
                $synergyMeta = [
                    'engine' => 'v2',
                    'dry_run' => $isDryRun,
                    'synergy_01' => round($v2Score, 4),
                    'role_key_used' => $result['role']['role_key_used'] ?? $rankCode,
                    'weights_version' => $result['role']['weights_version'] ?? null,
                    'weights_used' => $result['role']['weights_used'] ?? null,
                    'trait_scores_01' => $result['role']['trait_scores_01'] ?? [],
                    'components' => $result['components'] ?? [],
                ];

                if ($isDryRun) {
                    // Dry-run: log V2 score but use neutral 0.5 for final_score
                    Log::info('CandidateDecisionService: synergy V2 dry-run', [
                        'candidate_id' => $candidate->id,
                        'vessel_id' => $vessel->id,
                        'rank_code' => $rankCode,
                        'v2_synergy_01' => $v2Score,
                        'decision_score_used' => 0.5,
                    ]);

                    return [
                        'score' => 0.5, // neutral — V2 does NOT affect final_score
                        'engine' => 'v2_dry_run',
                        'synergy_meta' => $synergyMeta,
                    ];
                }

                return [
                    'score' => $v2Score,
                    'engine' => 'v2',
                    'synergy_meta' => $synergyMeta,
                ];
            }

            // V1 — simple synergy
            $v1 = app(CrewSynergyService::class);
            // V1 needs vessel_id that references the old Vessel model, not FleetVessel
            // Use IMO bridge if possible
            $result = null;
            if ($vessel->imo) {
                $oldVessel = \App\Models\Vessel::where('imo', $vessel->imo)->first();
                if ($oldVessel) {
                    $result = $v1->compute($candidate->id, $oldVessel->id);
                }
            }

            if ($result) {
                return [
                    'score' => min(1.0, max(0.0, ($result['synergy']['score'] ?? 50) / 100)),
                    'engine' => 'v1',
                    'synergy_meta' => null,
                ];
            }

            return ['score' => 0.5, 'engine' => 'v1_fallback', 'synergy_meta' => null];
        } catch (\Throwable $e) {
            Log::warning('CandidateDecisionService: synergy computation failed', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
            ]);
            return ['score' => 0.5, 'engine' => 'error', 'synergy_meta' => null];
        }
    }

    private function computeComplianceFit(PoolCandidate $candidate): array
    {
        $certs = $candidate->certificates()->get();

        if ($certs->isEmpty()) {
            return ['score' => 0.4, 'cert_status' => 'no_data'];
        }

        $now = now();
        $total = $certs->count();
        $valid = 0;
        $expiringSoon = 0;
        $expired = 0;

        foreach ($certs as $cert) {
            if (!$cert->expires_at) {
                $valid++; // no expiry = assumed valid
                continue;
            }
            if ($cert->expires_at->lt($now)) {
                $expired++;
            } elseif ($cert->expires_at->lt($now->copy()->addDays(90))) {
                $expiringSoon++;
            } else {
                $valid++;
            }
        }

        if ($expired > 0) {
            $status = 'critical';
            $score = max(0.1, 0.5 - ($expired * 0.15));
        } elseif ($expiringSoon > 0) {
            $status = 'warn';
            $score = max(0.5, 0.8 - ($expiringSoon * 0.1));
        } else {
            $status = 'ok';
            $score = 1.0;
        }

        return [
            'score' => $score,
            'cert_status' => $status,
        ];
    }

    // ─── Profile-aware Pillar Implementations ──────────────────────

    /**
     * Certificate fit: score based on how many required certs the candidate has.
     */
    private function computeCertFit(PoolCandidate $candidate, array $profile): array
    {
        $requiredCerts = $profile['required_certificates'] ?? [];

        if (empty($requiredCerts)) {
            return ['score' => 0.7, 'matched' => 0, 'missing' => 0, 'expired' => 0, 'total_required' => 0, 'hard_blocked' => []];
        }

        $candidateCerts = $candidate->certificates()->get()->keyBy(function ($cert) {
            return strtoupper($cert->certificate_type ?? '');
        });

        $now = now();
        $matched = 0;
        $missing = 0;
        $expired = 0;
        $expiringSoon = 0;
        $mandatoryMissing = 0;
        $hardBlocked = [];
        $total = count($requiredCerts);

        foreach ($requiredCerts as $req) {
            $certType = strtoupper($req['certificate_type'] ?? '');
            $minMonths = $req['min_remaining_months'] ?? 3;
            $mandatory = $req['mandatory'] ?? false;
            $isHardBlock = (bool) ($req['hard_block'] ?? false);
            $blockReasonKey = $req['block_reason_key'] ?? null;

            $cert = $candidateCerts->get($certType);

            if (!$cert) {
                $missing++;
                if ($mandatory) {
                    $mandatoryMissing++;
                }
                if ($isHardBlock) {
                    $hardBlocked[] = [
                        'certificate_type' => $certType,
                        'reason' => 'missing',
                        'block_reason_key' => $blockReasonKey,
                    ];
                }
                continue;
            }

            if ($cert->expires_at && $cert->expires_at->lt($now)) {
                $expired++;
                if ($mandatory) {
                    $mandatoryMissing++;
                }
                if ($isHardBlock) {
                    $hardBlocked[] = [
                        'certificate_type' => $certType,
                        'reason' => 'expired',
                        'block_reason_key' => $blockReasonKey,
                    ];
                }
                continue;
            }

            if ($cert->expires_at && $cert->expires_at->lt($now->copy()->addMonths($minMonths))) {
                $expiringSoon++;
                if ($isHardBlock) {
                    $hardBlocked[] = [
                        'certificate_type' => $certType,
                        'reason' => 'insufficient_validity',
                        'block_reason_key' => $blockReasonKey,
                    ];
                }
            } else {
                $matched++;
            }
        }

        // Base score: proportion matched + partial credit for expiring
        $score = $total > 0
            ? ($matched * 1.0 + $expiringSoon * 0.7) / $total
            : 0.7;

        // Mandatory missing penalty
        $score -= $mandatoryMissing * 0.25;
        $score = max(0.0, min(1.0, $score));

        return [
            'score' => $score,
            'matched' => $matched,
            'missing' => $missing,
            'expired' => $expired,
            'total_required' => $total,
            'hard_blocked' => $hardBlocked,
        ];
    }

    /**
     * Experience fit: score based on sea-time experience vs vessel requirements.
     */
    private function computeExperienceFit(PoolCandidate $candidate, FleetVessel $vessel, array $profile): array
    {
        $expReq = $profile['experience'] ?? [];
        $vesselTypeMinMonths = $expReq['vessel_type_min_months'] ?? 12;
        $anyVesselMinMonths = $expReq['any_vessel_min_months'] ?? 24;
        $preferredTypes = $expReq['preferred_vessel_types'] ?? [];

        $vesselTypeKey = $this->profileService->resolveTypeKey($vessel->vessel_type ?? '');

        // Try contracts first
        $contracts = $candidate->contracts()->get();
        $source = 'contracts';

        $vesselTypeMonths = 0;
        $totalSeaMonths = 0;

        if ($contracts->isNotEmpty()) {
            foreach ($contracts as $contract) {
                $months = $contract->durationMonths();
                $totalSeaMonths += $months;

                $contractTypeKey = $this->profileService->resolveTypeKey($contract->vessel_type ?? '');
                if ($contractTypeKey === $vesselTypeKey || in_array($contractTypeKey, $preferredTypes)) {
                    $vesselTypeMonths += $months;
                }
            }
        } else {
            // Fallback to sea time logs
            $logs = $candidate->seaTimeLogs()->get();
            $source = 'sea_time_logs';

            foreach ($logs as $log) {
                $months = round(($log->calculated_days ?? 0) / 30.44, 1);
                $totalSeaMonths += $months;

                $logTypeKey = $this->profileService->resolveTypeKey($log->vessel_type ?? '');
                if ($logTypeKey === $vesselTypeKey || in_array($logTypeKey, $preferredTypes)) {
                    $vesselTypeMonths += $months;
                }
            }

            if ($logs->isEmpty()) {
                $source = 'insufficient_data';
            }
        }

        // Score = 0.6 × (vessel_type_months / required) + 0.4 × (total / required)
        $vesselRatio = $vesselTypeMinMonths > 0
            ? min(1.0, $vesselTypeMonths / $vesselTypeMinMonths)
            : 1.0;
        $totalRatio = $anyVesselMinMonths > 0
            ? min(1.0, $totalSeaMonths / $anyVesselMinMonths)
            : 1.0;

        $score = 0.6 * $vesselRatio + 0.4 * $totalRatio;

        if ($source === 'insufficient_data') {
            $score = 0.5; // neutral when no data
        }

        return [
            'score' => max(0.0, min(1.0, $score)),
            'vessel_type_months' => round($vesselTypeMonths, 1),
            'total_sea_months' => round($totalSeaMonths, 1),
            'source' => $source,
        ];
    }

    /**
     * Behavior fit: synergy score with threshold penalties from profile.
     */
    private function computeBehaviorFit(
        PoolCandidate $candidate,
        FleetVessel $vessel,
        string $rankCode,
        ?string $tenantId,
        array $profile,
    ): array {
        $synergyFit = $this->computeSynergyFit($candidate, $vessel, $rankCode, $tenantId);
        $score = $synergyFit['score'];

        // Apply behavior_threshold penalties
        $thresholds = $profile['behavior_thresholds'] ?? [];
        $belowThreshold = [];

        if (!empty($thresholds)) {
            $trust = CandidateTrustProfile::where('pool_candidate_id', $candidate->id)->first();
            $traitScores = $this->extractTraitScores01($trust);

            foreach ($thresholds as $dim => $minThreshold) {
                if (isset($traitScores[$dim]) && $traitScores[$dim] < $minThreshold) {
                    $belowThreshold[] = $dim;
                    $score -= 0.10;
                }
            }
        }

        $score = max(0.0, min(1.0, $score));

        return [
            'score' => $score,
            'engine' => $synergyFit['engine'],
            'below_threshold_dims' => $belowThreshold,
            'synergy_meta' => $synergyFit['synergy_meta'] ?? null,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    /**
     * Extract behavioral trait scores as 0..1 from trust profile.
     */
    private function extractTraitScores01(?CandidateTrustProfile $trust): array
    {
        if (!$trust) {
            return [];
        }

        $detail = $trust->detail_json ?? [];
        $dimensions = $detail['competency_engine']['score_by_dimension']
            ?? $detail['competency']['dimensions']
            ?? [];

        // Map competency dimensions to synergy trait names
        $mapping = [
            'DISCIPLINE'     => 'discipline',
            'TEAMWORK'       => 'teamwork',
            'COMMS'          => 'communication',
            'STRESS'         => 'stress_tolerance',
            'LEADERSHIP'     => 'initiative',
            'TECH_PRACTICAL' => 'respect', // closest proxy
        ];

        $traits = [];
        foreach ($mapping as $dim => $traitKey) {
            $val = $dimensions[$dim] ?? null;
            if (is_array($val)) {
                $val = $val['score'] ?? null;
            }
            if ($val !== null) {
                $traits[$traitKey] = min(1.0, max(0.0, (float) $val / 100));
            }
        }

        return $traits;
    }

    /**
     * Role-fit evaluation: checks if candidate's behavioral profile matches their applied role.
     */
    private function computeRoleFit(PoolCandidate $candidate, string $rankCode): array
    {
        try {
            $trust = CandidateTrustProfile::where('pool_candidate_id', $candidate->id)->first();
            $traitScores = $this->extractTraitScores01($trust);

            // Need at least some trait data to run evaluation
            if (empty($traitScores)) {
                return [
                    'role_fit_score' => 0.5,
                    'mismatch_level' => 'none',
                    'mismatch_flags' => [],
                    'inferred_role_key' => null,
                    'suggestions' => [],
                ];
            }

            $latestInterview = $candidate->latestCompletedInterview();
            $formInterviewId = $latestInterview?->id;

            $result = $this->roleFitEngine->evaluate(
                $rankCode,
                $traitScores,
                $candidate->id,
                $formInterviewId,
            );

            // Defense-in-depth: filter out any cross-department suggestions
            // even though the engine already prevents them
            $normalizedRole = \App\Config\MaritimeRole::normalize($rankCode) ?? $rankCode;
            $appliedDept = \App\Config\MaritimeRole::departmentFor($normalizedRole);
            if ($appliedDept && !empty($result['suggestions'])) {
                $result['suggestions'] = array_values(array_filter(
                    $result['suggestions'],
                    fn($s) => \App\Config\MaritimeRole::departmentFor($s['role_key']) === $appliedDept,
                ));
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('CandidateDecisionService: role-fit evaluation failed', [
                'candidate_id' => $candidate->id,
                'rank_code' => $rankCode,
                'error' => $e->getMessage(),
            ]);

            return [
                'role_fit_score' => 0.5,
                'mismatch_level' => 'none',
                'mismatch_flags' => [],
                'inferred_role_key' => null,
                'suggestions' => [],
            ];
        }
    }

    private function scoreLabel(float $score): string
    {
        if ($score >= 0.80) return 'strong_match';
        if ($score >= 0.65) return 'good_match';
        if ($score >= 0.45) return 'moderate_match';
        if ($score >= 0.30) return 'weak_match';
        return 'poor_match';
    }
}
