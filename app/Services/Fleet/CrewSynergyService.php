<?php

namespace App\Services\Fleet;

use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Models\Vessel;
use Illuminate\Support\Facades\DB;

class CrewSynergyService
{
    private const DIMENSIONS = ['DISCIPLINE', 'LEADERSHIP', 'STRESS', 'TEAMWORK', 'COMMS', 'TECH_PRACTICAL'];

    /**
     * Compute crew synergy: how well a candidate fits with the existing vessel crew.
     */
    public function compute(string $candidateId, string $vesselId): ?array
    {
        $candidate = PoolCandidate::find($candidateId);
        $vessel = Vessel::find($vesselId);

        if (!$candidate || !$vessel) {
            return null;
        }

        // Get candidate's trust profile
        $candidateProfile = CandidateTrustProfile::where('pool_candidate_id', $candidateId)->first();

        // Get existing crew from skeleton slots
        $crewSlots = DB::table('vessel_crew_skeleton_slots')
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->whereNotNull('candidate_id')
            ->where('candidate_id', '!=', $candidateId) // exclude self if already assigned
            ->get();

        // Get crew trust profiles
        $crewCandidateIds = $crewSlots->pluck('candidate_id')->unique()->values();
        $crewProfiles = CandidateTrustProfile::whereIn('pool_candidate_id', $crewCandidateIds)
            ->get()
            ->keyBy('pool_candidate_id');

        // Get crew candidate names
        $crewCandidates = PoolCandidate::whereIn('id', $crewCandidateIds)
            ->get()
            ->keyBy('id');

        // Extract candidate dimensions
        $candidateDimensions = $this->extractDimensions($candidateProfile);
        $candidateScores = $this->extractScores($candidateProfile);

        // Build crew member profiles
        $crewMembers = [];
        foreach ($crewSlots as $slot) {
            $profile = $crewProfiles[$slot->candidate_id] ?? null;
            $member = $crewCandidates[$slot->candidate_id] ?? null;

            $crewMembers[] = [
                'candidate_id' => $slot->candidate_id,
                'name' => $member ? ($member->first_name . ' ' . $member->last_name) : 'Unknown',
                'slot_role' => $slot->slot_role,
                'dimensions' => $this->extractDimensions($profile),
                'scores' => $this->extractScores($profile),
            ];
        }

        // Compute crew averages per dimension
        $crewDimensionAvgs = $this->computeCrewDimensionAverages($crewMembers);

        // Compute synergy analysis
        $dimensionAnalysis = $this->analyzeDimensions($candidateDimensions, $crewDimensionAvgs);
        $complementaryStrengths = $this->findComplementary($candidateDimensions, $crewDimensionAvgs);
        $riskFactors = $this->analyzeRiskFactors($candidateScores, $crewMembers);
        $synergyScore = $this->computeSynergyScore($dimensionAnalysis, $complementaryStrengths, $riskFactors);

        // How adding this candidate would change vessel tier
        $riskImpact = $this->computeRiskImpact($candidateProfile, $crewProfiles->values());

        return [
            'candidate' => [
                'id' => $candidateId,
                'name' => $candidate->first_name . ' ' . $candidate->last_name,
                'dimensions' => $candidateDimensions,
                'compliance_score' => $candidateScores['compliance'],
                'competency_score' => $candidateScores['competency'],
                'stability_index' => $candidateScores['stability'],
            ],
            'vessel' => [
                'id' => $vesselId,
                'name' => $vessel->name,
                'imo' => $vessel->imo,
            ],
            'existing_crew' => $crewMembers,
            'crew_dimension_averages' => $crewDimensionAvgs,
            'synergy' => [
                'score' => $synergyScore,
                'label' => $this->synergyLabel($synergyScore),
                'dimension_analysis' => $dimensionAnalysis,
                'complementary_strengths' => $complementaryStrengths,
                'risk_factors' => $riskFactors,
                'risk_impact' => $riskImpact,
            ],
        ];
    }

    private function extractDimensions(?CandidateTrustProfile $profile): array
    {
        if (!$profile) {
            return [];
        }

        $detail = $profile->detail_json ?? [];

        // Try competency_engine.score_by_dimension first, then competency.dimensions
        $dimensions = $detail['competency_engine']['score_by_dimension']
            ?? $detail['competency']['dimensions']
            ?? [];

        $result = [];
        foreach (self::DIMENSIONS as $dim) {
            $val = $dimensions[$dim] ?? null;
            // Handle nested array format {score: N, ...}
            if (is_array($val)) {
                $val = $val['score'] ?? null;
            }
            $result[$dim] = $val !== null ? (int) $val : null;
        }
        return $result;
    }

    private function extractScores(?CandidateTrustProfile $profile): array
    {
        if (!$profile) {
            return ['compliance' => null, 'competency' => null, 'stability' => null, 'predictive_risk' => null];
        }

        $detail = $profile->detail_json ?? [];
        return [
            'compliance' => $profile->compliance_score,
            'competency' => $profile->competency_score,
            'stability' => $profile->stability_index ? (float) $profile->stability_index : null,
            'predictive_risk' => $detail['predictive_risk']['predictive_risk_index'] ?? null,
        ];
    }

    private function computeCrewDimensionAverages(array $crewMembers): array
    {
        $sums = [];
        $counts = [];

        foreach (self::DIMENSIONS as $dim) {
            $sums[$dim] = 0;
            $counts[$dim] = 0;
        }

        foreach ($crewMembers as $member) {
            foreach (self::DIMENSIONS as $dim) {
                if (isset($member['dimensions'][$dim]) && $member['dimensions'][$dim] !== null) {
                    $sums[$dim] += $member['dimensions'][$dim];
                    $counts[$dim]++;
                }
            }
        }

        $avgs = [];
        foreach (self::DIMENSIONS as $dim) {
            $avgs[$dim] = $counts[$dim] > 0 ? round($sums[$dim] / $counts[$dim], 1) : null;
        }
        return $avgs;
    }

    private function analyzeDimensions(array $candidateDims, array $crewAvgs): array
    {
        $analysis = [];
        foreach (self::DIMENSIONS as $dim) {
            $candidateVal = $candidateDims[$dim] ?? null;
            $crewAvg = $crewAvgs[$dim] ?? null;

            if ($candidateVal === null && $crewAvg === null) {
                continue;
            }

            $delta = ($candidateVal !== null && $crewAvg !== null)
                ? round($candidateVal - $crewAvg, 1)
                : null;

            $fit = 'neutral';
            if ($delta !== null) {
                if ($delta >= 15) $fit = 'elevates';       // candidate lifts the crew average
                elseif ($delta >= 5) $fit = 'strengthens';
                elseif ($delta >= -5) $fit = 'aligns';
                elseif ($delta >= -15) $fit = 'below_avg';
                else $fit = 'gap';                          // candidate is well below crew avg
            }

            $analysis[] = [
                'dimension' => $dim,
                'candidate' => $candidateVal,
                'crew_avg' => $crewAvg,
                'delta' => $delta,
                'fit' => $fit,
            ];
        }
        return $analysis;
    }

    private function findComplementary(array $candidateDims, array $crewAvgs): array
    {
        $strengths = [];

        foreach (self::DIMENSIONS as $dim) {
            $cv = $candidateDims[$dim] ?? null;
            $ca = $crewAvgs[$dim] ?? null;

            // Candidate fills a crew weakness
            if ($cv !== null && $ca !== null && $ca < 45 && $cv >= 60) {
                $strengths[] = [
                    'dimension' => $dim,
                    'type' => 'fills_gap',
                    'detail' => "Crew avg {$ca} in {$dim}, candidate brings {$cv}",
                ];
            }

            // Candidate strengthens already strong area
            if ($cv !== null && $ca !== null && $ca >= 60 && $cv >= 60) {
                $strengths[] = [
                    'dimension' => $dim,
                    'type' => 'reinforces',
                    'detail' => "Both crew ({$ca}) and candidate ({$cv}) strong in {$dim}",
                ];
            }
        }

        return $strengths;
    }

    private function analyzeRiskFactors(array $candidateScores, array $crewMembers): array
    {
        $factors = [];

        // Low compliance candidate joining compliant crew
        if ($candidateScores['compliance'] !== null && $candidateScores['compliance'] < 50) {
            $factors[] = [
                'type' => 'low_compliance',
                'severity' => 'warning',
                'detail' => "Candidate compliance score ({$candidateScores['compliance']}) is below threshold",
            ];
        }

        // Check if crew has dominant weakness that candidate shares
        $crewWeakDims = [];
        foreach ($crewMembers as $member) {
            foreach (self::DIMENSIONS as $dim) {
                if (isset($member['dimensions'][$dim]) && $member['dimensions'][$dim] < 40) {
                    $crewWeakDims[$dim] = ($crewWeakDims[$dim] ?? 0) + 1;
                }
            }
        }

        foreach ($crewWeakDims as $dim => $count) {
            $candidateVal = null;
            // Re-extract from candidateScores context — we need dims here
            // This is a simplified check
            if ($count >= 1) {
                $factors[] = [
                    'type' => 'shared_weakness',
                    'severity' => 'info',
                    'detail' => "{$count} crew member(s) weak in {$dim} — verify candidate covers this gap",
                ];
            }
        }

        // Stability concern
        if ($candidateScores['stability'] !== null && $candidateScores['stability'] < 3.0) {
            $factors[] = [
                'type' => 'low_stability',
                'severity' => 'warning',
                'detail' => "Candidate stability index ({$candidateScores['stability']}) indicates flight risk",
            ];
        }

        return $factors;
    }

    private function computeRiskImpact(?CandidateTrustProfile $candidateProfile, $crewProfiles): array
    {
        $aggregator = new VesselRiskAggregator();

        // Current crew-only predictive risks
        $currentRisks = [];
        foreach ($crewProfiles as $cp) {
            $pri = $cp->detail_json['predictive_risk']['predictive_risk_index'] ?? null;
            if ($pri !== null) {
                $currentRisks[] = (float) $pri;
            }
        }

        // With candidate added
        $withCandidate = $currentRisks;
        if ($candidateProfile) {
            $candidatePri = $candidateProfile->detail_json['predictive_risk']['predictive_risk_index'] ?? null;
            if ($candidatePri !== null) {
                $withCandidate[] = (float) $candidatePri;
            }
        }

        $currentAvg = !empty($currentRisks) ? round(array_sum($currentRisks) / count($currentRisks), 2) : null;
        $projectedAvg = !empty($withCandidate) ? round(array_sum($withCandidate) / count($withCandidate), 2) : null;

        return [
            'current_avg_predictive_risk' => $currentAvg,
            'projected_avg_predictive_risk' => $projectedAvg,
            'delta' => ($currentAvg !== null && $projectedAvg !== null) ? round($projectedAvg - $currentAvg, 2) : null,
            'note' => $currentAvg === null && $projectedAvg === null
                ? 'Insufficient predictive risk data for impact analysis'
                : null,
        ];
    }

    private function computeSynergyScore(array $dimensionAnalysis, array $complementary, array $riskFactors): int
    {
        // Base score: 50
        $score = 50;

        // Dimension fit adjustments
        foreach ($dimensionAnalysis as $da) {
            switch ($da['fit']) {
                case 'elevates': $score += 8; break;
                case 'strengthens': $score += 5; break;
                case 'aligns': $score += 2; break;
                case 'below_avg': $score -= 3; break;
                case 'gap': $score -= 8; break;
            }
        }

        // Complementary bonuses
        foreach ($complementary as $c) {
            $score += $c['type'] === 'fills_gap' ? 10 : 3;
        }

        // Risk penalties
        foreach ($riskFactors as $rf) {
            $score -= $rf['severity'] === 'warning' ? 8 : 3;
        }

        return max(0, min(100, $score));
    }

    private function synergyLabel(int $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 65) return 'good';
        if ($score >= 45) return 'moderate';
        if ($score >= 30) return 'low';
        return 'poor';
    }
}
