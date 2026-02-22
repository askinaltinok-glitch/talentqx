<?php

namespace App\Services\Maritime;

use App\Models\CandidateCommandProfile;
use App\Models\CandidateQualificationCheck;
use App\Models\CandidatePhaseReview;
use App\Models\LanguageAssessment;
use App\Models\PoolCandidate;
use App\Models\Vessel;
use Illuminate\Support\Facades\DB;

class CrewSynergyPreviewService
{
    private const BASE_SCORE = 70;

    // Penalty definitions (from spec)
    private const PENALTY_COMMAND_LANGUAGE = 15;
    private const PENALTY_DERIVED_PROFILE = 8;
    private const PENALTY_REJECTED_CERT = 12;
    private const PENALTY_UNAPPROVED_COMPETENCY = 6;

    // Command ranks that require B1+ language
    private const COMMAND_RANKS = ['MASTER', 'C/O', 'C/E', 'captain', 'chief_officer', 'chief_engineer'];

    /**
     * Compute a lightweight synergy preview for a candidate.
     * Returns null if no active vessel assignment.
     */
    public function previewForCandidate(string $candidateId): ?array
    {
        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return null;
        }

        // Find active vessel slot for this candidate
        $activeSlot = DB::table('vessel_crew_skeleton_slots')
            ->where('candidate_id', $candidateId)
            ->where('is_active', true)
            ->first();

        if (!$activeSlot) {
            return null;
        }

        $vessel = Vessel::find($activeSlot->vessel_id);
        if (!$vessel) {
            return null;
        }

        // Compute penalties
        $penalties = [];
        $score = self::BASE_SCORE;
        $rank = data_get($candidate->source_meta, 'rank', '');

        // 1. Command-role language penalty
        $isCommand = $this->isCommandRank($rank);
        if ($isCommand) {
            $langAssessment = LanguageAssessment::forCandidate($candidateId);
            $level = $langAssessment?->locked_level ?? $langAssessment?->estimated_level;
            $levelOrder = ['A1' => 1, 'A2' => 2, 'B1' => 3, 'B2' => 4, 'C1' => 5, 'C2' => 6];
            $levelValue = $levelOrder[$level] ?? 0;

            if ($levelValue < ($levelOrder['B1'] ?? 3)) {
                $score -= self::PENALTY_COMMAND_LANGUAGE;
                $penalties[] = [
                    'type' => 'command_language_below_b1',
                    'points' => self::PENALTY_COMMAND_LANGUAGE,
                    'detail' => "Command role ({$rank}) with language level {$level} (below B1)",
                ];
            }
        }

        // 2. Derived profile penalty
        $commandProfile = CandidateCommandProfile::where('candidate_id', $candidateId)->first();
        if ($commandProfile && ($commandProfile->source ?? 'derived') === 'derived') {
            $score -= self::PENALTY_DERIVED_PROFILE;
            $penalties[] = [
                'type' => 'derived_profile',
                'points' => self::PENALTY_DERIVED_PROFILE,
                'detail' => 'Command profile is derived (not Phase-1 verified)',
            ];
        }

        // 3. Rejected cert penalty
        $rejectedCerts = CandidateQualificationCheck::where('candidate_id', $candidateId)
            ->where('status', 'rejected')
            ->count();
        if ($rejectedCerts > 0) {
            $penalty = self::PENALTY_REJECTED_CERT * $rejectedCerts;
            $score -= $penalty;
            $penalties[] = [
                'type' => 'rejected_certs',
                'points' => $penalty,
                'detail' => "{$rejectedCerts} rejected qualification(s)",
            ];
        }

        // 4. Unapproved competency penalty
        $reviews = CandidatePhaseReview::where('candidate_id', $candidateId)->get();
        $unapprovedCount = 0;
        foreach ($reviews as $review) {
            if (!in_array($review->status, ['approved', 'completed'])) {
                $unapprovedCount++;
            }
        }
        // Also check if no review exists at all for standard_competency
        if ($reviews->where('phase_key', 'standard_competency')->isEmpty()) {
            $unapprovedCount++;
        }
        if ($unapprovedCount > 0) {
            $penalty = self::PENALTY_UNAPPROVED_COMPETENCY * $unapprovedCount;
            $score -= $penalty;
            $penalties[] = [
                'type' => 'unapproved_competency',
                'points' => $penalty,
                'detail' => "{$unapprovedCount} unapproved competency phase(s)",
            ];
        }

        $score = max(0, min(100, $score));

        // Count active crew on the same vessel
        $crewCount = DB::table('vessel_crew_skeleton_slots')
            ->where('vessel_id', $activeSlot->vessel_id)
            ->where('is_active', true)
            ->whereNotNull('candidate_id')
            ->count();

        return [
            'score' => $score,
            'label' => $this->scoreLabel($score),
            'base_score' => self::BASE_SCORE,
            'penalties' => $penalties,
            'total_penalty' => self::BASE_SCORE - $score,
            'vessel' => [
                'id' => $vessel->id,
                'name' => $vessel->name,
                'imo' => $vessel->imo,
            ],
            'slot_role' => $activeSlot->slot_role,
            'crew_count' => $crewCount,
        ];
    }

    private function isCommandRank(string $rank): bool
    {
        $normalized = strtolower(trim($rank));
        foreach (self::COMMAND_RANKS as $cr) {
            if (strtolower($cr) === $normalized) {
                return true;
            }
        }
        return false;
    }

    private function scoreLabel(int $score): string
    {
        if ($score >= 65) return 'good';
        if ($score >= 50) return 'moderate';
        if ($score >= 35) return 'low';
        return 'critical';
    }
}
