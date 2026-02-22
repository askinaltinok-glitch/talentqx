<?php

namespace App\Services\Compliance;

use App\Models\CandidateTrustProfile;

class ComplianceRecommendationEngine
{
    public function generate(int $score, array $sectionScores, string $status, CandidateTrustProfile $profile): array
    {
        $recommendations = [];

        // Collect available sections sorted by raw_score ascending (weakest first)
        $available = array_filter($sectionScores, fn($s) => $s['available'] && $s['raw_score'] !== null);
        usort($available, fn($a, $b) => $a['raw_score'] <=> $b['raw_score']);

        foreach ($available as $section) {
            if (count($recommendations) >= 5) break;

            $rawScore = $section['raw_score'];
            $name = $section['section'];

            // Only recommend for sections scoring below 70
            if ($rawScore >= 70) continue;

            $rec = match ($name) {
                'cri' => [
                    'priority' => $rawScore < 30 ? 1 : ($rawScore < 50 ? 2 : 3),
                    'section' => 'cri',
                    'recommendation' => 'Review contract history for consistency issues',
                    'action' => 'Verify employment records and resolve timeline discrepancies',
                ],
                'technical' => [
                    'priority' => $rawScore < 30 ? 1 : ($rawScore < 50 ? 2 : 3),
                    'section' => 'technical',
                    'recommendation' => 'Candidate may need additional sea-time in current rank',
                    'action' => 'Assess promotion readiness and required experience thresholds',
                ],
                'stability' => [
                    'priority' => $rawScore < 30 ? 1 : ($rawScore < 50 ? 2 : 3),
                    'section' => 'stability',
                    'recommendation' => 'High career volatility; verify reasons for frequent changes',
                    'action' => 'Conduct reference checks to understand employment gaps and transitions',
                ],
                'stcw' => [
                    'priority' => $rawScore < 30 ? 1 : ($rawScore < 50 ? 2 : 3),
                    'section' => 'stcw',
                    'recommendation' => 'Missing/expired certificates require renewal before deployment',
                    'action' => 'Request updated certificates and verify with issuing authorities',
                ],
                'ais' => [
                    'priority' => $rawScore < 30 ? 2 : 3,
                    'section' => 'ais',
                    'recommendation' => 'Vessel tracking data incomplete; manual verification recommended',
                    'action' => 'Cross-reference contracts with AIS data or request supporting documentation',
                ],
                default => null,
            };

            if ($rec) {
                $recommendations[] = $rec;
            }
        }

        // Sort by priority
        usort($recommendations, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return $recommendations;
    }
}
