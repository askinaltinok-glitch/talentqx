<?php

namespace App\Services\Decision;

class WhatIfSimulator
{
    /**
     * Simulate fastest paths to decision improvement.
     * Deterministic rules engine — never uses AI/LLM.
     *
     * @param array $execSummary  Full exec summary (scores, correlation, predictive_risk)
     * @param array $detail       CandidateTrustProfile->detail_json
     * @return array  Up to 3 action items, sorted by estimated impact
     */
    public function simulate(array $execSummary, array $detail): array
    {
        $actions = [];

        $actions = array_merge($actions, $this->checkMissingStcw($detail));
        $actions = array_merge($actions, $this->checkExpiredCerts($detail));
        $actions = array_merge($actions, $this->checkCriticalComplianceFlags($execSummary, $detail));
        $actions = array_merge($actions, $this->checkNoCompetency($execSummary));
        $actions = array_merge($actions, $this->checkUnverifiedContracts($execSummary));
        $actions = array_merge($actions, $this->checkCareerGaps($detail));
        $actions = array_merge($actions, $this->checkLowComplianceSections($detail));
        $actions = array_merge($actions, $this->checkPredictivePatterns($execSummary));

        // Sort by impact: high > medium > low
        $impactOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
        usort($actions, fn($a, $b) =>
            ($impactOrder[$a['estimated_impact']] ?? 3) <=> ($impactOrder[$b['estimated_impact']] ?? 3)
        );

        return array_slice($actions, 0, 3);
    }

    private function checkMissingStcw(array $detail): array
    {
        $rankStcw = $detail['rank_stcw'] ?? null;
        if (!$rankStcw) return [];

        $stcw = $rankStcw['stcw_compliance'] ?? [];
        $missingCount = (int) ($stcw['missing_count'] ?? 0);
        if ($missingCount <= 0) return [];

        $ratio = $stcw['compliance_ratio'] ?? 0;
        $totalRequired = (int) ($stcw['total_required'] ?? 0);
        $currentPct = round($ratio * 100);
        $projectedRatio = $totalRequired > 0
            ? min(1.0, ($stcw['total_held'] ?? 0) + $missingCount) / $totalRequired
            : 1.0;
        $projectedPct = round($projectedRatio * 100);

        return [[
            'action' => "Obtain {$missingCount} missing STCW certificate(s)",
            'engine' => 'technical',
            'estimated_impact' => 'high',
            'current_state' => "STCW compliance at {$currentPct}%",
            'projected_state' => "Would reach ~{$projectedPct}% compliance",
        ]];
    }

    private function checkExpiredCerts(array $detail): array
    {
        $rankStcw = $detail['rank_stcw'] ?? null;
        if (!$rankStcw) return [];

        $expiredCerts = $rankStcw['stcw_compliance']['expired_certs'] ?? [];
        $expiredCount = count($expiredCerts);
        if ($expiredCount <= 0) return [];

        $stcw = $rankStcw['stcw_compliance'] ?? [];
        $ratio = $stcw['compliance_ratio'] ?? 0;
        $currentPct = round($ratio * 100);

        return [[
            'action' => "Renew {$expiredCount} expired certificate(s)",
            'engine' => 'technical',
            'estimated_impact' => 'high',
            'current_state' => "STCW compliance at {$currentPct}%",
            'projected_state' => "Would improve STCW compliance after renewal",
        ]];
    }

    private function checkCriticalComplianceFlags(array $execSummary, array $detail): array
    {
        $criticalCount = $execSummary['scores']['compliance']['critical_flag_count'] ?? 0;
        if ($criticalCount <= 0) return [];

        $score = $execSummary['scores']['compliance']['compliance_score'] ?? 0;

        return [[
            'action' => "Resolve {$criticalCount} critical compliance flag(s)",
            'engine' => 'compliance',
            'estimated_impact' => 'high',
            'current_state' => "Compliance score {$score}/100 with {$criticalCount} critical flag(s)",
            'projected_state' => "Would significantly improve compliance status",
        ]];
    }

    private function checkNoCompetency(array $execSummary): array
    {
        $compScore = $execSummary['scores']['competency']['competency_score'] ?? null;
        if ($compScore !== null) return [];

        return [[
            'action' => 'Complete competency interview',
            'engine' => 'competency',
            'estimated_impact' => 'high',
            'current_state' => 'No competency assessment available',
            'projected_state' => 'Would enable competency scoring and improve decision confidence',
        ]];
    }

    private function checkUnverifiedContracts(array $execSummary): array
    {
        $v = $execSummary['scores']['verification'] ?? [];
        $confidence = $v['confidence_score'] ?? null;
        $anomalies = $v['anomaly_count'] ?? 0;

        if ($confidence !== null && $confidence >= 0.7 && $anomalies === 0) return [];
        if ($confidence === null) return [];

        $pct = round($confidence * 100);

        return [[
            'action' => 'Verify unmatched contracts via AIS data',
            'engine' => 'verification',
            'estimated_impact' => 'medium',
            'current_state' => "Verification confidence at {$pct}%",
            'projected_state' => 'Would increase verification confidence',
        ]];
    }

    private function checkCareerGaps(array $detail): array
    {
        $stability = $detail['stability_risk'] ?? [];
        $totalGapMonths = $stability['contract_summary']['total_gap_months'] ?? 0;
        if ($totalGapMonths <= 6) return [];

        $gapMonths = round($totalGapMonths, 1);

        return [[
            'action' => 'Document reasons for career gap(s)',
            'engine' => 'stability',
            'estimated_impact' => 'medium',
            'current_state' => "{$gapMonths} months total career gap",
            'projected_state' => 'Would improve risk tier assessment',
        ]];
    }

    private function checkLowComplianceSections(array $detail): array
    {
        $pack = $detail['compliance_pack'] ?? null;
        if (!$pack) return [];

        $available = $pack['available_sections'] ?? 5;
        $remaining = 5 - $available;
        if ($remaining <= 0) return [];

        $score = $pack['score'] ?? 0;

        return [[
            'action' => "Complete {$remaining} remaining compliance section(s)",
            'engine' => 'compliance',
            'estimated_impact' => 'medium',
            'current_state' => "Compliance based on {$available}/5 sections (score: {$score})",
            'projected_state' => 'Would provide more comprehensive compliance assessment',
        ]];
    }

    private function checkPredictivePatterns(array $execSummary): array
    {
        $pred = $execSummary['predictive_risk'] ?? [];
        $patterns = $pred['triggered_patterns'] ?? [];
        if (empty($patterns)) return [];

        $dominant = $patterns[0]['pattern'] ?? 'unknown';
        $reason = $patterns[0]['reason'] ?? '';

        $actionMap = [
            'gap_growth' => 'Address career gaps to reduce risk trajectory',
            'switching_acceleration' => 'Maintain stable employment to counter switching acceleration',
            'behavioral_technical_mismatch' => 'Align technical credentials with competency evidence',
            'risk_compounding' => 'Address multiple risk factors simultaneously',
            'tenure_decay' => 'Demonstrate longer-term contract commitments',
        ];

        $action = $actionMap[$dominant] ?? "Address predictive risk pattern: {$dominant}";
        $pri = round($pred['predictive_risk_index'] ?? 0);
        $tier = $pred['predictive_tier'] ?? 'unknown';

        return [[
            'action' => $action,
            'engine' => 'predictive_risk',
            'estimated_impact' => 'low',
            'current_state' => "Predictive risk {$pri}/100 ({$tier})",
            'projected_state' => 'Would reduce future risk trajectory',
        ]];
    }
}
