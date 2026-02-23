<?php

namespace App\Services\Fleet;

use App\Models\BehavioralProfile;
use App\Models\CaptainProfile;
use App\Models\CandidateContract;
use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Models\SeafarerCertificate;
use App\Models\Vessel;
use App\Services\Behavioral\VesselFitEvidenceService;
use App\Services\Maritime\RoleWeightMap;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrewSynergyEngineV2
{
    private RoleWeightMap $roleWeights;

    public function __construct()
    {
        $this->roleWeights = new RoleWeightMap();
    }
    private const COMPETENCY_DIMENSIONS = [
        'DISCIPLINE', 'LEADERSHIP', 'STRESS', 'TEAMWORK', 'COMMS', 'TECH_PRACTICAL',
    ];

    public function isEnabled(): bool
    {
        return (bool) config('maritime.crew_synergy_v2', false);
    }

    /**
     * Role-based synergy scoring using weighted behavioral dimensions.
     *
     * Input keys:
     *   role_key        — master/chief_officer/chief_engineer/able_seaman/...
     *   trait_scores_01 — ['respect'=>0.8, 'discipline'=>0.7, ...]
     *   hard_fit_01     — 0..1 hard-fit score (certs, experience, etc.)
     *
     * Returns normalized 0..1 synergy score with component breakdown.
     */
    public function score(array $input, array $featurePayload = []): array
    {
        $tenantOverrides = $featurePayload['role_weights_overrides'] ?? [];

        $roleKey = $input['role_key'] ?? 'default';
        $traitScores01 = $input['trait_scores_01'] ?? [];
        $hardFit01 = (float) ($input['hard_fit_01'] ?? 0.5);

        $weights = $this->roleWeights->resolve($roleKey, $tenantOverrides);
        $softFit01 = $this->roleWeights->computeWeightedScore($traitScores01, $weights);

        $synergy01 = (0.55 * $hardFit01) + (0.45 * $softFit01);

        return [
            'synergy_01' => max(0, min(1, $synergy01)),
            'components' => [
                'hard_fit_01' => $hardFit01,
                'soft_fit_01' => $softFit01,
            ],
            'role' => [
                'role_key_used' => $roleKey,
                'weights_version' => config('maritime.synergy_weights.version'),
                'weights_used' => $weights,
                'trait_scores_01' => $traitScores01,
            ],
            'meta' => [
                'engine' => 'v2',
            ],
        ];
    }

    /**
     * Compute full 4-pillar compatibility for a candidate on a vessel.
     */
    public function computeCompatibility(string $candidateId, string $vesselId): ?array
    {
        try {
            $candidate = PoolCandidate::find($candidateId);
            $vessel = Vessel::find($vesselId);

            if (!$candidate || !$vessel) {
                return null;
            }

            $crewContext = $this->getVesselCrewContext($vesselId);
            $candidateBehavioral = BehavioralProfile::where('candidate_id', $candidateId)
                ->where('status', BehavioralProfile::STATUS_FINAL)
                ->latest('computed_at')
                ->first();
            $candidateTrust = CandidateTrustProfile::where('pool_candidate_id', $candidateId)->first();

            $weights = config('maritime.synergy_v2.component_weights');

            // Compute each pillar
            $captainFit = $this->computeCaptainFit($candidateBehavioral, $crewContext);
            $teamBalance = $this->computeTeamBalance($candidateId, $candidateTrust, $crewContext);
            $vesselFit = $this->computeVesselFit($candidateId, $candidateBehavioral, $vessel);
            $operationalRisk = $this->computeOperationalRisk($candidateId, $candidateBehavioral, $candidateTrust, $vessel);

            // Composite score
            $score = (int) round(
                $captainFit['score'] * $weights['captain_fit'] +
                $teamBalance['score'] * $weights['team_balance'] +
                $vesselFit['score'] * $weights['vessel_fit'] +
                $operationalRisk['score'] * $weights['operational_risk']
            );
            $score = max(0, min(100, $score));

            // Merge all evidence
            $allEvidence = array_merge(
                $captainFit['evidence'] ?? [],
                $teamBalance['evidence'] ?? [],
                $vesselFit['evidence'] ?? [],
                $operationalRisk['evidence'] ?? []
            );

            return [
                'candidate' => [
                    'id' => $candidateId,
                    'name' => $candidate->first_name . ' ' . $candidate->last_name,
                    'rank' => $candidate->rank,
                ],
                'vessel' => [
                    'id' => $vesselId,
                    'name' => $vessel->name,
                    'imo' => $vessel->imo,
                    'type' => $vessel->type,
                ],
                'compatibility_score' => $score,
                'label' => $this->compatibilityLabel($score),
                'pillars' => [
                    'captain_fit' => $captainFit,
                    'team_balance' => $teamBalance,
                    'vessel_fit' => $vesselFit,
                    'operational_risk' => $operationalRisk,
                ],
                'evidence' => $allEvidence,
                'computed_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::channel('single')->warning('CrewSynergyEngineV2::computeCompatibility failed', [
                'candidate_id' => $candidateId,
                'vessel_id' => $vesselId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Shortlist candidates for a role on a vessel, ranked by compatibility.
     */
    public function shortlistCandidates(string $vesselId, string $rankCode, int $limit = 10): array
    {
        $vessel = Vessel::find($vesselId);
        if (!$vessel) {
            return [];
        }

        // Reuse CrewPlanningService's eligibility logic: maritime, assessed/in_pool, matching rank, verified email, not active on vessel
        $activeOnVessel = CandidateContract::query()
            ->where(function ($q) use ($vesselId, $vessel) {
                $q->where('vessel_id', $vesselId)
                    ->orWhere('vessel_imo', $vessel->imo);
            })
            ->whereNull('end_date')
            ->pluck('pool_candidate_id');

        $candidates = PoolCandidate::query()
            ->where('primary_industry', 'maritime')
            ->whereIn('status', [PoolCandidate::STATUS_ASSESSED, PoolCandidate::STATUS_IN_POOL])
            ->where('rank', $rankCode)
            ->whereNotNull('email_verified_at')
            ->whereNotIn('id', $activeOnVessel)
            ->limit($limit * 3) // Over-fetch for ranking
            ->get();

        if ($candidates->isEmpty()) {
            return [];
        }

        // Batch-load profiles (2 queries instead of N×2)
        $candidateIds = $candidates->pluck('id');
        $behavioralProfiles = BehavioralProfile::whereIn('candidate_id', $candidateIds)
            ->where('status', BehavioralProfile::STATUS_FINAL)
            ->get()
            ->groupBy('candidate_id')
            ->map(fn ($group) => $group->sortByDesc('computed_at')->first());

        $trustProfiles = CandidateTrustProfile::whereIn('pool_candidate_id', $candidateIds)
            ->get()
            ->keyBy('pool_candidate_id');

        $crewContext = $this->getVesselCrewContext($vesselId);
        $weights = config('maritime.synergy_v2.component_weights');
        $now = now();

        $results = [];
        foreach ($candidates as $candidate) {
            $behavioral = $behavioralProfiles[$candidate->id] ?? null;
            $trust = $trustProfiles[$candidate->id] ?? null;

            $captainFit = $this->computeCaptainFit($behavioral, $crewContext);
            $teamBalance = $this->computeTeamBalance($candidate->id, $trust, $crewContext);
            $vesselFit = $this->computeVesselFit($candidate->id, $behavioral, $vessel);
            $operationalRisk = $this->computeOperationalRisk($candidate->id, $behavioral, $trust, $vessel);

            $score = (int) round(
                $captainFit['score'] * $weights['captain_fit'] +
                $teamBalance['score'] * $weights['team_balance'] +
                $vesselFit['score'] * $weights['vessel_fit'] +
                $operationalRisk['score'] * $weights['operational_risk']
            );
            $score = max(0, min(100, $score));

            // Availability resolution (same logic as CrewPlanningService)
            $availStatus = $candidate->availability_status ?? 'unknown';
            $contractEnd = $candidate->contract_end_estimate;
            $daysToAvailable = null;

            if ($availStatus === 'available') {
                $daysToAvailable = 0;
            } elseif ($availStatus === 'on_contract' && $contractEnd) {
                $daysToAvailable = max(0, (int) $now->diffInDays($contractEnd, false));
                $availStatus = 'soon_available';
            } elseif ($availStatus === 'soon_available') {
                $daysToAvailable = $contractEnd ? max(0, (int) $now->diffInDays($contractEnd, false)) : null;
            }

            // Top evidence (3 items)
            $allEvidence = array_merge(
                $captainFit['evidence'] ?? [],
                $teamBalance['evidence'] ?? [],
                $vesselFit['evidence'] ?? [],
                $operationalRisk['evidence'] ?? []
            );

            $results[] = [
                'candidate_id' => $candidate->id,
                'name' => $candidate->first_name . ' ' . $candidate->last_name,
                'rank' => $candidate->rank,
                'compatibility_score' => $score,
                'label' => $this->compatibilityLabel($score),
                'captain_fit' => $captainFit['score'],
                'team_balance_fit' => $teamBalance['score'],
                'vessel_fit' => $vesselFit['score'],
                'operational_risk' => $operationalRisk['score'],
                'evidence' => array_slice($allEvidence, 0, 3),
                'availability_status' => $availStatus,
                'contract_end_estimate' => $contractEnd?->toDateString(),
                'days_to_available' => $daysToAvailable,
            ];
        }

        // Sort by score desc
        usort($results, fn ($a, $b) => $b['compatibility_score'] <=> $a['compatibility_score']);

        return array_slice($results, 0, $limit);
    }

    // ─── Pillar 1: Captain Fit ────────────────────────────────────────────

    private function computeCaptainFit(?BehavioralProfile $candidateBehavioral, array $crewContext): array
    {
        $evidence = [];

        // No captain on vessel
        if (!$crewContext['captain']) {
            return [
                'score' => 50,
                'captain_style' => 'unknown',
                'captain_name' => null,
                'evidence' => [
                    ['source' => 'crew_roster', 'key' => 'no_captain', 'label' => 'No captain assigned', 'detail' => 'Neutral score applied', 'pillar' => 'captain_fit'],
                ],
            ];
        }

        $captainName = $crewContext['captain']['name'];
        $captainBehavioral = $crewContext['captain']['behavioral'];
        $captainCandidateId = $crewContext['captain']['candidate_id'] ?? null;

        // Prefer CaptainProfile (outcome-based) when available with sufficient confidence
        $captainProfile = $captainCandidateId
            ? CaptainProfile::where('candidate_id', $captainCandidateId)->first()
            : null;

        if ($captainProfile && $captainProfile->isSufficientForScoring()) {
            $captainStyle = $captainProfile->primary_style ?? 'balanced';
            $styleSource = 'captain_profile';
            $evidence[] = [
                'source' => 'captain_profile',
                'key' => 'captain_style',
                'label' => "Captain style: {$captainStyle} (profiled)",
                'detail' => "Captain {$captainName} profiled as {$captainStyle} — confidence " . round($captainProfile->confidence * 100) . '%',
                'pillar' => 'captain_fit',
            ];
        } else {
            // Fallback: infer captain style from behavioral dimensions
            $captainStyle = $this->inferCaptainStyle($captainBehavioral);
            $styleSource = 'behavioral_inference';
            $evidence[] = [
                'source' => 'captain_analysis',
                'key' => 'captain_style',
                'label' => "Captain style: {$captainStyle}",
                'detail' => "Captain {$captainName} classified as {$captainStyle}",
                'pillar' => 'captain_fit',
            ];
        }

        // Score candidate against captain's style preferences
        if (!$candidateBehavioral) {
            return [
                'score' => 45,
                'captain_style' => $captainStyle,
                'captain_name' => $captainName,
                'evidence' => array_merge($evidence, [
                    ['source' => 'behavioral_profile', 'key' => 'no_profile', 'label' => 'No behavioral profile', 'detail' => 'Cannot assess captain fit without candidate behavioral data', 'pillar' => 'captain_fit'],
                ]),
            ];
        }

        $candidateDims = $candidateBehavioral->dimensions_json ?? [];
        $score = $this->scoreCandidateForStyle($captainStyle, $candidateDims);

        // Evidence for individual dimension contributions
        $styleDetails = $this->getStyleDimensionDetails($captainStyle, $candidateDims);
        foreach ($styleDetails as $detail) {
            $evidence[] = array_merge($detail, ['pillar' => 'captain_fit']);
        }

        return [
            'score' => $score,
            'captain_style' => $captainStyle,
            'captain_name' => $captainName,
            'evidence' => $evidence,
        ];
    }

    private function inferCaptainStyle(?BehavioralProfile $captainBehavioral): string
    {
        if (!$captainBehavioral) {
            return 'balanced';
        }

        $dims = $captainBehavioral->dimensions_json ?? [];
        $thresholds = config('maritime.synergy_v2.captain_style_thresholds');

        // Check authoritative
        if ($this->dimScore($dims, 'DISCIPLINE_COMPLIANCE') >= $thresholds['authoritative']['DISCIPLINE_COMPLIANCE']
            && $this->dimScore($dims, 'CONFLICT_RISK') >= $thresholds['authoritative']['CONFLICT_RISK']) {
            return 'authoritative';
        }

        // Check collaborative
        if ($this->dimScore($dims, 'TEAM_COOPERATION') >= $thresholds['collaborative']['TEAM_COOPERATION']
            && $this->dimScore($dims, 'COMM_CLARITY') >= $thresholds['collaborative']['COMM_CLARITY']) {
            return 'collaborative';
        }

        // Check adaptive
        if ($this->dimScore($dims, 'STRESS_CONTROL') >= $thresholds['adaptive']['STRESS_CONTROL']
            && $this->dimScore($dims, 'LEARNING_GROWTH') >= $thresholds['adaptive']['LEARNING_GROWTH']) {
            return 'adaptive';
        }

        return 'balanced';
    }

    private function dimScore(array $dims, string $key): float
    {
        $val = $dims[$key] ?? null;
        if (is_array($val)) {
            return (float) ($val['score'] ?? 0);
        }
        return (float) ($val ?? 0);
    }

    private function scoreCandidateForStyle(string $style, array $candidateDims): int
    {
        $score = 50; // neutral base

        switch ($style) {
            case 'authoritative':
                // Wants high DISCIPLINE, STRESS, RELIABILITY + low CONFLICT
                $score += $this->dimContribution($candidateDims, 'DISCIPLINE_COMPLIANCE', 60, 12);
                $score += $this->dimContribution($candidateDims, 'STRESS_CONTROL', 55, 10);
                $score += $this->dimContribution($candidateDims, 'RELIABILITY_STABILITY', 55, 10);
                // Low CONFLICT_RISK is good for authoritative crews (inverted: high value = risky)
                $conflictScore = $this->dimScore($candidateDims, 'CONFLICT_RISK');
                $score += ($conflictScore < 40) ? 10 : (($conflictScore > 65) ? -10 : 0);
                break;

            case 'collaborative':
                // Wants high TEAM, COMM, LEARNING
                $score += $this->dimContribution($candidateDims, 'TEAM_COOPERATION', 60, 15);
                $score += $this->dimContribution($candidateDims, 'COMM_CLARITY', 55, 12);
                $score += $this->dimContribution($candidateDims, 'LEARNING_GROWTH', 50, 10);
                break;

            case 'adaptive':
                // Wants high LEARNING, STRESS, COMM
                $score += $this->dimContribution($candidateDims, 'LEARNING_GROWTH', 55, 15);
                $score += $this->dimContribution($candidateDims, 'STRESS_CONTROL', 55, 12);
                $score += $this->dimContribution($candidateDims, 'COMM_CLARITY', 50, 10);
                break;

            case 'balanced':
            default:
                // Balanced: moderate contribution from all dimensions
                foreach (['DISCIPLINE_COMPLIANCE', 'TEAM_COOPERATION', 'COMM_CLARITY', 'STRESS_CONTROL', 'RELIABILITY_STABILITY'] as $dim) {
                    $score += $this->dimContribution($candidateDims, $dim, 50, 6);
                }
                break;
        }

        return max(0, min(100, $score));
    }

    private function dimContribution(array $dims, string $key, float $threshold, int $maxPoints): int
    {
        $val = $this->dimScore($dims, $key);
        if ($val >= $threshold + 20) return $maxPoints;
        if ($val >= $threshold) return (int) round($maxPoints * 0.5);
        if ($val >= $threshold - 15) return 0;
        return -1 * (int) round($maxPoints * 0.4);
    }

    private function getStyleDimensionDetails(string $style, array $candidateDims): array
    {
        $details = [];
        $keyDimensions = match ($style) {
            'authoritative' => ['DISCIPLINE_COMPLIANCE', 'STRESS_CONTROL', 'RELIABILITY_STABILITY'],
            'collaborative' => ['TEAM_COOPERATION', 'COMM_CLARITY', 'LEARNING_GROWTH'],
            'adaptive'      => ['LEARNING_GROWTH', 'STRESS_CONTROL', 'COMM_CLARITY'],
            default         => ['DISCIPLINE_COMPLIANCE', 'TEAM_COOPERATION', 'STRESS_CONTROL'],
        };

        foreach ($keyDimensions as $dim) {
            $val = $this->dimScore($candidateDims, $dim);
            $level = $val >= 70 ? 'strong' : ($val >= 50 ? 'moderate' : 'weak');
            $details[] = [
                'source' => 'behavioral_profile',
                'key' => strtolower($dim),
                'label' => str_replace('_', ' ', ucfirst(strtolower($dim))) . ": {$val}",
                'detail' => "Candidate {$level} in {$dim} (score: {$val})",
            ];
        }

        return $details;
    }

    // ─── Pillar 2: Team Balance ───────────────────────────────────────────

    private function computeTeamBalance(string $candidateId, ?CandidateTrustProfile $candidateTrust, array $crewContext): array
    {
        $evidence = [];
        $crewMembers = $crewContext['crew_members'] ?? [];

        if (empty($crewMembers)) {
            return [
                'score' => 60,
                'evidence' => [
                    ['source' => 'crew_roster', 'key' => 'no_crew', 'label' => 'No existing crew data', 'detail' => 'Cannot assess team balance without crew profiles', 'pillar' => 'team_balance'],
                ],
            ];
        }

        // Get candidate competency dimensions
        $candidateDims = $this->extractCompetencyDimensions($candidateTrust);
        if (empty($candidateDims)) {
            return [
                'score' => 50,
                'evidence' => [
                    ['source' => 'trust_profile', 'key' => 'no_dimensions', 'label' => 'No candidate competency data', 'detail' => 'Cannot assess team balance', 'pillar' => 'team_balance'],
                ],
            ];
        }

        // Collect all crew dimensions + candidate
        $allDimensionSets = [];
        foreach ($crewMembers as $member) {
            if (!empty($member['competency_dims'])) {
                $allDimensionSets[] = $member['competency_dims'];
            }
        }
        $allDimensionSets[] = $candidateDims; // Include candidate

        // Compute per-dimension std deviation
        $idealRange = config('maritime.synergy_v2.team_balance_ideal_std_range', [8, 25]);
        $stdDevs = [];

        foreach (self::COMPETENCY_DIMENSIONS as $dim) {
            $values = [];
            foreach ($allDimensionSets as $set) {
                if (isset($set[$dim]) && $set[$dim] !== null) {
                    $values[] = (float) $set[$dim];
                }
            }
            if (count($values) >= 2) {
                $stdDevs[$dim] = $this->stdDev($values);
            }
        }

        if (empty($stdDevs)) {
            return [
                'score' => 50,
                'evidence' => [
                    ['source' => 'competency_engine', 'key' => 'insufficient_data', 'label' => 'Insufficient dimension data', 'detail' => 'Not enough crew members with competency scores', 'pillar' => 'team_balance'],
                ],
            ];
        }

        $avgStd = array_sum($stdDevs) / count($stdDevs);

        // Score: ideal range [8,25] → 100. Outside → proportional penalty
        if ($avgStd >= $idealRange[0] && $avgStd <= $idealRange[1]) {
            $score = 100;
        } elseif ($avgStd < $idealRange[0]) {
            // Too uniform
            $score = max(30, (int) round(100 * ($avgStd / $idealRange[0])));
        } else {
            // Too polarized
            $overshoot = $avgStd - $idealRange[1];
            $score = max(20, (int) round(100 - ($overshoot * 3)));
        }

        $evidence[] = [
            'source' => 'competency_engine',
            'key' => 'team_std',
            'label' => "Team dimension std dev: " . round($avgStd, 1),
            'detail' => $avgStd < $idealRange[0] ? 'Team too uniform — groupthink risk' :
                ($avgStd > $idealRange[1] ? 'Team too polarized — cohesion risk' : 'Team diversity in healthy range'),
            'pillar' => 'team_balance',
        ];

        // Bonus: candidate fills dimension gaps
        $crewOnlyDimSets = array_slice($allDimensionSets, 0, -1);
        $crewAvgs = $this->computeDimensionAverages($crewOnlyDimSets);

        foreach (self::COMPETENCY_DIMENSIONS as $dim) {
            $crewAvg = $crewAvgs[$dim] ?? null;
            $candidateVal = $candidateDims[$dim] ?? null;
            if ($crewAvg !== null && $candidateVal !== null && $crewAvg < 45 && $candidateVal >= 60) {
                $score = min(100, $score + 10);
                $evidence[] = [
                    'source' => 'competency_engine',
                    'key' => 'fills_gap_' . strtolower($dim),
                    'label' => "Fills {$dim} gap",
                    'detail' => "Crew avg {$crewAvg} in {$dim}, candidate brings {$candidateVal}",
                    'pillar' => 'team_balance',
                ];
            }
        }

        // Penalty: creates extreme clustering
        foreach (self::COMPETENCY_DIMENSIONS as $dim) {
            $highCount = 0;
            foreach ($crewOnlyDimSets as $set) {
                if (isset($set[$dim]) && $set[$dim] > 75) {
                    $highCount++;
                }
            }
            $candidateVal = $candidateDims[$dim] ?? 0;
            if ($highCount >= 3 && $candidateVal > 75) {
                $score = max(0, $score - 5);
                $evidence[] = [
                    'source' => 'competency_engine',
                    'key' => 'cluster_' . strtolower($dim),
                    'label' => "Over-concentration in {$dim}",
                    'detail' => "{$highCount} crew members already score >75, candidate adds more",
                    'pillar' => 'team_balance',
                ];
            }
        }

        return [
            'score' => max(0, min(100, $score)),
            'evidence' => $evidence,
        ];
    }

    // ─── Pillar 3: Vessel Fit ─────────────────────────────────────────────

    private function computeVesselFit(string $candidateId, ?BehavioralProfile $behavioral, Vessel $vessel): array
    {
        $evidence = [];

        if (!$behavioral) {
            return [
                'score' => 40,
                'confidence' => 'none',
                'primary_source' => 'none',
                'evidence' => [
                    ['source' => 'behavioral_profile', 'key' => 'no_profile', 'label' => 'No behavioral profile', 'detail' => 'Cannot assess vessel fit without behavioral data', 'pillar' => 'vessel_fit'],
                ],
            ];
        }

        $fitJson = $behavioral->fit_json ?? [];
        $confidence = $behavioral->confidence ?? 0;

        try {
            $vesselFitService = app(VesselFitEvidenceService::class);
            $fitResults = $vesselFitService->compute($candidateId, $fitJson, $confidence);

            // Find matching vessel type entry
            $vesselType = $this->mapVesselTypeToFitCode($vessel->type);
            $matchingFit = null;

            foreach ($fitResults as $entry) {
                if (($entry['vessel_type'] ?? '') === $vesselType) {
                    $matchingFit = $entry;
                    break;
                }
            }

            if (!$matchingFit) {
                return [
                    'score' => 40,
                    'confidence' => 'none',
                    'primary_source' => 'unknown',
                    'evidence' => [
                        ['source' => 'vessel_fit', 'key' => 'no_match', 'label' => 'Unknown vessel type', 'detail' => "No fit data for vessel type: {$vessel->type}", 'pillar' => 'vessel_fit'],
                    ],
                ];
            }

            $score = (int) ($matchingFit['fit_pct'] ?? 40);

            $evidence[] = [
                'source' => 'vessel_fit',
                'key' => 'fit_score',
                'label' => "Vessel fit: {$score}%",
                'detail' => "Primary source: " . ($matchingFit['primary_source'] ?? 'unknown') . ", confidence: " . ($matchingFit['confidence'] ?? 'none'),
                'pillar' => 'vessel_fit',
            ];

            // Include top evidence items from VesselFitEvidenceService
            foreach (array_slice($matchingFit['evidence'] ?? [], 0, 2) as $ev) {
                $evidence[] = array_merge($ev, ['pillar' => 'vessel_fit']);
            }

            return [
                'score' => $score,
                'confidence' => $matchingFit['confidence'] ?? 'none',
                'primary_source' => $matchingFit['primary_source'] ?? 'unknown',
                'evidence' => $evidence,
            ];
        } catch (\Throwable $e) {
            Log::channel('single')->warning('CrewSynergyEngineV2: vessel fit computation failed', [
                'candidate_id' => $candidateId,
                'error' => $e->getMessage(),
            ]);
            return [
                'score' => 40,
                'confidence' => 'none',
                'primary_source' => 'error',
                'evidence' => [
                    ['source' => 'vessel_fit', 'key' => 'error', 'label' => 'Vessel fit unavailable', 'detail' => 'Service error during computation', 'pillar' => 'vessel_fit'],
                ],
            ];
        }
    }

    // ─── Pillar 4: Operational Risk ───────────────────────────────────────

    private function computeOperationalRisk(
        string $candidateId,
        ?BehavioralProfile $behavioral,
        ?CandidateTrustProfile $trust,
        Vessel $vessel
    ): array {
        $score = 100; // Start safe, deduct for risk factors
        $evidence = [];
        $riskFactors = [];

        // Factor 1: Certificate expiry within contract window
        $warningDays = config('maritime.synergy_v2.cert_expiry_warning_days', 90);
        $expiringCerts = SeafarerCertificate::where('pool_candidate_id', $candidateId)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($warningDays))
            ->where('expires_at', '>=', now())
            ->count();

        if ($expiringCerts > 0) {
            $deduction = min(40, $expiringCerts * 20);
            $score -= $deduction;
            $riskFactors[] = "cert_expiry:{$expiringCerts}";
            $evidence[] = [
                'source' => 'certificates',
                'key' => 'cert_expiry',
                'label' => "{$expiringCerts} certificate(s) expiring within {$warningDays}d",
                'detail' => "Deduction: -{$deduction}",
                'pillar' => 'operational_risk',
            ];
        }

        // Factor 2: Availability mismatch
        $candidate = PoolCandidate::find($candidateId);
        if ($candidate && $candidate->availability_status === 'on_contract' && !$candidate->contract_end_estimate) {
            $score -= 15;
            $riskFactors[] = 'availability_mismatch';
            $evidence[] = [
                'source' => 'availability',
                'key' => 'on_contract_no_end',
                'label' => 'On contract with no end date',
                'detail' => 'Availability uncertain — deduction: -15',
                'pillar' => 'operational_risk',
            ];
        }

        // Factor 3: High CONFLICT_RISK
        if ($behavioral) {
            $conflictRisk = $this->dimScore($behavioral->dimensions_json ?? [], 'CONFLICT_RISK');
            if ($conflictRisk > 60) {
                $score -= 15;
                $riskFactors[] = 'high_conflict_risk';
                $evidence[] = [
                    'source' => 'behavioral_profile',
                    'key' => 'conflict_risk',
                    'label' => "High conflict risk: {$conflictRisk}",
                    'detail' => 'Elevated interpersonal conflict probability — deduction: -15',
                    'pillar' => 'operational_risk',
                ];
            }
        }

        // Factor 4: Early termination ratio
        $threshold = config('maritime.synergy_v2.early_termination_threshold_ratio', 0.3);
        $contracts = CandidateContract::where('pool_candidate_id', $candidateId)->get();
        $totalContracts = $contracts->count();
        $earlyTerminations = $contracts->where('early_termination', true)->count();

        if ($totalContracts >= 2 && $earlyTerminations / $totalContracts > $threshold) {
            $score -= 20;
            $ratio = round($earlyTerminations / $totalContracts * 100);
            $riskFactors[] = 'early_termination';
            $evidence[] = [
                'source' => 'contract_history',
                'key' => 'early_termination',
                'label' => "Early termination ratio: {$ratio}%",
                'detail' => "{$earlyTerminations}/{$totalContracts} contracts ended early — deduction: -20",
                'pillar' => 'operational_risk',
            ];
        }

        // Factor 5: Low stability index
        if ($trust && $trust->stability_index !== null && $trust->stability_index < 4.0) {
            $score -= 10;
            $riskFactors[] = 'low_stability';
            $evidence[] = [
                'source' => 'trust_profile',
                'key' => 'stability',
                'label' => "Stability index: {$trust->stability_index}",
                'detail' => 'Below threshold (4.0) — deduction: -10',
                'pillar' => 'operational_risk',
            ];
        }

        $score = max(0, min(100, $score));
        $riskLevel = $score >= 70 ? 'low' : ($score >= 40 ? 'medium' : 'high');

        return [
            'score' => $score,
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'evidence' => $evidence,
        ];
    }

    // ─── Shared Helpers ───────────────────────────────────────────────────

    /**
     * Get cached vessel crew context (captain, crew members with dimensions).
     */
    private function getVesselCrewContext(string $vesselId): array
    {
        $ttl = config('maritime.synergy_v2.cache_ttl_seconds', 300);

        return Cache::remember("synergy_v2:vessel_crew:{$vesselId}", $ttl, function () use ($vesselId) {
            $slots = DB::table('vessel_crew_skeleton_slots')
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

            return [
                'captain' => $captain,
                'crew_members' => $crewMembers,
                'crew_count' => count($crewMembers),
            ];
        });
    }

    private function extractCompetencyDimensions(?CandidateTrustProfile $profile): array
    {
        if (!$profile) {
            return [];
        }

        $detail = $profile->detail_json ?? [];
        $dimensions = $detail['competency_engine']['score_by_dimension']
            ?? $detail['competency']['dimensions']
            ?? [];

        $result = [];
        foreach (self::COMPETENCY_DIMENSIONS as $dim) {
            $val = $dimensions[$dim] ?? null;
            if (is_array($val)) {
                $val = $val['score'] ?? null;
            }
            $result[$dim] = $val !== null ? (int) $val : null;
        }
        return $result;
    }

    private function computeDimensionAverages(array $dimensionSets): array
    {
        $sums = [];
        $counts = [];

        foreach (self::COMPETENCY_DIMENSIONS as $dim) {
            $sums[$dim] = 0;
            $counts[$dim] = 0;
        }

        foreach ($dimensionSets as $set) {
            foreach (self::COMPETENCY_DIMENSIONS as $dim) {
                if (isset($set[$dim]) && $set[$dim] !== null) {
                    $sums[$dim] += $set[$dim];
                    $counts[$dim]++;
                }
            }
        }

        $avgs = [];
        foreach (self::COMPETENCY_DIMENSIONS as $dim) {
            $avgs[$dim] = $counts[$dim] > 0 ? round($sums[$dim] / $counts[$dim], 1) : null;
        }
        return $avgs;
    }

    private function stdDev(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;
        $mean = array_sum($values) / $n;
        $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / ($n - 1);
        return round(sqrt($variance), 2);
    }

    private function mapVesselTypeToFitCode(?string $vesselType): string
    {
        if (!$vesselType) return 'UNKNOWN';

        $mapping = [
            'tanker' => 'TANKER', 'oil_tanker' => 'TANKER', 'chemical_tanker' => 'TANKER',
            'lng' => 'LNG', 'lpg' => 'LNG', 'lng_lpg' => 'LNG', 'gas_carrier' => 'LNG',
            'container' => 'CONTAINER_ULCS', 'container_ship' => 'CONTAINER_ULCS',
            'passenger' => 'PASSENGER', 'cruise' => 'PASSENGER', 'ferry' => 'PASSENGER',
            'offshore' => 'OFFSHORE', 'platform_supply' => 'OFFSHORE', 'ahts' => 'OFFSHORE',
            'river' => 'RIVER', 'inland' => 'RIVER', 'barge' => 'RIVER', 'river_vessel' => 'RIVER',
            'coastal' => 'COASTAL',
            'short_sea' => 'SHORT_SEA',
            'deep_sea' => 'DEEP_SEA', 'bulk_carrier' => 'DEEP_SEA',
        ];

        return $mapping[strtolower($vesselType)] ?? 'UNKNOWN';
    }

    private function compatibilityLabel(int $score): string
    {
        if ($score >= 80) return 'strong_match';
        if ($score >= 65) return 'good_match';
        if ($score >= 45) return 'moderate_match';
        if ($score >= 30) return 'weak_match';
        return 'poor_match';
    }
}
