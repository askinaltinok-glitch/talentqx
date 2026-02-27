<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BehavioralProfile;
use App\Models\CandidateCredential;
use App\Models\CandidateScoringVector;
use App\Models\CandidateTimelineEvent;
use App\Models\LanguageAssessment;
use App\Models\PoolCandidate;
use App\Helpers\RadarChartGenerator;
use App\Jobs\SendInterviewInvitationJob;
use App\Models\CandidateConsent;
use App\Models\CandidateDecisionOverride;
use App\Models\CandidatePhaseReview;
use App\Models\DecisionAuditLog;
use App\Models\InterviewInvitation;
use App\Models\MarketplaceAccessRequest;
use App\Models\VoiceTranscription;
use App\Services\ExecutiveSummary\ExecutiveSummaryBuilder;
use App\Services\Maritime\CertificateLifecycleService;
use App\Services\Behavioral\VesselFitEvidenceService;
use App\Services\Behavioral\VesselFitProvenanceService;
use App\Services\Fleet\CrewSynergyEngineV2;
use App\Services\KVKK\PoolCandidateErasureService;
use App\Services\Trust\CrewReliabilityCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PoolCandidate::where('primary_industry', 'maritime')
            ->with(['formInterviews' => fn($q) => $q->latest()->limit(1)]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('source_channel')) {
            $query->where('source_channel', $request->input('source_channel'));
        }
        if ($request->filled('english_level')) {
            $query->where('english_level_self', $request->input('english_level'));
        }
        if ($request->boolean('seafarer')) {
            $query->where('seafarer', true);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowed = ['last_assessed_at', 'created_at', 'first_name', 'last_name'];
        if (in_array($sortBy, $allowed)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $candidates = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $candidates->map(fn($c) => [
                'id' => $c->id,
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'country_code' => $c->country_code,
                'nationality' => $c->nationality,
                'license_country' => $c->license_country,
                'english_level_self' => $c->english_level_self,
                'source_channel' => $c->source_channel,
                'source_type' => $c->source_type,
                'source_label' => $c->source_label,
                'status' => $c->status,
                'seafarer' => $c->seafarer,
                'last_assessed_at' => $c->last_assessed_at?->toIso8601String(),
                'form_interviews' => $c->formInterviews->map(fn($i) => [
                    'id' => $i->id,
                    'position_code' => $i->position_code,
                    'final_score' => $i->calibrated_score ?? $i->final_score,
                    'calibrated_score' => $i->calibrated_score,
                    'decision' => $i->decision,
                    'completed_at' => $i->completed_at?->toIso8601String(),
                ]),
                'created_at' => $c->created_at->toIso8601String(),
            ]),
            'pagination' => [
                'current_page' => $candidates->currentPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
                'last_page' => $candidates->lastPage(),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        $base = PoolCandidate::where('primary_industry', 'maritime');

        $bySource = (clone $base)
            ->selectRaw('source_channel, count(*) as cnt')
            ->groupBy('source_channel')
            ->pluck('cnt', 'source_channel');

        return response()->json([
            'success' => true,
            'data' => [
                'total_candidates' => (clone $base)->count(),
                'in_pool' => (clone $base)->where('status', 'in_pool')->count(),
                'presented' => (clone $base)->where('status', 'presented_to_company')->count(),
                'hired' => (clone $base)->where('status', 'hired')->count(),
                'by_source' => $bySource,
            ],
        ]);
    }

    public function present(string $id): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->findOrFail($id);

        if ($candidate->status !== PoolCandidate::STATUS_IN_POOL) {
            return response()->json(['success' => false, 'message' => 'Only pool candidates can be presented'], 422);
        }

        $candidate->markAsPresented();

        return response()->json(['success' => true, 'status' => $candidate->fresh()->status]);
    }

    public function hire(string $id): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->findOrFail($id);

        if (!in_array($candidate->status, [PoolCandidate::STATUS_IN_POOL, PoolCandidate::STATUS_PRESENTED])) {
            return response()->json(['success' => false, 'message' => 'Only pool or presented candidates can be hired'], 422);
        }

        $candidate->markAsHired();

        return response()->json(['success' => true, 'status' => $candidate->fresh()->status]);
    }

    /**
     * GET /v1/octopus/admin/candidates/{id}
     *
     * Full candidate detail with profile, credentials, timeline.
     */
    public function show(string $id): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')
            ->with([
                'profile',
                'contactPoints',
                'credentials',
                'certificates',
                'contracts.aisVerification',
                'contracts.latestAisVerification',
                'contracts.vessel',
                'trustProfile',
                'formInterviews' => fn($q) => $q->latest()->limit(5),
            ])
            ->find($id);

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $candidate->id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'country_code' => $candidate->country_code,
                'nationality' => $candidate->nationality,
                'country_of_residence' => $candidate->country_of_residence,
                'passport_expiry' => $candidate->passport_expiry?->toDateString(),
                'visa_status' => $candidate->visa_status,
                'license_country' => $candidate->license_country,
                'license_class' => $candidate->license_class,
                'flag_endorsement' => $candidate->flag_endorsement,
                'preferred_language' => $candidate->preferred_language,
                'english_level_self' => $candidate->english_level_self,
                'source_channel' => $candidate->source_channel,
                'source_type' => $candidate->source_type,
                'source_company_id' => $candidate->source_company_id,
                'source_label' => $candidate->source_label,
                'candidate_source' => $candidate->candidate_source,
                'status' => $candidate->status,
                'seafarer' => $candidate->seafarer,
                'last_assessed_at' => $candidate->last_assessed_at?->toIso8601String(),
                'created_at' => $candidate->created_at->toIso8601String(),

                'profile' => $candidate->profile ? [
                    'status' => $candidate->profile->status,
                    'preferred_language' => $candidate->profile->preferred_language,
                    'timezone' => $candidate->profile->timezone,
                    'marketing_opt_in' => $candidate->profile->marketing_opt_in,
                    'reminders_opt_in' => $candidate->profile->reminders_opt_in,
                    'headhunt_opt_in' => $candidate->profile->headhunt_opt_in,
                    'data_processing_consent_at' => $candidate->profile->data_processing_consent_at?->toIso8601String(),
                    'blocked_reason' => $candidate->profile->blocked_reason,
                    'blocked_at' => $candidate->profile->blocked_at?->toIso8601String(),
                ] : null,

                'contact_points' => $candidate->contactPoints->map(fn($cp) => [
                    'id' => $cp->id,
                    'type' => $cp->type,
                    'value' => $cp->value,
                    'is_primary' => $cp->is_primary,
                    'is_verified' => $cp->is_verified,
                    'verified_at' => $cp->verified_at?->toIso8601String(),
                ]),

                'credentials' => $candidate->credentials->map(fn(CandidateCredential $c) => [
                    'id' => $c->id,
                    'credential_type' => $c->credential_type,
                    'credential_number' => $c->credential_number,
                    'issuer' => $c->issuer,
                    'issued_at' => $c->issued_at?->toDateString(),
                    'expires_at' => $c->expires_at?->toDateString(),
                    'days_until_expiry' => $c->days_until_expiry,
                    'verification_status' => $c->verification_status,
                    'last_reminded_at' => $c->last_reminded_at?->toIso8601String(),
                ]),

                'certificates' => app(CertificateLifecycleService::class)
                    ->enrichWithRiskLevels($candidate->certificates),
                'certificate_compliance' => app(CertificateLifecycleService::class)
                    ->getComplianceSummary($candidate->certificates),

                'contracts_count' => $candidate->contracts->count(),
                'contracts' => $candidate->contracts->map(
                    fn($c) => \App\Presenters\CandidateContractAisPresenter::present($c)
                ),
                'ais_kpis' => \App\Presenters\CandidateContractAisPresenter::aggregateKpis($candidate->contracts),

                'sea_time' => \App\Presenters\SeaTimePresenter::fromTrustProfile($candidate->trustProfile),
                'sea_time_logs' => \App\Presenters\SeaTimePresenter::contractLogs($candidate->id),

                'rank_stcw' => \App\Presenters\RankStcwPresenter::fromTrustProfile($candidate->trustProfile),

                'stability_risk' => \App\Presenters\StabilityRiskPresenter::fromTrustProfile($candidate->trustProfile),

                'compliance_pack' => \App\Presenters\CompliancePackPresenter::fromTrustProfile($candidate->trustProfile),

                'competency' => \App\Presenters\CompetencyPresenter::fromTrustProfile($candidate->trustProfile),

                'executive_summary' => app(ExecutiveSummaryBuilder::class)->build($candidate),

                'cri' => $candidate->trustProfile ? [
                    'cri_score' => $candidate->trustProfile->cri_score,
                    'confidence_level' => $candidate->trustProfile->confidence_level,
                    'risk_notes' => $candidate->trustProfile->risk_notes,
                    'flags' => $candidate->trustProfile->flags_json,
                    'computed_at' => $candidate->trustProfile->computed_at?->toIso8601String(),
                ] : null,

                'interviews' => $candidate->formInterviews->map(fn($i) => [
                    'id' => $i->id,
                    'position_code' => $i->position_code,
                    'status' => $i->status,
                    'final_score' => $i->calibrated_score ?? $i->final_score,
                    'decision' => $i->decision,
                    'completed_at' => $i->completed_at?->toIso8601String(),
                ]),

                'language_assessment' => $this->formatLanguageAssessment($candidate->id),

                // Phase B: Behavioral profile + scoring vector
                'behavioral_profile' => $this->formatBehavioralProfile($candidate->id),
                'scoring_vector' => $this->formatScoringVector($candidate->id),
            ],
        ]);
    }

    /**
     * GET /v1/octopus/admin/candidates/{id}/cri
     *
     * Get or compute CRI for candidate (full detail for admin).
     */
    public function cri(string $id, CrewReliabilityCalculator $calculator): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->findOrFail($id);

        $trustProfile = $candidate->trustProfile;

        // Compute if not yet computed
        if (!$trustProfile) {
            $trustProfile = $calculator->compute($candidate->id);
            if (!$trustProfile) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'CRI could not be computed (no contracts)',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cri_score' => $trustProfile->cri_score,
                'confidence_level' => $trustProfile->confidence_level,
                'short_contract_ratio' => $trustProfile->short_contract_ratio,
                'overlap_count' => $trustProfile->overlap_count,
                'gap_months_total' => $trustProfile->gap_months_total,
                'unique_company_count_3y' => $trustProfile->unique_company_count_3y,
                'rank_anomaly_flag' => $trustProfile->rank_anomaly_flag,
                'frequent_switch_flag' => $trustProfile->frequent_switch_flag,
                'timeline_inconsistency_flag' => $trustProfile->timeline_inconsistency_flag,
                'flags' => $trustProfile->flags_json,
                'risk_notes' => $trustProfile->risk_notes,
                'detail' => $trustProfile->detail_json,
                'computed_at' => $trustProfile->computed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /v1/octopus/admin/candidates/{id}/timeline
     *
     * Full timeline (all event types, admin sees everything).
     */
    public function timeline(string $id): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->find($id);

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found'], 404);
        }

        $events = $candidate->timelineEvents()
            ->limit(50)
            ->get()
            ->map(fn(CandidateTimelineEvent $e) => [
                'id' => $e->id,
                'event_type' => $e->event_type,
                'source' => $e->source,
                'payload' => $e->payload_json,
                'created_at' => $e->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * GET /v1/octopus/admin/candidates/{id}/compliance-pack.pdf
     *
     * Download compliance pack PDF report.
     */
    public function compliancePdf(string $id)
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')
            ->with(['trustProfile', 'contracts.latestAisVerification'])
            ->find($id);

        if (!$candidate) {
            abort(404, 'Candidate not found');
        }

        $tp = $candidate->trustProfile;
        if (!$tp || !$tp->compliance_score) {
            abort(404, 'Compliance pack not computed');
        }

        $detail = $tp->detail_json ?? [];
        $compliancePack = $detail['compliance_pack'] ?? [];
        $sectionScores = $compliancePack['section_scores'] ?? [];

        // Build radar chart data — sections with raw scores
        $scores = [];
        $labels = [];
        $sectionLabels = [
            'cri' => 'CRI',
            'technical' => 'Technical',
            'stability' => 'Stability',
            'stcw' => 'STCW',
            'ais' => 'AIS',
        ];

        foreach ($sectionScores as $section) {
            $key = $section['section'];
            $scores[$key] = $section['available'] ? (int) round($section['raw_score']) : 0;
            $labels[$key] = $sectionLabels[$key] ?? $key;
        }

        $radarChart = !empty($scores) ? RadarChartGenerator::generate($scores, $labels, 480) : null;

        $execSummary = app(ExecutiveSummaryBuilder::class)->build($candidate);

        $pdf = Pdf::loadView('pdf.compliance-pack', [
            'candidate' => $candidate,
            'trustProfile' => $tp,
            'compliancePack' => $compliancePack,
            'sectionScores' => $sectionScores,
            'radarChart' => $radarChart,
            'rankStcw' => $detail['rank_stcw'] ?? null,
            'stabilityRisk' => $detail['stability_risk'] ?? null,
            'execSummary' => $execSummary,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $name = str_replace(' ', '_', $candidate->first_name . '_' . $candidate->last_name);
        $filename = "Compliance_Pack_{$name}_" . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * POST /v1/octopus/admin/candidates/{id}/executive-decision-override
     *
     * Append-only manual decision override.
     */
    public function executiveDecisionOverride(string $id, Request $request): JsonResponse
    {
        if (!config('maritime.exec_summary_override_v1')) {
            return response()->json(['success' => false, 'message' => 'Override feature is disabled'], 403);
        }

        $candidate = PoolCandidate::where('primary_industry', 'maritime')->findOrFail($id);

        $validated = $request->validate([
            'decision' => 'required|in:approve,review,reject',
            'reason' => 'required|string|min:20',
            'expires_at' => 'nullable|date|after:now',
        ]);

        // Fetch previous override for old_state tracking
        $previousOverride = CandidateDecisionOverride::where('candidate_id', $candidate->id)
            ->latest()
            ->first();

        $override = CandidateDecisionOverride::create([
            'candidate_id' => $candidate->id,
            'decision' => $validated['decision'],
            'reason' => $validated['reason'],
            'created_by' => $request->user()?->id,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        DecisionAuditLog::create([
            'candidate_id' => $candidate->id,
            'action' => DecisionAuditLog::ACTION_OVERRIDE,
            'performed_by' => $request->user()?->id,
            'old_state' => $previousOverride?->decision,
            'new_state' => $validated['decision'],
            'reason' => $validated['reason'],
            'metadata' => [
                'ip_address' => $request->ip(),
                'override_id' => $override->id,
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $override->id,
                'decision' => $override->decision,
                'reason' => $override->reason,
                'created_at' => $override->created_at->toIso8601String(),
                'expires_at' => $override->expires_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * POST /v1/octopus/admin/candidates/{id}/send-interview-invite
     *
     * Immediately dispatch interview invitation for a candidate (company-uploaded flow).
     * No delay — used when admin manually triggers invite for company staff.
     */
    public function sendInterviewInvite(string $id, Request $request): JsonResponse
    {
        if (!config('maritime.clean_workflow_v1') && !config('maritime.question_bank_v1')) {
            return response()->json([
                'success' => false,
                'message' => 'Interview invitation workflow is not enabled',
            ], 403);
        }

        $candidate = PoolCandidate::where('primary_industry', 'maritime')->find($id);

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found'], 404);
        }

        if (!$candidate->email) {
            return response()->json(['success' => false, 'message' => 'Candidate has no email address'], 422);
        }

        // Check for active invitation
        $existing = InterviewInvitation::where('pool_candidate_id', $candidate->id)
            ->whereIn('status', [InterviewInvitation::STATUS_INVITED, InterviewInvitation::STATUS_STARTED])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Active invitation already exists',
                'data' => [
                    'invitation_id' => $existing->id,
                    'status' => $existing->status,
                    'expires_at' => $existing->expires_at?->toIso8601String(),
                ],
            ], 409);
        }

        // Dispatch immediately (no delay for company flow)
        SendInterviewInvitationJob::dispatch($candidate->id);

        CandidateTimelineEvent::record(
            $candidate->id,
            'interview_invite_manual',
            CandidateTimelineEvent::SOURCE_ADMIN,
            [
                'triggered_by' => $request->user()?->id,
                'reason' => 'admin_manual_dispatch',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Interview invitation dispatched',
        ]);
    }

    /**
     * GET /v1/octopus/admin/candidates/{id}/decision-packet.pdf
     *
     * Download decision packet PDF for shipowners.
     */
    public function decisionPacketPdf(string $id, Request $request)
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')
            ->with(['trustProfile', 'contracts.latestAisVerification', 'credentials', 'certificates'])
            ->find($id);

        if (!$candidate) {
            abort(404, 'Candidate not found');
        }

        $execSummary = app(ExecutiveSummaryBuilder::class)->build($candidate);

        $tp = $candidate->trustProfile;
        $detail = $tp?->detail_json ?? [];

        // Build radar chart if compliance data exists
        $radarChart = null;
        $compliancePack = $detail['compliance_pack'] ?? null;
        if ($compliancePack) {
            $scores = [];
            $labels = [];
            $sectionLabels = ['cri' => 'CRI', 'technical' => 'Technical', 'stability' => 'Stability', 'stcw' => 'STCW', 'ais' => 'AIS'];
            foreach ($compliancePack['section_scores'] ?? [] as $section) {
                $key = $section['section'];
                $scores[$key] = $section['available'] ? (int) round($section['raw_score']) : 0;
                $labels[$key] = $sectionLabels[$key] ?? $key;
            }
            if (!empty($scores)) {
                $radarChart = RadarChartGenerator::generate($scores, $labels, 480);
            }
        }

        // Certificate lifecycle risk data
        $certService = app(CertificateLifecycleService::class);
        $certificateRisks = $certService->enrichWithRiskLevels($candidate->certificates);

        // Vessel fit evidence
        $vesselFitEvidence = [];
        $behavioralProfile = BehavioralProfile::where('candidate_id', $candidate->id)
            ->where('version', 'v1')
            ->first();
        if ($behavioralProfile && !empty($behavioralProfile->fit_json) && is_array($behavioralProfile->fit_json)) {
            $vesselFitEvidence = app(VesselFitEvidenceService::class)
                ->compute($candidate->id, $behavioralProfile->fit_json, (float) ($behavioralProfile->confidence ?? 0));
        }

        // Find the latest completed interview for evidence section
        $interview = \App\Models\FormInterview::with('answers')
            ->where('pool_candidate_id', $candidate->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        // Crew compatibility (V2) — find active vessel slot
        $compatibilityData = null;
        $synergyEngine = app(CrewSynergyEngineV2::class);
        if ($synergyEngine->isEnabled()) {
            $activeSlot = \Illuminate\Support\Facades\DB::table('vessel_crew_skeleton_slots')
                ->where('candidate_id', $candidate->id)
                ->where('is_active', true)
                ->first();
            if ($activeSlot) {
                $compatibilityData = $synergyEngine->computeCompatibility($candidate->id, $activeSlot->vessel_id);
            }
        }

        // --- V2: English Gate / Voice Data ---
        $englishGateData = null;
        if ($interview) {
            $meta = $interview->meta ?? [];
            $langAssessment = LanguageAssessment::forCandidate($candidate->id);
            $voiceTranscriptions = VoiceTranscription::where('interview_id', $interview->id)
                ->where('status', 'done')
                ->get();

            $totalTranscriptLength = $voiceTranscriptions->sum(fn($vt) => mb_strlen($vt->transcript_text ?? ''));
            $totalVoiceDuration = $voiceTranscriptions->sum('duration_ms');

            $englishGateData = [
                'cefr_level' => $meta['english_gate_cefr'] ?? $langAssessment?->estimated_level,
                'confidence' => $meta['english_gate_confidence'] ?? $langAssessment?->confidence,
                'declared_level' => $langAssessment?->declared_level,
                'estimated_level' => $langAssessment?->estimated_level,
                'locked_level' => $langAssessment?->locked_level,
                'transcript_length' => $totalTranscriptLength,
                'voice_duration_ms' => $totalVoiceDuration,
                'voice_count' => $voiceTranscriptions->count(),
                'provider' => $voiceTranscriptions->first()?->provider,
                'model' => $voiceTranscriptions->first()?->model,
            ];

            // Only include if there's actual data
            if (!$englishGateData['cefr_level'] && !$englishGateData['declared_level'] && $voiceTranscriptions->isEmpty()) {
                $englishGateData = null;
            }
        }

        // --- V2: Question Block Summary ---
        $questionBlockSummary = null;
        if ($interview && $interview->answers && $interview->answers->count() > 0) {
            $answers = $interview->answers;
            $questionBlockSummary = [
                'core' => $answers->whereBetween('slot', [1, 12])->count(),
                'role' => $answers->whereBetween('slot', [13, 18])->count(),
                'safety' => $answers->whereBetween('slot', [19, 22])->count(),
                'english' => $answers->whereBetween('slot', [23, 25])->count(),
                'total' => $answers->count(),
                'workflow' => ($interview->meta ?? [])['workflow'] ?? null,
            ];
        }

        // --- V2: Admin Decision / Override ---
        $phaseReviews = CandidatePhaseReview::where('candidate_id', $candidate->id)
            ->orderByDesc('reviewed_at')
            ->limit(10)
            ->get();

        $latestOverride = CandidateDecisionOverride::where('candidate_id', $candidate->id)
            ->latest()
            ->first();

        $adminDecisionData = [
            'phase_reviews' => $phaseReviews->map(fn($pr) => [
                'phase_key' => $pr->phase_key,
                'status' => $pr->status,
                'review_notes' => $pr->review_notes,
                'reviewed_at' => $pr->reviewed_at?->format('d M Y H:i'),
            ])->toArray(),
            'override' => $latestOverride ? [
                'decision' => $latestOverride->decision,
                'reason' => $latestOverride->reason,
                'active' => $latestOverride->expires_at === null || $latestOverride->expires_at->isFuture(),
                'created_at' => $latestOverride->created_at?->format('d M Y H:i'),
                'expires_at' => $latestOverride->expires_at?->format('d M Y H:i'),
            ] : null,
        ];

        // --- V2: Marketplace Data ---
        $marketplaceData = MarketplaceAccessRequest::where('candidate_id', $candidate->id)
            ->with('requestingCompany')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($mar) => [
                'requesting_company' => $mar->requestingCompany?->name ?? 'Unknown',
                'status' => $mar->status,
                'requested_at' => $mar->created_at?->format('d M Y'),
                'responded_at' => $mar->responded_at?->format('d M Y'),
            ])
            ->toArray();

        // --- V2: Consent Snapshot ---
        $consentSnapshot = [];
        if ($interview) {
            $consentSnapshot = CandidateConsent::where('form_interview_id', $interview->id)
                ->get()
                ->map(fn($c) => [
                    'type' => $c->consent_type,
                    'regulation' => $c->regulation,
                    'version' => $c->consent_version,
                    'granted' => $c->granted,
                    'valid' => $c->isValid(),
                    'consented_at' => $c->consented_at?->format('d M Y H:i'),
                    'ip' => $c->ip_address,
                ])
                ->toArray();
        }

        // --- V2: Audit Log on Download ---
        $name = str_replace(' ', '_', $candidate->first_name . '_' . $candidate->last_name);
        $filename = "Decision_Packet_{$name}_" . date('Y-m-d') . '.pdf';

        DecisionAuditLog::create([
            'candidate_id' => $candidate->id,
            'interview_id' => $interview?->id,
            'action' => DecisionAuditLog::ACTION_DOWNLOAD_PACKET,
            'performed_by' => $request->user()?->id,
            'metadata' => [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'filename' => $filename,
            ],
        ]);

        $pdf = Pdf::loadView('pdf.decision-packet', [
            'candidate' => $candidate,
            'interview' => $interview,
            'trustProfile' => $tp,
            'execSummary' => $execSummary,
            'compliancePack' => $compliancePack,
            'rankStcw' => $detail['rank_stcw'] ?? null,
            'stabilityRisk' => $detail['stability_risk'] ?? null,
            'radarChart' => $radarChart,
            'certificateRisks' => $certificateRisks,
            'vesselFitEvidence' => $vesselFitEvidence,
            'compatibilityData' => $compatibilityData,
            // V2 additions
            'englishGateData' => $englishGateData,
            'questionBlockSummary' => $questionBlockSummary,
            'adminDecisionData' => $adminDecisionData,
            'marketplaceData' => $marketplaceData,
            'consentSnapshot' => $consentSnapshot,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * DELETE /v1/octopus/admin/candidates/{id}/erase
     *
     * KVKK right-to-erasure: anonymize all PII, cascade to voice/interviews/certificates.
     */
    public function eraseCandidate(string $id, Request $request, PoolCandidateErasureService $service): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->find($id);

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found'], 404);
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $result = $service->erase($candidate, $validated['reason'], $request->user()?->id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Candidate data erased successfully.',
            'erased_types' => $result['erased_types'],
        ]);
    }

    /**
     * GET /v1/octopus/admin/candidates/{id}/data-export
     *
     * KVKK data portability: export all candidate data as JSON.
     */
    public function exportCandidateData(string $id, Request $request, PoolCandidateErasureService $service): JsonResponse
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')->find($id);

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate not found'], 404);
        }

        if ($candidate->is_erased) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate data has already been erased.',
            ], 410);
        }

        $data = $service->exportData($candidate);

        // Audit log for the export action
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'kvkk_data_export',
            'entity_type' => PoolCandidate::class,
            'entity_id' => $candidate->id,
            'metadata' => ['ip_address' => $request->ip()],
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function formatLanguageAssessment(string $candidateId): ?array
    {
        $a = LanguageAssessment::forCandidate($candidateId);
        if (!$a) return null;

        return [
            'declared_level' => $a->declared_level,
            'mcq_score' => $a->mcq_score,
            'mcq_total' => $a->mcq_total,
            'mcq_correct' => $a->mcq_correct,
            'writing_score' => $a->writing_score,
            'interview_score' => $a->interview_score,
            'overall_score' => $a->overall_score,
            'estimated_level' => $a->estimated_level,
            'confidence' => $a->confidence,
            'locked_level' => $a->locked_level,
            'locked_at' => $a->locked_at?->toIso8601String(),
            'signals' => $a->signals,
        ];
    }

    private function formatBehavioralProfile(string $candidateId): ?array
    {
        $profile = BehavioralProfile::where('candidate_id', $candidateId)
            ->where('version', 'v1')
            ->first();

        if (!$profile) return null;

        $fitJson = $profile->fit_json;

        // Compute evidence-based vessel fit
        $vesselFitEvidence = null;
        if (!empty($fitJson) && is_array($fitJson)) {
            $vesselFitEvidence = app(VesselFitEvidenceService::class)
                ->compute($candidateId, $fitJson, (float) ($profile->confidence ?? 0));
        }

        return [
            'dimensions' => $profile->dimensions_json,
            'fit_json' => $fitJson,
            'vessel_fit_evidence' => $vesselFitEvidence,
            'flags' => $profile->flags_json,
            'confidence' => $profile->confidence,
            'status' => $profile->status,
        ];
    }

    private function formatScoringVector(string $candidateId): ?array
    {
        $vector = CandidateScoringVector::where('candidate_id', $candidateId)
            ->where('version', 'v1')
            ->first();

        if (!$vector) return null;

        return [
            'technical' => $vector->technical_score,
            'behavioral' => $vector->behavioral_score,
            'reliability' => $vector->reliability_score,
            'personality' => $vector->personality_score,
            'english_proficiency' => $vector->english_proficiency,
            'english_level' => $vector->english_level,
            'english_weight' => $vector->english_weight,
            'composite_score' => $vector->composite_score,
            'computed_at' => $vector->computed_at?->toIso8601String(),
        ];
    }
}
