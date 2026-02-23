<?php

namespace App\Services\Fleet;

use App\Models\BehavioralProfile;
use App\Models\CandidateTrustProfile;
use App\Models\DecisionRoomEntry;
use App\Models\FleetVessel;
use App\Models\PoolCandidate;
use App\Models\Vessel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DecisionRoomService
{
    public function __construct(
        private CrewSynergyEngineV2 $synergyEngine,
        private PortalCrewPlanningService $crewPlanning,
    ) {}

    /**
     * Resolve FleetVessel → system Vessel via IMO bridge.
     */
    public function resolveSystemVessel(FleetVessel $fv): ?Vessel
    {
        if (!$fv->imo) {
            return null;
        }

        return Vessel::where('imo', $fv->imo)->first();
    }

    /**
     * Get vessel snapshot: info + gaps + captain style.
     */
    public function getSnapshot(FleetVessel $vessel): array
    {
        $systemVessel = $this->resolveSystemVessel($vessel);
        $analysis = $this->crewPlanning->analyseVessel($vessel);

        $captainStyle = null;
        $captainName = null;

        if ($systemVessel && $this->synergyEngine->isEnabled()) {
            try {
                $crewContext = Cache::remember(
                    "synergy_v2:vessel_crew:{$systemVessel->id}",
                    300,
                    fn () => $this->getCrewContextDirect($systemVessel->id)
                );
                if ($crewContext['captain']) {
                    $captainName = $crewContext['captain']['name'];
                    $captainBehavioral = $crewContext['captain']['behavioral'];
                    if ($captainBehavioral) {
                        $captainStyle = $this->inferCaptainStyleFromBehavioral($captainBehavioral);
                    }
                }
            } catch (\Throwable $e) {
                Log::channel('single')->warning('DecisionRoom::getSnapshot captain style failed', [
                    'vessel_id' => $vessel->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'vessel' => $vessel->toArray(),
            'system_vessel_id' => $systemVessel?->id,
            'gaps' => $analysis['gaps'],
            'summary' => $analysis['summary'],
            'captain_style' => $captainStyle,
            'captain_name' => $captainName,
            'v2_enabled' => $this->synergyEngine->isEnabled(),
        ];
    }

    /**
     * Get shortlist for a rank via CrewSynergyEngineV2 (or fallback to PortalCrewPlanningService).
     */
    public function getShortlist(FleetVessel $vessel, string $rankCode, int $limit = 10): array
    {
        $systemVessel = $this->resolveSystemVessel($vessel);

        // V2 path: use synergy engine
        if ($systemVessel && $this->synergyEngine->isEnabled()) {
            try {
                $results = $this->synergyEngine->shortlistCandidates($systemVessel->id, $rankCode, $limit);
                if (!empty($results)) {
                    // Add nationality from pool_candidates
                    $candidateIds = collect($results)->pluck('candidate_id');
                    $nationalities = PoolCandidate::whereIn('id', $candidateIds)
                        ->pluck('nationality', 'id');

                    return array_map(function ($r) use ($nationalities) {
                        $r['nationality'] = $nationalities[$r['candidate_id']] ?? null;
                        return $r;
                    }, $results);
                }
            } catch (\Throwable $e) {
                Log::channel('single')->warning('DecisionRoom::getShortlist V2 failed, falling back', [
                    'vessel_id' => $vessel->id,
                    'rank' => $rankCode,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: use PortalCrewPlanningService with adapter mapping
        $recommendations = $this->crewPlanning->recommendForGap(
            vessel: $vessel,
            rankCode: $rankCode,
            limit: $limit,
            urgencyBucket: 'immediate',
        );

        return array_map(function ($rec) {
            return [
                'candidate_id' => $rec['candidate_id'],
                'name' => $rec['name'],
                'rank' => $rec['rank'] ?? null,
                'nationality' => $rec['nationality'] ?? null,
                'compatibility_score' => $rec['total_score'],
                'label' => $this->scoreLabelFromTotal($rec['total_score']),
                'captain_fit' => 50, // neutral — v1 doesn't compute pillars
                'team_balance_fit' => 50,
                'vessel_fit' => 50,
                'operational_risk' => 50,
                'evidence' => [],
                'availability_status' => $rec['availability_status'] ?? 'unknown',
                'contract_end_estimate' => $rec['contract_end_estimate'] ?? null,
                'days_to_available' => $rec['days_to_available'] ?? null,
            ];
        }, $recommendations);
    }

    /**
     * Get full compatibility for a specific candidate on a vessel.
     */
    public function getCompatibility(FleetVessel $vessel, string $candidateId): ?array
    {
        $systemVessel = $this->resolveSystemVessel($vessel);

        if (!$systemVessel || !$this->synergyEngine->isEnabled()) {
            return null;
        }

        return $this->synergyEngine->computeCompatibility($candidateId, $systemVessel->id);
    }

    /**
     * Simulate: given a set of candidate IDs being added to crew, recalculate team metrics.
     */
    public function simulate(FleetVessel $vessel, array $candidateIds): array
    {
        $systemVessel = $this->resolveSystemVessel($vessel);

        // Load current crew context
        $currentCrewMembers = [];
        if ($systemVessel && $this->synergyEngine->isEnabled()) {
            try {
                $crewContext = Cache::remember(
                    "synergy_v2:vessel_crew:{$systemVessel->id}",
                    300,
                    fn () => $this->getCrewContextDirect($systemVessel->id)
                );
                $currentCrewMembers = $crewContext['crew_members'] ?? [];
            } catch (\Throwable $e) {
                // Continue with empty crew
            }
        }

        // Load simulated candidates' profiles (batch, 2 queries)
        $candidates = PoolCandidate::whereIn('id', $candidateIds)->get()->keyBy('id');
        $behavioralProfiles = BehavioralProfile::whereIn('candidate_id', $candidateIds)
            ->where('status', BehavioralProfile::STATUS_FINAL)
            ->get()
            ->groupBy('candidate_id')
            ->map(fn ($g) => $g->sortByDesc('computed_at')->first());

        $trustProfiles = CandidateTrustProfile::whereIn('pool_candidate_id', $candidateIds)
            ->get()
            ->keyBy('pool_candidate_id');

        // Current crew metrics
        $currentMetrics = $this->computeTeamMetrics($currentCrewMembers);

        // Build combined crew: current + simulated candidates
        $simulatedMembers = [];
        foreach ($candidateIds as $cId) {
            $cand = $candidates[$cId] ?? null;
            $bProfile = $behavioralProfiles[$cId] ?? null;
            $tProfile = $trustProfiles[$cId] ?? null;

            $simulatedMembers[] = [
                'candidate_id' => $cId,
                'name' => $cand ? ($cand->first_name . ' ' . $cand->last_name) : 'Unknown',
                'slot_role' => $cand?->rank ?? 'unknown',
                'behavioral' => $bProfile,
                'competency_dims' => $this->extractCompetencyDimensions($tProfile),
            ];
        }

        $combinedCrew = array_merge($currentCrewMembers, $simulatedMembers);
        $combinedMetrics = $this->computeTeamMetrics($combinedCrew);

        return [
            'team_balance_index' => $combinedMetrics['team_balance_index'],
            'personality_distribution' => $combinedMetrics['personality_distribution'],
            'conflict_probability' => $combinedMetrics['conflict_probability'],
            'stability_projection' => $combinedMetrics['stability_projection'],
            'dimension_averages' => $combinedMetrics['dimension_averages'],
            'delta_from_current' => [
                'team_balance_index' => round($combinedMetrics['team_balance_index'] - $currentMetrics['team_balance_index'], 1),
                'conflict_probability' => round($combinedMetrics['conflict_probability'] - $currentMetrics['conflict_probability'], 1),
                'stability_projection' => round($combinedMetrics['stability_projection'] - $currentMetrics['stability_projection'], 2),
            ],
        ];
    }

    /**
     * Log a decision action.
     */
    public function logDecision(FleetVessel $vessel, string $userId, array $data): DecisionRoomEntry
    {
        $systemVessel = $this->resolveSystemVessel($vessel);

        $candidate = PoolCandidate::find($data['candidate_id']);
        $candidateName = $candidate
            ? ($candidate->first_name . ' ' . $candidate->last_name)
            : ($data['candidate_name'] ?? 'Unknown');

        // Snapshot current compatibility if available
        $compatibilitySnapshot = null;
        $riskSnapshot = null;
        if ($systemVessel && $this->synergyEngine->isEnabled()) {
            $compat = $this->synergyEngine->computeCompatibility($data['candidate_id'], $systemVessel->id);
            if ($compat) {
                $compatibilitySnapshot = [
                    'score' => $compat['compatibility_score'],
                    'label' => $compat['label'],
                    'pillars' => [
                        'captain_fit' => $compat['pillars']['captain_fit']['score'] ?? null,
                        'team_balance' => $compat['pillars']['team_balance']['score'] ?? null,
                        'vessel_fit' => $compat['pillars']['vessel_fit']['score'] ?? null,
                        'operational_risk' => $compat['pillars']['operational_risk']['score'] ?? null,
                    ],
                ];
                // Extract risk factors from operational_risk pillar
                $riskPillar = $compat['pillars']['operational_risk'] ?? [];
                if (!empty($riskPillar['risk_factors'])) {
                    $riskSnapshot = array_map(function ($rf) use ($riskPillar) {
                        return [
                            'factor' => $rf,
                            'severity' => $riskPillar['risk_level'] ?? 'unknown',
                            'detail' => $rf,
                        ];
                    }, $riskPillar['risk_factors']);
                }
            }
        }

        return DecisionRoomEntry::create([
            'fleet_vessel_id' => $vessel->id,
            'vessel_id' => $systemVessel?->id,
            'company_id' => $vessel->company_id,
            'user_id' => $userId,
            'rank_code' => $data['rank_code'],
            'action' => $data['action'],
            'candidate_id' => $data['candidate_id'],
            'candidate_name' => $candidateName,
            'compatibility_snapshot' => $compatibilitySnapshot,
            'risk_snapshot' => $riskSnapshot,
            'simulation_snapshot' => $data['simulation_snapshot'] ?? null,
            'reason' => $data['reason'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Get decision history for a vessel+rank.
     */
    public function getHistory(FleetVessel $vessel, ?string $rankCode = null): array
    {
        $query = DecisionRoomEntry::withoutTenantScope()
            ->where('fleet_vessel_id', $vessel->id)
            ->where('company_id', $vessel->company_id)
            ->orderByDesc('created_at');

        if ($rankCode) {
            $query->where('rank_code', $rankCode);
        }

        $entries = $query->limit(50)->get();

        return $entries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'rank_code' => $entry->rank_code,
                'action' => $entry->action,
                'candidate_id' => $entry->candidate_id,
                'candidate_name' => $entry->candidate_name,
                'compatibility_snapshot' => $entry->compatibility_snapshot,
                'risk_snapshot' => $entry->risk_snapshot,
                'reason' => $entry->reason,
                'created_at' => $entry->created_at->toIso8601String(),
                'user_name' => $entry->user?->first_name . ' ' . $entry->user?->last_name,
            ];
        })->toArray();
    }

    // ─── Private Helpers ──────────────────────────────────────────────────

    /**
     * Compute team metrics from crew member arrays.
     */
    private function computeTeamMetrics(array $crewMembers): array
    {
        $dimensions = ['DISCIPLINE', 'LEADERSHIP', 'STRESS', 'TEAMWORK', 'COMMS', 'TECH_PRACTICAL'];
        $personalityBuckets = ['authoritative' => 0, 'collaborative' => 0, 'adaptive' => 0, 'balanced' => 0];
        $dimensionSums = array_fill_keys($dimensions, 0);
        $dimensionCounts = array_fill_keys($dimensions, 0);
        $stabilityValues = [];
        $conflictRiskValues = [];
        $count = count($crewMembers);

        if ($count === 0) {
            return [
                'team_balance_index' => 50,
                'personality_distribution' => $personalityBuckets,
                'conflict_probability' => 0,
                'stability_projection' => 5.0,
                'dimension_averages' => array_fill_keys($dimensions, 50),
            ];
        }

        foreach ($crewMembers as $member) {
            // Competency dimensions for balance
            $dims = $member['competency_dims'] ?? [];
            foreach ($dimensions as $dim) {
                if (isset($dims[$dim])) {
                    $dimensionSums[$dim] += $dims[$dim];
                    $dimensionCounts[$dim]++;
                }
            }

            // Personality style classification
            $behavioral = $member['behavioral'] ?? null;
            if ($behavioral) {
                $style = $this->inferCaptainStyleFromBehavioral($behavioral);
                if (isset($personalityBuckets[$style])) {
                    $personalityBuckets[$style]++;
                }

                // Conflict risk from behavioral dimensions
                $dimJson = is_array($behavioral) ? ($behavioral['dimensions_json'] ?? [])
                    : ($behavioral->dimensions_json ?? []);
                $stressScore = $dimJson['STRESS']['score'] ?? null;
                $teamworkScore = $dimJson['TEAMWORK']['score'] ?? null;

                if ($stressScore !== null && $teamworkScore !== null) {
                    // High stress sensitivity + low teamwork → conflict risk
                    $conflictRisk = max(0, (100 - $stressScore) * 0.4 + (100 - $teamworkScore) * 0.6);
                    $conflictRiskValues[] = $conflictRisk;
                }
            }

            // Stability from trust profile
            $trustDims = $dims;
            $stabilityIndex = $trustDims['stability_index'] ?? null;
            if ($stabilityIndex !== null) {
                $stabilityValues[] = $stabilityIndex;
            }
        }

        // Team Balance Index: inversely proportional to std dev across dimensions
        $dimensionAverages = [];
        $allValues = [];
        foreach ($dimensions as $dim) {
            $avg = $dimensionCounts[$dim] > 0 ? round($dimensionSums[$dim] / $dimensionCounts[$dim], 1) : 50;
            $dimensionAverages[$dim] = $avg;
            $allValues[] = $avg;
        }

        $stdDev = $this->standardDeviation($allValues);
        // Map std dev to 0-100 score: low std dev = high balance
        // Ideal range: 8-25. Below 8 → too homogeneous. Above 25 → imbalanced.
        $idealMin = 8;
        $idealMax = 25;
        if ($stdDev <= $idealMin) {
            $teamBalanceIndex = (int) round(70 + ($idealMin - $stdDev) * 2); // slight penalty for too homogeneous
        } elseif ($stdDev <= $idealMax) {
            $teamBalanceIndex = (int) round(80 + (($idealMax - $stdDev) / ($idealMax - $idealMin)) * 20);
        } else {
            $teamBalanceIndex = (int) round(max(10, 70 - ($stdDev - $idealMax) * 2));
        }
        $teamBalanceIndex = max(0, min(100, $teamBalanceIndex));

        // Conflict probability: percentage of crew with conflict risk > 60
        $conflictProb = 0;
        if (!empty($conflictRiskValues)) {
            $highConflict = count(array_filter($conflictRiskValues, fn ($v) => $v > 60));
            $conflictProb = round(($highConflict / count($conflictRiskValues)) * 100, 1);
        }

        // Stability projection: average stability index (0-10)
        $stabilityProjection = !empty($stabilityValues)
            ? round(array_sum($stabilityValues) / count($stabilityValues), 2)
            : 5.0;

        return [
            'team_balance_index' => $teamBalanceIndex,
            'personality_distribution' => $personalityBuckets,
            'conflict_probability' => $conflictProb,
            'stability_projection' => $stabilityProjection,
            'dimension_averages' => $dimensionAverages,
        ];
    }

    /**
     * Infer captain style from a BehavioralProfile (or array).
     */
    private function inferCaptainStyleFromBehavioral($behavioral): string
    {
        $dims = is_array($behavioral) ? ($behavioral['dimensions_json'] ?? [])
            : ($behavioral->dimensions_json ?? []);

        $leadership = $dims['LEADERSHIP']['score'] ?? 50;
        $teamwork = $dims['TEAMWORK']['score'] ?? 50;
        $discipline = $dims['DISCIPLINE']['score'] ?? 50;
        $stress = $dims['STRESS']['score'] ?? 50;

        // Style thresholds (same as CrewSynergyEngineV2)
        $thresholds = config('maritime.synergy_v2.captain_style_thresholds', [
            'authoritative' => 70,
            'collaborative' => 65,
            'adaptive' => 60,
        ]);

        if ($leadership >= $thresholds['authoritative'] && $discipline >= $thresholds['authoritative']) {
            return 'authoritative';
        }
        if ($teamwork >= $thresholds['collaborative'] && $leadership >= 50) {
            return 'collaborative';
        }
        if ($stress >= $thresholds['adaptive'] && $teamwork >= 50) {
            return 'adaptive';
        }

        return 'balanced';
    }

    /**
     * Extract competency dimensions from a CandidateTrustProfile.
     */
    private function extractCompetencyDimensions(?CandidateTrustProfile $profile): array
    {
        if (!$profile) {
            return [];
        }

        $data = $profile->profile_data ?? [];
        $dims = [];

        foreach (['DISCIPLINE', 'LEADERSHIP', 'STRESS', 'TEAMWORK', 'COMMS', 'TECH_PRACTICAL'] as $dim) {
            if (isset($data[$dim])) {
                $dims[$dim] = is_numeric($data[$dim]) ? (float) $data[$dim] : ($data[$dim]['score'] ?? 50);
            }
        }

        // Stability index
        if (isset($data['stability_index'])) {
            $dims['stability_index'] = (float) $data['stability_index'];
        }

        return $dims;
    }

    /**
     * Fetch vessel crew context directly (mirrors CrewSynergyEngineV2::getVesselCrewContext).
     */
    private function getCrewContextDirect(string $vesselId): array
    {
        $slots = \Illuminate\Support\Facades\DB::table('vessel_crew_skeleton_slots')
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->whereNotNull('candidate_id')
            ->get();

        if ($slots->isEmpty()) {
            return ['captain' => null, 'crew_members' => [], 'crew_count' => 0];
        }

        $candidateIds = $slots->pluck('candidate_id')->unique();
        $behavioralProfiles = BehavioralProfile::whereIn('candidate_id', $candidateIds)
            ->where('status', BehavioralProfile::STATUS_FINAL)
            ->get()
            ->groupBy('candidate_id')
            ->map(fn ($g) => $g->sortByDesc('computed_at')->first());

        $trustProfiles = CandidateTrustProfile::whereIn('pool_candidate_id', $candidateIds)
            ->get()
            ->keyBy('pool_candidate_id');

        $candidates = PoolCandidate::whereIn('id', $candidateIds)
            ->get()
            ->keyBy('id');

        $captain = null;
        $crewMembers = [];

        foreach ($slots as $slot) {
            $cand = $candidates[$slot->candidate_id] ?? null;
            $bProfile = $behavioralProfiles[$slot->candidate_id] ?? null;
            $tProfile = $trustProfiles[$slot->candidate_id] ?? null;

            $member = [
                'candidate_id' => $slot->candidate_id,
                'name' => $cand ? ($cand->first_name . ' ' . $cand->last_name) : 'Unknown',
                'slot_role' => $slot->slot_role,
                'behavioral' => $bProfile,
                'competency_dims' => $this->extractCompetencyDimensions($tProfile),
            ];

            if (strtoupper($slot->slot_role) === 'MASTER') {
                $captain = $member;
            }
            $crewMembers[] = $member;
        }

        return ['captain' => $captain, 'crew_members' => $crewMembers, 'crew_count' => count($crewMembers)];
    }

    /**
     * Map a total_score to a compatibility label (v1 adapter).
     */
    private function scoreLabelFromTotal(int $score): string
    {
        if ($score >= 70) return 'strong_match';
        if ($score >= 55) return 'good_match';
        if ($score >= 40) return 'moderate_match';
        if ($score >= 25) return 'weak_match';
        return 'poor_match';
    }

    /**
     * Standard deviation calculation.
     */
    private function standardDeviation(array $values): float
    {
        $n = count($values);
        if ($n <= 1) return 0;

        $mean = array_sum($values) / $n;
        $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / $n;

        return sqrt($variance);
    }
}
