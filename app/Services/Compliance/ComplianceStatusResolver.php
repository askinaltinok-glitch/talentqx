<?php

namespace App\Services\Compliance;

use App\Models\CandidateTrustProfile;

class ComplianceStatusResolver
{
    public function resolve(int $score, array $sectionScores, CandidateTrustProfile $profile): ComplianceStatusResult
    {
        $flags = [];

        // Check critical flags
        $hasCritical = false;
        $hasWarning = false;

        // Critical: risk_tier === 'critical'
        if ($profile->risk_tier === CandidateTrustProfile::RISK_CRITICAL) {
            $flags[] = ['flag' => 'critical_risk_tier', 'severity' => 'critical', 'detail' => 'Risk tier is critical'];
            $hasCritical = true;
        }

        // Critical: cri_score < 30
        if ($profile->cri_score !== null && $profile->cri_score < 30) {
            $flags[] = ['flag' => 'very_low_cri', 'severity' => 'critical', 'detail' => 'CRI score below 30'];
            $hasCritical = true;
        }

        // Critical: STCW compliance_ratio < 0.3
        $detail = $profile->detail_json ?? [];
        $stcwRatio = $detail['rank_stcw']['stcw_compliance']['compliance_ratio'] ?? null;
        if ($stcwRatio !== null && $stcwRatio < 0.3) {
            $flags[] = ['flag' => 'very_low_stcw', 'severity' => 'critical', 'detail' => 'STCW compliance ratio below 30%'];
            $hasCritical = true;
        }

        // Warning: risk_tier === 'high'
        if ($profile->risk_tier === CandidateTrustProfile::RISK_HIGH) {
            $flags[] = ['flag' => 'high_risk_tier', 'severity' => 'warning', 'detail' => 'Risk tier is high'];
            $hasWarning = true;
        }

        // Warning: rank anomaly flag
        if ($profile->rank_anomaly_flag) {
            $flags[] = ['flag' => 'rank_anomaly', 'severity' => 'warning', 'detail' => 'Rank progression anomaly detected'];
            $hasWarning = true;
        }

        // Warning: STCW missing certs > 2
        $missingCerts = $detail['rank_stcw']['stcw_compliance']['missing_count'] ?? 0;
        if ($missingCerts > 2) {
            $flags[] = ['flag' => 'many_missing_certs', 'severity' => 'warning', 'detail' => "Missing {$missingCerts} STCW certificates"];
            $hasWarning = true;
        }

        // Determine status
        if ($hasCritical || $score < 50) {
            $status = CandidateTrustProfile::COMPLIANCE_NOT_COMPLIANT;
        } elseif ($hasWarning || $score < 70) {
            $status = CandidateTrustProfile::COMPLIANCE_NEEDS_REVIEW;
        } else {
            $status = CandidateTrustProfile::COMPLIANCE_COMPLIANT;
        }

        return new ComplianceStatusResult(
            status: $status,
            flags: $flags,
            recommendations: [],
        );
    }
}
