<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\CommandClass;
use App\Models\FormInterview;
use App\Models\MaritimeScenario;
use App\Models\PoolCandidate;
use App\Services\Maritime\ScenarioSelector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InterviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FormInterview::where('industry_code', 'maritime')
            ->with(['poolCandidate:id,first_name,last_name,email', 'behavioralProfile:id,status,confidence,fit_json'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('decision')) {
            $query->where('decision', $request->input('decision'));
        }
        if ($request->filled('position_code')) {
            $query->where('position_code', $request->input('position_code'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $interviews = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $interviews->map(fn($i) => [
                'id' => $i->id,
                'position_code' => $i->position_code,
                'status' => $i->status,
                'final_score' => $i->calibrated_score ?? $i->final_score,
                'decision' => $i->decision,
                'command_class_detected' => $i->command_class_detected,
                'interview_phase' => $i->interview_phase,
                'phase' => $i->phase,
                'needs_review' => $i->needs_review,
                'resolver_status' => $i->resolver_status,
                'completed_at' => $i->completed_at?->toIso8601String(),
                'created_at' => $i->created_at->toIso8601String(),
                'behavioral_status' => $i->behavioralProfile?->status,
                'behavioral_fit_top3' => $this->topFit($i->behavioralProfile),
                'candidate' => $i->poolCandidate ? [
                    'id' => $i->poolCandidate->id,
                    'first_name' => $i->poolCandidate->first_name,
                    'last_name' => $i->poolCandidate->last_name,
                    'email' => $i->poolCandidate->email,
                ] : null,
            ]),
            'pagination' => [
                'current_page' => $interviews->currentPage(),
                'per_page' => $interviews->perPage(),
                'total' => $interviews->total(),
                'last_page' => $interviews->lastPage(),
            ],
        ]);
    }

    /**
     * GET /v1/octopus/admin/interviews/{id}
     */
    public function show(string $id): JsonResponse
    {
        $interview = FormInterview::where('industry_code', 'maritime')
            ->with(['poolCandidate', 'answers', 'capabilityScore', 'behavioralProfile'])
            ->find($id);

        if (!$interview) {
            return response()->json(['success' => false, 'message' => 'Interview not found'], 404);
        }

        $cap = $interview->capabilityScore;

        $commandClassInfo = null;
        if ($interview->command_class_detected) {
            $cc = CommandClass::where('code', $interview->command_class_detected)->first();
            if ($cc) {
                $commandClassInfo = [
                    'code' => $cc->code,
                    'name_en' => $cc->name_en,
                    'name_tr' => $cc->name_tr,
                    'weight_vector' => $cc->weight_vector,
                    'risk_profile' => $cc->risk_profile,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $interview->id,
                'type' => $interview->type,
                'position_code' => $interview->position_code,
                'status' => $interview->status,
                'interview_phase' => $interview->interview_phase,
                'final_score' => $interview->calibrated_score ?? $interview->final_score,
                'raw_final_score' => $interview->raw_final_score,
                'decision' => $interview->decision,
                'decision_reason' => $interview->decision_reason,
                'decision_summary_json' => $interview->decision_summary_json,
                'command_class_detected' => $interview->command_class_detected,
                'capability_profile_json' => $interview->capability_profile_json,
                'deployment_packet_json' => $interview->deployment_packet_json,
                'phase' => $interview->phase,
                'needs_review' => $interview->needs_review,
                'resolver_status' => $interview->resolver_status,
                'linked_phase_interview_id' => $interview->linked_phase_interview_id,
                'scenario_set_json' => $interview->scenario_set_json,
                'override_class' => $interview->override_class,
                'override_by_user_id' => $interview->override_by_user_id,
                'completed_at' => $interview->completed_at?->toIso8601String(),
                'phase1_completed_at' => $interview->phase1_completed_at?->toIso8601String(),
                'created_at' => $interview->created_at->toIso8601String(),
                'candidate' => $interview->poolCandidate ? [
                    'id' => $interview->poolCandidate->id,
                    'first_name' => $interview->poolCandidate->first_name,
                    'last_name' => $interview->poolCandidate->last_name,
                    'email' => $interview->poolCandidate->email,
                ] : null,
                'answers' => $interview->answers->map(fn($a) => [
                    'id' => $a->id,
                    'slot' => $a->slot,
                    'question' => $a->question,
                    'answer' => $a->answer,
                    'score' => $a->score,
                    'competency_code' => $a->competency_code,
                    'justification' => $a->justification,
                ]),
                'capability_score' => $cap ? [
                    'id' => $cap->id,
                    'command_class' => $cap->command_class,
                    'crl' => $cap->crl,
                    'crl_label' => $cap->getCrlLabel(),
                    'raw_scores' => $cap->getRawScores(),
                    'adjusted_scores' => $cap->getAdjustedScores(),
                    'axis_scores' => $cap->axis_scores,
                    'deployment_flags' => $cap->deployment_flags,
                    'scoring_version' => $cap->scoring_version,
                    'scored_at' => $cap->scored_at?->toIso8601String(),
                ] : null,
                'command_class_info' => $commandClassInfo,
                'behavioral_snapshot' => $interview->behavioralProfile ? [
                    'id' => $interview->behavioralProfile->id,
                    'status' => $interview->behavioralProfile->status,
                    'confidence' => (float) $interview->behavioralProfile->confidence,
                    'dimensions' => $interview->behavioralProfile->dimensions_json,
                    'fit' => $interview->behavioralProfile->fit_json,
                    'flags' => $interview->behavioralProfile->flags_json,
                    'computed_at' => $interview->behavioralProfile->computed_at?->toIso8601String(),
                ] : null,
            ],
        ]);
    }

    /**
     * Download candidate assessment report PDF.
     * GET /v1/octopus/admin/interviews/{id}/report.pdf
     */
    public function reportPdf(Request $request, string $id)
    {
        $interview = FormInterview::where('industry_code', 'maritime')
            ->with(['answers', 'behavioralProfile'])
            ->find($id);

        if (!$interview) {
            return response()->json(['success' => false, 'message' => 'Interview not found'], 404);
        }

        if (!$interview->isCompleted()) {
            return response()->json(['success' => false, 'message' => 'Report only available for completed interviews'], 400);
        }

        $candidate = $interview->pool_candidate_id
            ? PoolCandidate::find($interview->pool_candidate_id)
            : null;

        if (!$candidate) {
            $meta = $interview->meta ?? [];
            $candidate = (object) [
                'id' => null,
                'first_name' => $meta['candidate_name'] ?? 'Unknown',
                'last_name' => '',
                'email' => $meta['candidate_email'] ?? '',
                'phone' => '',
                'country_code' => '',
                'english_level_self' => $meta['english_level_self'] ?? '',
                'source_channel' => '',
                'status' => $interview->status,
                'source_meta' => [],
            ];
        }

        $generatedAt = now()->format('d.m.Y H:i');
        $user = $request->user();
        if (!$user) {
            $generatedBy = 'Octopus Admin';
        } elseif ($user->is_octopus_admin) {
            $generatedBy = 'Platform admin tarafından oluşturulmuştur';
        } else {
            $generatedBy = ($user->name ?? $user->email) . ' (' . $user->email . ')';
        }

        // Behavioral snapshot for PDF (Octo-admin sees full details)
        $bp = $interview->behavioralProfile;
        $behavioralSnapshot = ($bp && $bp->status === 'final') ? [
            'status' => $bp->status,
            'confidence' => (float) $bp->confidence,
            'dimensions' => $bp->dimensions_json,
            'fit_top3' => $bp->fit_json ? collect($bp->fit_json)
                ->map(fn($v, $k) => ['class' => $k, 'fit' => $v['normalized_fit'] ?? 0, 'risk_flag' => $v['risk_flag'] ?? false, 'friction_flag' => $v['friction_flag'] ?? false])
                ->sortByDesc('fit')->take(3)->values()->toArray() : null,
            'flags' => $bp->flags_json,
            'computed_at' => $bp->computed_at?->toIso8601String(),
        ] : null;

        // Certificate risk data for the report
        $certificateRisks = [];
        if ($candidate->id) {
            $realCandidate = $candidate instanceof PoolCandidate ? $candidate : PoolCandidate::find($candidate->id);
            if ($realCandidate) {
                $certService = app(\App\Services\Maritime\CertificateLifecycleService::class);
                $certificateRisks = $certService->enrichWithRiskLevels($realCandidate->certificates);
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.candidate-report', [
            'candidate' => $candidate,
            'interview' => $interview,
            'answers' => $interview->answers,
            'generatedAt' => $generatedAt,
            'generatedBy' => $generatedBy,
            'behavioralSnapshot' => $behavioralSnapshot,
            'certificateRisks' => $certificateRisks,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);

        $name = $candidate->first_name ?? 'candidate';
        $filename = sprintf(
            'assessment-report-%s-%s.pdf',
            \Illuminate\Support\Str::slug($name),
            now()->format('Ymd-His')
        );

        return $pdf->download($filename);
    }

    /**
     * POST /v1/octopus/admin/interviews/{id}/override-class
     *
     * Admin override of detected command class.
     * Re-selects scenarios for the new class and updates the interview.
     */
    public function overrideClass(Request $request, string $id): JsonResponse
    {
        if (!config('maritime.resolver_v2')) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $interview = FormInterview::where('industry_code', 'maritime')->find($id);

        if (!$interview) {
            return response()->json(['success' => false, 'message' => 'Interview not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'command_class' => ['required', 'string', 'in:' . implode(',', CommandClass::CODES)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $newClass = $request->input('command_class');

        // Verify scenario bank ready for new class
        $selector = app(ScenarioSelector::class);
        if (!$selector->isReady($newClass)) {
            $count = MaritimeScenario::active()->forClass($newClass)->count();
            return response()->json([
                'success' => false,
                'error' => 'scenario_bank_incomplete',
                'message' => "Only {$count}/8 active scenarios for class {$newClass}.",
            ], 422);
        }

        $oldClass = $interview->command_class_detected;

        $interview->update([
            'command_class_detected' => $newClass,
            'override_class' => $newClass,
            'override_by_user_id' => $request->user()?->id,
            'needs_review' => false,
            'resolver_status' => 'overridden',
        ]);

        // If Phase-2 interview exists and hasn't started, update scenario set
        $phase2 = $interview->phase2Interview;
        if ($phase2 && $phase2->status === FormInterview::STATUS_IN_PROGRESS) {
            $scenarios = $selector->select($newClass);
            $phase2->update([
                'command_class_detected' => $newClass,
                'scenario_set_json' => $scenarios->pluck('scenario_code')->toArray(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $interview->id,
                'old_class' => $oldClass,
                'new_class' => $newClass,
                'resolver_status' => 'overridden',
            ],
        ]);
    }

    /**
     * Extract top-3 vessel-type fit from behavioral profile (for list view).
     * Returns array like [['class' => 'TANKER', 'fit' => 82], ...] or null.
     */
    private function topFit(?\App\Models\BehavioralProfile $profile): ?array
    {
        if (!$profile || !$profile->fit_json) {
            return null;
        }

        $fitMap = $profile->fit_json;
        $sorted = collect($fitMap)
            ->map(fn($v, $k) => ['class' => $k, 'fit' => $v['normalized_fit'] ?? 0])
            ->sortByDesc('fit')
            ->take(3)
            ->values()
            ->toArray();

        return $sorted;
    }
}
