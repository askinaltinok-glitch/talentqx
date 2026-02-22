<?php

namespace App\Services\Decision;

class RationaleBuilder
{
    /**
     * Build rationale array for all engines.
     *
     * @param array $detail  CandidateTrustProfile->detail_json
     * @param array $execSummary  The full exec summary array (with scores, correlation, predictive_risk)
     * @return array  Indexed array of engine rationale objects
     */
    public function build(array $detail, array $execSummary): array
    {
        return array_values(array_filter([
            $this->verification($execSummary),
            $this->technical($detail, $execSummary),
            $this->stability($detail, $execSummary),
            $this->compliance($detail, $execSummary),
            $this->competency($detail, $execSummary),
            $this->correlation($execSummary),
            $this->predictiveRisk($execSummary),
        ]));
    }

    private function verification(array $execSummary): ?array
    {
        $v = $execSummary['scores']['verification'] ?? null;
        if (!$v || $v['confidence_score'] === null) {
            return null;
        }

        $pct = round($v['confidence_score'] * 100);
        $anomalies = $v['anomaly_count'] ?? 0;

        $topReason = "Average AIS confidence {$pct}%";
        $evidence = [];
        $recommendations = [];

        if ($anomalies === 0) {
            $evidence[] = 'Zero anomalies detected';
        } else {
            $evidence[] = "{$anomalies} anomaly(ies) detected";
        }

        if ($v['confidence_score'] < 0.7) {
            $recommendations[] = 'Verify unmatched contracts via AIS data';
        }

        return [
            'engine' => 'verification',
            'label' => 'Vessel Verification',
            'top_reason' => $topReason,
            'evidence' => $evidence,
            'recommendations' => $recommendations,
            'confidence_note' => "Based on AIS verification data",
        ];
    }

    private function technical(array $detail, array $execSummary): ?array
    {
        $t = $execSummary['scores']['technical'] ?? null;
        if (!$t || $t['technical_score'] === null) {
            return null;
        }

        $rankStcw = $detail['rank_stcw'] ?? [];
        $stcwCompliance = $rankStcw['stcw_compliance'] ?? [];
        $complianceRatio = $stcwCompliance['compliance_ratio'] ?? null;
        $missingCount = (int) ($stcwCompliance['missing_count'] ?? 0);
        $expiredCerts = $stcwCompliance['expired_certs'] ?? [];
        $expiredCount = count($expiredCerts);

        $techPct = round($t['technical_score'] * 100);
        $stcwPct = $complianceRatio !== null ? round($complianceRatio * 100) : null;

        $topReason = "Technical readiness {$techPct}%";
        if ($stcwPct !== null) {
            $topReason .= " with {$stcwPct}% STCW compliance";
        }

        $evidence = [];
        // Use exec summary missing_cert_count as fallback (includes missing + expired)
        $execMissing = $t['missing_cert_count'] ?? 0;
        if ($missingCount > 0) {
            $evidence[] = "{$missingCount} missing STCW certificate(s)";
        } elseif ($execMissing > 0 && $missingCount === 0) {
            $evidence[] = "{$execMissing} missing/expired STCW certificate(s)";
        }
        if ($expiredCount > 0) {
            $evidence[] = "{$expiredCount} expired certificate(s)";
        }
        if ($missingCount === 0 && $expiredCount === 0 && $execMissing === 0 && $complianceRatio !== null && $complianceRatio >= 1.0) {
            $evidence[] = "STCW fully compliant";
        }
        if (empty($evidence) && $complianceRatio !== null && $complianceRatio < 1.0) {
            $totalRequired = (int) ($stcwCompliance['total_required'] ?? 0);
            $totalHeld = (int) ($stcwCompliance['total_held'] ?? 0);
            $evidence[] = "STCW: {$totalHeld}/{$totalRequired} certificates held ({$stcwPct}% compliant)";
        }

        $recommendations = [];
        if ($expiredCount > 0) {
            $recommendations[] = "Renew {$expiredCount} expired certificate(s)";
        }
        if ($missingCount > 0) {
            $recommendations[] = "Obtain {$missingCount} missing STCW certificate(s)";
        }
        if ($complianceRatio !== null && $complianceRatio < 0.7 && $missingCount === 0 && $expiredCount === 0) {
            $recommendations[] = "Obtain required STCW certificates to improve compliance";
        }

        return [
            'engine' => 'technical',
            'label' => 'Technical Readiness',
            'top_reason' => $topReason,
            'evidence' => $evidence,
            'recommendations' => $recommendations,
            'confidence_note' => "Based on rank/STCW analysis",
        ];
    }

    private function stability(array $detail, array $execSummary): ?array
    {
        $s = $execSummary['scores']['stability_risk'] ?? null;
        if (!$s || $s['risk_tier'] === null) {
            return null;
        }

        $stabilityData = $detail['stability_risk'] ?? [];
        $contractSummary = $stabilityData['contract_summary'] ?? [];
        $flags = $stabilityData['flags'] ?? [];

        $tier = ucfirst($s['risk_tier']);
        $avgMonths = $contractSummary['avg_duration_months'] ?? null;
        $totalContracts = $contractSummary['total_contracts'] ?? null;
        $totalGapMonths = $contractSummary['total_gap_months'] ?? 0;

        $topReason = "{$tier} risk tier";
        if ($avgMonths !== null && $totalContracts !== null) {
            $topReason .= " — avg " . round($avgMonths, 1) . " months per contract across {$totalContracts} contracts";
        }

        $evidence = [];
        foreach (array_slice($flags, 0, 3) as $flag) {
            $evidence[] = str_replace('_', ' ', ucfirst($flag));
        }
        if (empty($evidence) && $s['risk_tier'] === 'low') {
            $evidence[] = 'Stable employment pattern';
        }

        $recommendations = [];
        if ($totalGapMonths > 6) {
            $recommendations[] = 'Document reasons for career gap(s)';
        }
        if (($contractSummary['short_contract_ratio'] ?? 0) > 0.5) {
            $recommendations[] = 'Demonstrate longer contract commitments';
        }

        return [
            'engine' => 'stability',
            'label' => 'Stability & Risk',
            'top_reason' => $topReason,
            'evidence' => $evidence,
            'recommendations' => $recommendations,
            'confidence_note' => "Based on career pattern analysis",
        ];
    }

    private function compliance(array $detail, array $execSummary): ?array
    {
        $c = $execSummary['scores']['compliance'] ?? null;
        if (!$c || $c['compliance_score'] === null) {
            return null;
        }

        $pack = $detail['compliance_pack'] ?? [];
        $status = $c['compliance_status'] ?? 'unknown';
        $criticalCount = $c['critical_flag_count'] ?? 0;
        $packFlags = $pack['flags'] ?? [];
        $packRecommendations = $pack['recommendations'] ?? [];

        $topReason = "Compliance score {$c['compliance_score']}/100 (" . str_replace('_', ' ', $status) . ")";

        $evidence = [];
        if ($criticalCount > 0) {
            $evidence[] = "{$criticalCount} critical flag(s) detected";
        }
        // Add top non-critical flags
        foreach (array_slice($packFlags, 0, 2) as $flag) {
            $detail_text = $flag['detail'] ?? $flag['flag'] ?? '';
            if ($detail_text && count($evidence) < 3) {
                $evidence[] = $detail_text;
            }
        }
        if (empty($evidence)) {
            $evidence[] = 'No compliance issues detected';
        }

        $recommendations = [];
        foreach (array_slice($packRecommendations, 0, 2) as $rec) {
            $recommendations[] = $rec['recommendation'] ?? $rec['action'] ?? '';
        }
        $recommendations = array_filter($recommendations);

        return [
            'engine' => 'compliance',
            'label' => 'Compliance',
            'top_reason' => $topReason,
            'evidence' => array_values($evidence),
            'recommendations' => array_values($recommendations),
            'confidence_note' => "Based on compliance pack analysis",
        ];
    }

    private function competency(array $detail, array $execSummary): ?array
    {
        $comp = $execSummary['scores']['competency'] ?? null;
        if (!$comp || $comp['competency_score'] === null) {
            return null;
        }

        $competencyData = $detail['competency_engine'] ?? [];
        $evidenceSummary = $competencyData['evidence_summary'] ?? [];
        $strengths = $evidenceSummary['strengths'] ?? [];
        $concerns = $evidenceSummary['concerns'] ?? [];

        $score = $comp['competency_score'];
        $topReason = "Score {$score}/100";
        if (!empty($strengths)) {
            $topReason .= " — strongest in " . ($strengths[0] ?? '');
        }
        if (!empty($concerns)) {
            $topReason .= ", concern in " . ($concerns[0] ?? '');
        }

        $evidence = [];
        $evidenceBullets = $evidenceSummary['evidence_bullets'] ?? [];
        foreach (array_slice($evidenceBullets, 0, 3) as $bullet) {
            $evidence[] = $bullet;
        }
        if (empty($evidence)) {
            foreach (array_slice($strengths, 0, 2) as $s) {
                $evidence[] = $s;
            }
        }

        $recommendations = [];
        foreach (array_slice($concerns, 0, 2) as $concern) {
            $recommendations[] = "Address: " . $concern;
        }

        return [
            'engine' => 'competency',
            'label' => 'Competency',
            'top_reason' => $topReason,
            'evidence' => $evidence,
            'recommendations' => $recommendations,
            'confidence_note' => "Based on competency assessment",
        ];
    }

    private function correlation(array $execSummary): ?array
    {
        $corr = $execSummary['correlation'] ?? null;
        if (!$corr || empty($corr['correlation_flags'])) {
            return null;
        }

        $flags = $corr['correlation_flags'];
        $summary = $corr['correlation_summary'] ?? '';

        $topReason = $summary ?: (count($flags) . " cross-engine correlation flag(s) detected");

        $evidence = [];
        foreach (array_slice($flags, 0, 3) as $flag) {
            $evidence[] = $flag['detail'] ?? $flag['flag'];
        }

        return [
            'engine' => 'correlation',
            'label' => 'Behavioral Intelligence',
            'top_reason' => $topReason,
            'evidence' => $evidence,
            'recommendations' => [],
            'confidence_note' => "Cross-engine correlation analysis",
        ];
    }

    private function predictiveRisk(array $execSummary): ?array
    {
        $pred = $execSummary['predictive_risk'] ?? null;
        if (!$pred || $pred['predictive_risk_index'] === null) {
            return null;
        }

        $pri = round($pred['predictive_risk_index']);
        $tier = $pred['predictive_tier'] ?? 'unknown';
        $reasonChain = $pred['reason_chain'] ?? [];
        $triggeredPatterns = $pred['triggered_patterns'] ?? [];

        $topReason = !empty($reasonChain)
            ? $reasonChain[0]
            : "Predictive risk index {$pri}/100 ({$tier})";

        $evidence = [];
        foreach (array_slice($triggeredPatterns, 0, 3) as $pattern) {
            $evidence[] = $pattern['reason'] ?? $pattern['pattern'];
        }

        $recommendations = [];
        // Derive recommendation from dominant pattern
        if (!empty($triggeredPatterns)) {
            $dominant = $triggeredPatterns[0]['pattern'] ?? '';
            $recMap = [
                'gap_growth' => 'Address career gaps to reduce risk trajectory',
                'switching_acceleration' => 'Maintain stable employment to counter switching acceleration',
                'behavioral_technical_mismatch' => 'Align technical credentials with competency evidence',
                'risk_compounding' => 'Address multiple risk factors simultaneously',
                'tenure_decay' => 'Demonstrate longer-term commitments',
            ];
            if (isset($recMap[$dominant])) {
                $recommendations[] = $recMap[$dominant];
            }
        }

        return [
            'engine' => 'predictive_risk',
            'label' => 'Predictive Risk',
            'top_reason' => $topReason,
            'evidence' => $evidence,
            'recommendations' => $recommendations,
            'confidence_note' => "Trend-based risk projection",
        ];
    }
}
