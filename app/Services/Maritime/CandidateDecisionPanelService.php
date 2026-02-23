<?php

namespace App\Services\Maritime;

use App\Models\CandidateCommandProfile;
use App\Models\CandidatePhaseReview;
use App\Models\CandidateQualificationCheck;
use App\Models\FormInterview;
use App\Models\LanguageAssessment;
use App\Models\PoolCandidate;
use App\Models\SeafarerCertificate;

class CandidateDecisionPanelService
{
    public function __construct(
        private readonly CrewSynergyPreviewService $synergyPreview,
        private readonly CertificateLifecycleService $lifecycleService,
    ) {}
    /**
     * Known qualification keys for maritime candidates.
     * Baselines from source_meta.certificates, then overridden by candidate_qualification_checks.
     */
    private const KNOWN_QUALIFICATIONS = [
        'stcw' => 'STCW',
        'coc' => 'COC',
        'goc' => 'GOC',
        'ecdis' => 'ECDIS',
        'brm' => 'BRM',
        'arpa' => 'ARPA',
        'passport' => 'Passport',
        'seamans_book' => "Seaman's Book",
        'medical' => 'Medical',
    ];

    public function build(string $candidateId): ?array
    {
        $candidate = PoolCandidate::with(['trustProfile'])->find($candidateId);
        if (!$candidate) {
            return null;
        }

        // Clean workflow guard: no decision packet without completed behavioral interview
        if (config('maritime.clean_workflow_v1')) {
            $hasCompleted = $candidate->formInterviews()
                ->where('status', 'completed')
                ->exists();
            if (!$hasCompleted) {
                return null;
            }
        }

        $qualifications = $this->buildQualifications($candidate);
        $competencies = $this->buildCompetencies($candidate);
        $language = $this->buildLanguage($candidateId);

        return [
            'candidate' => $this->buildCandidateHeader($candidate),
            'qualifications' => $qualifications,
            'competencies' => $competencies,
            'command_profile' => $this->buildCommandProfile($candidateId),
            'language' => $language,
            'synergy_preview' => $this->synergyPreview->previewForCandidate($candidateId),
            'decision_state' => $this->computeDecisionState($qualifications, $competencies, $language),
            'actions' => $this->buildActions($candidateId),
        ];
    }

    private function buildCandidateHeader(PoolCandidate $c): array
    {
        return [
            'id' => $c->id,
            'first_name' => $c->first_name,
            'last_name' => $c->last_name,
            'rank' => data_get($c->source_meta, 'rank'),
            'country_code' => $c->country_code,
            'status' => $c->status,
        ];
    }

    private function buildQualifications(PoolCandidate $c): array
    {
        $declaredCerts = $c->source_meta['certificates'] ?? [];
        $checks = CandidateQualificationCheck::where('candidate_id', $c->id)
            ->get()
            ->keyBy('qualification_key');

        // Load all SeafarerCertificates for lifecycle data, keyed by certificate_type (lowercased)
        $seafarerCerts = SeafarerCertificate::where('pool_candidate_id', $c->id)
            ->get()
            ->keyBy(fn(SeafarerCertificate $cert) => strtolower($cert->certificate_type));

        $items = [];
        $verifiedCount = 0;
        $expiryStats = ['expired' => 0, 'critical' => 0, 'expiring_soon' => 0, 'valid' => 0, 'unknown' => 0];

        // Build full list from known qualifications
        $allKeys = array_keys(self::KNOWN_QUALIFICATIONS);
        // Add any extra declared certs not in known list (normalized via alias map)
        foreach ($declaredCerts as $cert) {
            $key = $this->normalizeCertKey($cert);
            if (!in_array($key, $allKeys)) {
                $allKeys[] = $key;
            }
        }

        $normalizedDeclared = array_map(fn($cert) => $this->normalizeCertKey($cert), $declaredCerts);

        foreach ($allKeys as $key) {
            $check = $checks->get($key);
            $isDeclared = in_array($key, $normalizedDeclared);

            // Skip if not declared and no check record
            if (!$isDeclared && !$check) {
                continue;
            }

            $status = $check?->status ?? ($isDeclared ? 'self_declared' : null);
            if (!$status) continue;

            if ($status === 'verified') {
                $verifiedCount++;
            }

            // Certificate lifecycle (expiry) data
            $seafarerCert = $seafarerCerts->get($key);
            $expiryData = [
                'expires_at' => null,
                'expiry_source' => null,
                'risk_level' => 'unknown',
                'risk_color' => 'gray',
                'days_remaining' => null,
                'risk_label' => null,
            ];

            if ($seafarerCert) {
                $risk = $this->lifecycleService->getExpiryRiskLevel($seafarerCert);
                $expiryData = [
                    'expires_at' => $seafarerCert->expires_at?->toDateString(),
                    'expiry_source' => $risk['expiry_source'],
                    'risk_level' => $risk['level'],
                    'risk_color' => $risk['color'],
                    'days_remaining' => $risk['days_remaining'],
                    'risk_label' => $risk['label'],
                ];
                $expiryStats[$risk['level']]++;
            } else {
                $expiryStats['unknown']++;
            }

            $items[] = array_merge([
                'key' => $key,
                'label' => __("maritime.qualification.{$key}") !== "maritime.qualification.{$key}"
                    ? __("maritime.qualification.{$key}")
                    : (self::KNOWN_QUALIFICATIONS[$key] ?? strtoupper($key)),
                'status' => $status,
                'evidence_url' => $check?->evidence_url,
                'verified_by' => $check?->verified_by,
                'verified_at' => $check?->verified_at?->toIso8601String(),
                'notes' => $check?->notes,
            ], $expiryData);
        }

        return [
            'items' => $items,
            'summary' => [
                'verified_count' => $verifiedCount,
                'total_count' => count($items),
                'expiry_stats' => $expiryStats,
            ],
        ];
    }

    private function buildCompetencies(PoolCandidate $c): array
    {
        // Get latest completed interview for competency data
        $interview = FormInterview::where('pool_candidate_id', $c->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        $trustProfile = $c->trustProfile;

        // Get phase reviews
        $reviews = CandidatePhaseReview::where('candidate_id', $c->id)
            ->get()
            ->keyBy('phase_key');

        $phases = [];

        // Standard competency phase
        $competencyReview = $reviews->get('standard_competency');
        $competencyStatus = $competencyReview?->status ?? ($interview ? 'completed' : 'not_started');

        $topStrengths = [];
        $topRisks = [];
        if ($trustProfile && $trustProfile->detail_json) {
            $competencyDetail = data_get($trustProfile->detail_json, 'competency');
            if ($competencyDetail) {
                $topStrengths = data_get($competencyDetail, 'evidence_summary.strengths', []);
                $topRisks = data_get($competencyDetail, 'evidence_summary.concerns', []);
            }
        }

        $phases[] = [
            'phase_key' => 'standard_competency',
            'status' => $competencyStatus,
            'overall_score' => $trustProfile?->competency_score,
            'top_strengths' => $topStrengths,
            'top_risks' => $topRisks,
            'reviewed_by' => $competencyReview?->reviewed_by,
            'reviewed_at' => $competencyReview?->reviewed_at?->toIso8601String(),
            'review_notes' => $competencyReview?->review_notes,
            'last_updated_at' => $trustProfile?->competency_computed_at?->toIso8601String(),
        ];

        return [
            'phases' => $phases,
        ];
    }

    private function buildCommandProfile(string $candidateId): ?array
    {
        $profile = CandidateCommandProfile::where('candidate_id', $candidateId)->first();

        if (!$profile) {
            return null;
        }

        $missingFields = [];
        $fieldChecks = [
            'vessel_experience' => 'vessel_history',
            'trading_areas' => 'trading_areas',
            'dwt_history' => 'dwt_history',
            'cargo_history' => 'cargo_history',
            'incident_history' => 'incident_history',
            'crew_scale_history' => 'crew_scale',
            'automation_exposure' => 'automation_exposure',
        ];

        foreach ($fieldChecks as $column => $label) {
            $val = $profile->$column;
            if (empty($val) || (is_array($val) && count($val) === 0)) {
                $missingFields[] = $label;
            }
        }

        return [
            'source' => $profile->source ?? 'derived',
            'completeness_pct' => $profile->completeness_pct ?? 0,
            'generated_at' => $profile->generated_at?->toIso8601String() ?? $profile->created_at?->toIso8601String(),
            'missing_fields' => $missingFields,
            'summary' => [
                'vessel_types' => $profile->getVesselTypes(),
                'trading_areas' => $profile->trading_areas ?? [],
                'dwt_range' => $this->formatDwtRange($profile),
            ],
        ];
    }

    private function formatDwtRange(CandidateCommandProfile $p): ?string
    {
        $min = $p->getDwtMin();
        $max = $p->getDwtMax();
        if (!$min && !$max) return null;

        $fmt = fn($v) => $v >= 1000 ? round($v / 1000) . 'k' : $v;
        return ($min ? $fmt($min) : '0') . '-' . ($max ? $fmt($max) : '?');
    }

    private function buildLanguage(string $candidateId): ?array
    {
        $assessment = LanguageAssessment::forCandidate($candidateId);
        if (!$assessment) {
            return null;
        }

        return [
            'declared_level' => $assessment->declared_level,
            'estimated_level' => $assessment->estimated_level,
            'confidence' => $assessment->confidence,
            'overall_score' => $assessment->overall_score,
            'locked_level' => $assessment->locked_level,
            'locked_by' => $assessment->locked_by,
            'locked_at' => $assessment->locked_at?->toIso8601String(),
            'flags' => data_get($assessment->signals, 'flags', []),
        ];
    }

    private function buildActions(string $candidateId): array
    {
        $langAssessment = LanguageAssessment::forCandidate($candidateId);

        return [
            'can_lock_language' => $langAssessment !== null && $langAssessment->locked_level === null && $langAssessment->estimated_level !== null,
            'can_verify_qualifications' => true,
            'can_approve_competencies' => true,
        ];
    }

    /**
     * Compute a single decision_state from panel data.
     * Priority order: rejected → missing_language → missing_qual_verification → missing_competency → ready_for_shortlist
     */
    private function computeDecisionState(array $qualifications, array $competencies, ?array $language): array
    {
        $blockers = [];

        // Check for rejected qualifications
        $hasRejected = false;
        foreach ($qualifications['items'] as $item) {
            if ($item['status'] === 'rejected') {
                $hasRejected = true;
                break;
            }
        }
        if ($hasRejected) {
            $blockers[] = 'rejected_qualification';
        }

        // Check unverified qualifications (self_declared or uploaded but not verified)
        $unverifiedCount = 0;
        foreach ($qualifications['items'] as $item) {
            if (in_array($item['status'], ['self_declared', 'uploaded'])) {
                $unverifiedCount++;
            }
        }
        if ($unverifiedCount > 0) {
            $blockers[] = 'missing_qual_verification';
        }

        // Check language assessment
        if (!$language || $language['estimated_level'] === null) {
            $blockers[] = 'missing_language';
        }

        // Check competency phases — need at least one phase completed/approved
        $hasCompletedPhase = false;
        foreach ($competencies['phases'] as $phase) {
            if (in_array($phase['status'], ['completed', 'approved'])) {
                $hasCompletedPhase = true;
                break;
            }
        }
        if (!$hasCompletedPhase) {
            $blockers[] = 'missing_competency';
        }

        // Derive state from blockers priority
        if (in_array('rejected_qualification', $blockers)) {
            $state = 'blocked_by_rejection';
        } elseif (empty($blockers)) {
            $state = 'ready_for_shortlist';
        } else {
            // Primary blocker = first in list (priority order)
            $state = $blockers[0];
        }

        return [
            'state' => $state,
            'blockers' => $blockers,
            'is_ready' => empty($blockers),
        ];
    }

    /**
     * Normalize a certificate key using the config alias map.
     * E.g. "G.O.C" → "goc", "seaman_book" → "seamans_book"
     */
    private function normalizeCertKey(string $cert): string
    {
        $key = strtolower(trim($cert));
        $aliases = config('maritime.decision_panel.cert_aliases', []);

        return $aliases[$key] ?? $key;
    }
}
