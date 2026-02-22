<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\CandidateCredential;
use App\Models\CandidateTimelineEvent;
use App\Models\LanguageAssessment;
use App\Models\PoolCandidate;
use App\Helpers\RadarChartGenerator;
use App\Models\CandidateDecisionOverride;
use App\Services\ExecutiveSummary\ExecutiveSummaryBuilder;
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
                'english_level_self' => $c->english_level_self,
                'source_channel' => $c->source_channel,
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
                'preferred_language' => $candidate->preferred_language,
                'english_level_self' => $candidate->english_level_self,
                'source_channel' => $candidate->source_channel,
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

        $override = CandidateDecisionOverride::create([
            'candidate_id' => $candidate->id,
            'decision' => $validated['decision'],
            'reason' => $validated['reason'],
            'created_by' => $request->user()?->id,
            'expires_at' => $validated['expires_at'] ?? null,
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
     * GET /v1/octopus/admin/candidates/{id}/decision-packet.pdf
     *
     * Download decision packet PDF for shipowners.
     */
    public function decisionPacketPdf(string $id)
    {
        $candidate = PoolCandidate::where('primary_industry', 'maritime')
            ->with(['trustProfile', 'contracts.latestAisVerification', 'credentials'])
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

        $pdf = Pdf::loadView('pdf.decision-packet', [
            'candidate' => $candidate,
            'trustProfile' => $tp,
            'execSummary' => $execSummary,
            'compliancePack' => $compliancePack,
            'rankStcw' => $detail['rank_stcw'] ?? null,
            'stabilityRisk' => $detail['stability_risk'] ?? null,
            'radarChart' => $radarChart,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $name = str_replace(' ', '_', $candidate->first_name . '_' . $candidate->last_name);
        $filename = "Decision_Packet_{$name}_" . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
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
}
