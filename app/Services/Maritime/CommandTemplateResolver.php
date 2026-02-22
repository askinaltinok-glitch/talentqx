<?php

namespace App\Services\Maritime;

use App\Models\CandidateCommandProfile;
use App\Models\CommandClass;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the command class for Phase-2 scenario selection.
 *
 * Handles:
 * - Contradiction detection (DWT mismatch + missing STCW)
 * - Low confidence flagging (< threshold → needs_review)
 * - Multi-class blending decision (delta < threshold)
 */
class CommandTemplateResolver
{
    /**
     * Resolve the command class and determine blending strategy.
     *
     * @return array{
     *   command_class: string,
     *   confidence: float,
     *   needs_review: bool,
     *   blend: bool,
     *   secondary_class: ?string,
     *   flags: array,
     *   contradictions: array
     * }
     *
     * @throws \InvalidArgumentException on profile_inconsistent
     */
    public function resolve(CandidateCommandProfile $profile): array
    {
        $flags = [];
        $contradictions = $this->detectContradictions($profile);

        if (!empty($contradictions)) {
            $flags[] = [
                'type' => 'PROFILE_INCONSISTENT',
                'details' => $contradictions,
            ];

            throw new \InvalidArgumentException(
                json_encode([
                    'error' => 'profile_inconsistent',
                    'message' => 'Candidate profile contains contradictions.',
                    'contradictions' => $contradictions,
                ])
            );
        }

        $commandClass = $profile->derived_command_class;
        $confidence = (float) ($profile->confidence_score ?? 0);
        $multiClassFlags = $profile->multi_class_flags ?? [];

        $lowConfidenceThreshold = (float) config('maritime.thresholds.low_confidence_threshold', 0.65);
        $multiClassDelta = (float) config('maritime.thresholds.multi_class_delta_threshold', 0.08);

        // Normalize confidence to 0-1 scale (detector outputs 0-100)
        $confidenceNormalized = $confidence > 1 ? $confidence / 100 : $confidence;

        $needsReview = false;
        $blend = false;
        $secondaryClass = null;

        // Low confidence check
        if ($confidenceNormalized < $lowConfidenceThreshold) {
            $needsReview = true;
            $flags[] = [
                'type' => 'LOW_CONFIDENCE',
                'confidence' => $confidenceNormalized,
                'threshold' => $lowConfidenceThreshold,
            ];
        }

        // Multi-class blend check
        foreach ($multiClassFlags as $flag) {
            if (($flag['type'] ?? '') === 'MULTI_CLASS') {
                $gap = ($flag['gap'] ?? 100) / 100; // Normalize from percentage
                if ($gap < $multiClassDelta) {
                    $blend = true;
                    $secondaryClass = $flag['secondary'] ?? null;
                    $flags[] = [
                        'type' => 'MULTI_CLASS_BLEND',
                        'primary' => $commandClass,
                        'secondary' => $secondaryClass,
                        'gap' => $gap,
                        'threshold' => $multiClassDelta,
                    ];
                }
            }
        }

        Log::info('CommandTemplateResolver: resolved', [
            'candidate_id' => $profile->candidate_id,
            'command_class' => $commandClass,
            'confidence' => $confidenceNormalized,
            'needs_review' => $needsReview,
            'blend' => $blend,
            'secondary_class' => $secondaryClass,
            'flags_count' => count($flags),
        ]);

        return [
            'command_class' => $commandClass,
            'confidence' => $confidenceNormalized,
            'needs_review' => $needsReview,
            'blend' => $blend,
            'secondary_class' => $secondaryClass,
            'flags' => $flags,
            'contradictions' => [],
        ];
    }

    /**
     * Detect contradictions in candidate profile.
     *
     * Checks:
     * 1. DWT mismatch: vessel experience doesn't match DWT history
     * 2. Missing STCW: critical certifications expected but not declared
     */
    private function detectContradictions(CandidateCommandProfile $profile): array
    {
        $contradictions = [];

        // DWT mismatch: candidate claims large vessels but DWT doesn't match
        $vesselTypes = $profile->getVesselTypes();
        $dwtMax = $profile->getDwtMax();

        $largeVesselTypes = [
            'VLCC', 'container_ulcs', 'container_post_panamax', 'LNG_carrier',
            'crude_tanker', 'FPSO',
        ];

        $hasLargeVessel = !empty(array_intersect($vesselTypes, $largeVesselTypes));

        if ($hasLargeVessel && $dwtMax !== null && $dwtMax < 10000) {
            $contradictions[] = [
                'type' => 'DWT_MISMATCH',
                'detail' => "Claims large vessel types but max DWT is {$dwtMax}",
                'vessel_types' => array_intersect($vesselTypes, $largeVesselTypes),
                'dwt_max' => $dwtMax,
            ];
        }

        // Missing STCW: if incident history contains critical incidents
        // but no safety certifications mentioned
        $incidentHistory = $profile->incident_history ?? [];
        $rawAnswers = $profile->raw_identity_answers ?? [];
        $certText = mb_strtolower($rawAnswers['CERTIFICATION_STATUS'] ?? '');

        if (
            ($incidentHistory['severity_max'] ?? null) === 'critical'
            && !empty($incidentHistory['types'])
            && !str_contains($certText, 'stcw')
            && !str_contains($certText, 'certificate')
            && !str_contains($certText, 'sertifika')
        ) {
            $contradictions[] = [
                'type' => 'MISSING_STCW',
                'detail' => 'Critical incident history without STCW certification mention',
                'incident_types' => $incidentHistory['types'],
            ];
        }

        return $contradictions;
    }
}
