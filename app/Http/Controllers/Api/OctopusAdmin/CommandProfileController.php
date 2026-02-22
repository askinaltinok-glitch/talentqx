<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\CandidateCommandProfile;
use App\Models\CommandClass;
use App\Models\CommandDetectionLog;
use App\Services\Fleet\CommandProfileService;
use Illuminate\Http\JsonResponse;

class CommandProfileController extends Controller
{
    /**
     * GET /v1/octopus/admin/command-profiles/{candidateId}
     *
     * Admin debug endpoint for command class detection inspection.
     */
    public function show(string $candidateId, CommandProfileService $service): JsonResponse
    {
        $profile = CandidateCommandProfile::latestForCandidate($candidateId);

        if (!$profile) {
            // Auto-generate derived profile from source_meta + contracts
            try {
                $profile = $service->generateDerived($candidateId);
            } catch (\Throwable) {
                return response()->json([
                    'success' => false,
                    'message' => 'No command profile found and unable to generate derived profile.',
                ], 404);
            }
        }

        // Get detection logs for this candidate
        $logs = CommandDetectionLog::where('candidate_id', $candidateId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => [
                    'id' => $profile->id,
                    'candidate_id' => $profile->candidate_id,
                    'raw_identity_answers' => $profile->raw_identity_answers,
                    'vessel_experience' => $profile->vessel_experience,
                    'dwt_history' => $profile->dwt_history,
                    'automation_exposure' => $profile->automation_exposure,
                    'cargo_history' => $profile->cargo_history,
                    'trading_areas' => $profile->trading_areas,
                    'crew_scale_history' => $profile->crew_scale_history,
                    'incident_history' => $profile->incident_history,
                    'derived_command_class' => $profile->derived_command_class,
                    'confidence_score' => $profile->confidence_score,
                    'identity_confidence_score' => $profile->identity_confidence_score,
                    'multi_class_flags' => $profile->multi_class_flags,
                    'source' => $profile->source ?? 'detection',
                    'completeness_pct' => $profile->completeness_pct ?? 0,
                    'is_partial' => ($profile->source ?? 'detection') === 'derived',
                    'missing_fields' => $this->getMissingFields($profile),
                    'created_at' => $profile->created_at?->toIso8601String(),
                    'generated_at' => $profile->generated_at?->toIso8601String(),
                ],
                'command_class' => $profile->derived_command_class
                    ? CommandClass::where('code', $profile->derived_command_class)->first()
                    : null,
                'detection_logs' => $logs->map(fn($log) => [
                    'id' => $log->id,
                    'detected_class' => $log->detected_class,
                    'confidence' => $log->confidence,
                    'profile_snapshot' => $log->profile_snapshot,
                    'scoring_output' => $log->scoring_output,
                    'created_at' => $log->created_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * GET /v1/octopus/admin/command-classes
     *
     * List all command classes.
     */
    /**
     * Identify missing profile fields.
     */
    private function getMissingFields(CandidateCommandProfile $profile): array
    {
        $missing = [];
        if (empty($profile->vessel_experience)) $missing[] = 'vessel_experience';
        if (empty($profile->trading_areas)) $missing[] = 'trading_areas';
        if (empty($profile->cargo_history)) $missing[] = 'cargo_history';
        if (empty($profile->crew_scale_history) || $profile->getCrewMin() === null) $missing[] = 'crew_scale';
        if (empty($profile->dwt_history) || $profile->getDwtMin() === null) $missing[] = 'dwt_range';
        return $missing;
    }

    public function classes(): JsonResponse
    {
        $classes = CommandClass::allActive();

        return response()->json([
            'success' => true,
            'data' => $classes->map(fn(CommandClass $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name_en' => $c->name_en,
                'name_tr' => $c->name_tr,
                'vessel_types' => $c->vessel_types,
                'dwt_min' => $c->dwt_min,
                'dwt_max' => $c->dwt_max,
                'crew_min' => $c->crew_min,
                'crew_max' => $c->crew_max,
                'risk_profile' => $c->risk_profile,
                'weight_vector' => $c->weight_vector,
            ]),
        ]);
    }
}
