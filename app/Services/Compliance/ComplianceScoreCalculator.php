<?php

namespace App\Services\Compliance;

use App\Models\CandidateContract;
use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;

class ComplianceScoreCalculator
{
    private const SECTION_WEIGHTS = [
        'cri'       => 0.25,
        'technical' => 0.25,
        'stability' => 0.20,
        'stcw'      => 0.15,
        'ais'       => 0.15,
    ];

    public function calculate(CandidateTrustProfile $profile, PoolCandidate $candidate): ?ComplianceScoreResult
    {
        $sections = [];

        // CRI section — direct from cri_score column (0-100)
        $sections['cri'] = $this->getCriScore($profile);

        // Technical section — from detail_json rank_stcw technical_score (0.0-1.0 → 0-100)
        $sections['technical'] = $this->getTechnicalScore($profile);

        // Stability section — composite from stability_index + risk_score
        $sections['stability'] = $this->getStabilityScore($profile);

        // STCW section — from detail_json rank_stcw stcw_compliance compliance_ratio (0.0-1.0 → 0-100)
        $sections['stcw'] = $this->getStcwScore($profile);

        // AIS section — average confidence from verified contracts
        $sections['ais'] = $this->getAisScore($candidate);

        // Count available sections
        $available = array_filter($sections, fn($s) => $s !== null);
        $availableCount = count($available);

        // Need at least 2 sections to compute a meaningful score
        if ($availableCount < 2) {
            return null;
        }

        // Redistribute weights among available sections
        $totalAvailableWeight = 0.0;
        foreach ($available as $key => $score) {
            $totalAvailableWeight += self::SECTION_WEIGHTS[$key];
        }

        $sectionScores = [];
        $weightedSum = 0.0;

        foreach (self::SECTION_WEIGHTS as $key => $baseWeight) {
            $rawScore = $sections[$key];
            $isAvailable = $rawScore !== null;
            $effectiveWeight = $isAvailable ? ($baseWeight / $totalAvailableWeight) : 0;
            $weightedScore = $isAvailable ? $rawScore * $effectiveWeight : 0;
            $weightedSum += $weightedScore;

            $sectionScores[] = [
                'section' => $key,
                'raw_score' => $isAvailable ? round($rawScore, 1) : null,
                'weight' => round($effectiveWeight, 4),
                'weighted_score' => round($weightedScore, 2),
                'available' => $isAvailable,
            ];
        }

        $finalScore = (int) round(min(100, max(0, $weightedSum)));

        return new ComplianceScoreResult(
            score: $finalScore,
            sectionScores: $sectionScores,
            availableSections: $availableCount,
            effectiveWeightSum: round($totalAvailableWeight, 4),
        );
    }

    private function getCriScore(CandidateTrustProfile $profile): ?float
    {
        if ($profile->cri_score === null) {
            return null;
        }
        return (float) $profile->cri_score;
    }

    private function getTechnicalScore(CandidateTrustProfile $profile): ?float
    {
        $detail = $profile->detail_json ?? [];
        $techScore = $detail['rank_stcw']['technical_score'] ?? null;
        if ($techScore === null) {
            return null;
        }
        return (float) $techScore * 100;
    }

    private function getStabilityScore(CandidateTrustProfile $profile): ?float
    {
        if ($profile->stability_index === null && $profile->risk_score === null) {
            return null;
        }

        $stabilityPart = 50.0;
        if ($profile->stability_index !== null) {
            $stabilityPart = (min((float) $profile->stability_index, 10) / 10) * 50;
        }

        $riskPart = 50.0;
        if ($profile->risk_score !== null) {
            $riskPart = (1 - (float) $profile->risk_score) * 50;
        }

        return $stabilityPart + $riskPart;
    }

    private function getStcwScore(CandidateTrustProfile $profile): ?float
    {
        $detail = $profile->detail_json ?? [];
        $ratio = $detail['rank_stcw']['stcw_compliance']['compliance_ratio'] ?? null;
        if ($ratio === null) {
            return null;
        }
        return (float) $ratio * 100;
    }

    private function getAisScore(PoolCandidate $candidate): ?float
    {
        $contracts = CandidateContract::where('pool_candidate_id', $candidate->id)
            ->whereHas('latestAisVerification', fn($q) => $q->whereNotNull('confidence_score'))
            ->with('latestAisVerification')
            ->get();

        if ($contracts->isEmpty()) {
            return null;
        }

        $totalConfidence = 0.0;
        $count = 0;
        foreach ($contracts as $contract) {
            $verification = $contract->latestAisVerification;
            if ($verification && $verification->confidence_score !== null) {
                $totalConfidence += (float) $verification->confidence_score;
                $count++;
            }
        }

        if ($count === 0) {
            return null;
        }

        return ($totalConfidence / $count) * 100;
    }
}
