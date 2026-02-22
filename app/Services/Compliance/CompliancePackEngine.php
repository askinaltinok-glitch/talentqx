<?php

namespace App\Services\Compliance;

use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Models\TrustEvent;
use Illuminate\Support\Facades\Log;

class CompliancePackEngine
{
    public function __construct(
        private ComplianceScoreCalculator $scoreCalc,
        private ComplianceStatusResolver $statusResolver,
        private ComplianceRecommendationEngine $recommendationEngine,
    ) {}

    public function compute(string $poolCandidateId): ?array
    {
        if (!config('maritime.compliance_v1')) {
            return null;
        }

        try {
            return $this->doCompute($poolCandidateId);
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('[CompliancePack] compute failed', [
                'candidate' => $poolCandidateId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function doCompute(string $poolCandidateId): ?array
    {
        $candidate = PoolCandidate::find($poolCandidateId);
        if (!$candidate) {
            return null;
        }

        $trustProfile = CandidateTrustProfile::where('pool_candidate_id', $poolCandidateId)->first();
        if (!$trustProfile) {
            return null;
        }

        // Prerequisite: at least CRI must exist
        if ($trustProfile->cri_score === null) {
            return null;
        }

        // 1. Calculate scores
        $scoreResult = $this->scoreCalc->calculate($trustProfile, $candidate);
        if (!$scoreResult) {
            return null;
        }

        // 2. Resolve status
        $statusResult = $this->statusResolver->resolve(
            $scoreResult->score,
            $scoreResult->sectionScores,
            $trustProfile,
        );

        // 3. Generate recommendations
        $recommendations = $this->recommendationEngine->generate(
            $scoreResult->score,
            $scoreResult->sectionScores,
            $statusResult->status,
            $trustProfile,
        );

        // Build result
        $result = [
            'score' => $scoreResult->score,
            'status' => $statusResult->status,
            'section_scores' => $scoreResult->sectionScores,
            'available_sections' => $scoreResult->availableSections,
            'flags' => $statusResult->flags,
            'recommendations' => $recommendations,
            'computed_at' => now()->toIso8601String(),
        ];

        // Store in trust profile
        $this->storeResult($trustProfile, $result);

        // Audit event
        TrustEvent::create([
            'pool_candidate_id' => $poolCandidateId,
            'event_type' => 'compliance_pack_computed',
            'payload_json' => [
                'score' => $scoreResult->score,
                'status' => $statusResult->status,
                'available_sections' => $scoreResult->availableSections,
                'flags_count' => count($statusResult->flags),
            ],
        ]);

        return $result;
    }

    private function storeResult(CandidateTrustProfile $trustProfile, array $result): void
    {
        $detailJson = $trustProfile->detail_json ?? [];
        $detailJson['compliance_pack'] = $result;

        $trustProfile->detail_json = $detailJson;
        $trustProfile->compliance_score = $result['score'];
        $trustProfile->compliance_status = $result['status'];
        $trustProfile->compliance_computed_at = now();
        $trustProfile->save();
    }
}
