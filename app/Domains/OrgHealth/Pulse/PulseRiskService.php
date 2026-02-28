<?php

namespace App\Domains\OrgHealth\Pulse;

use App\Models\OrgPulseProfile;
use App\Models\OrgPulseRiskSnapshot;
use Carbon\Carbon;

class PulseRiskService
{
    /**
     * Risk driver definitions: key => [condition callback, weight].
     * Trend-based drivers use previous profiles passed separately.
     */
    public function computeRisk(OrgPulseProfile $profile, string $tenantId, string $employeeId): OrgPulseRiskSnapshot
    {
        // Fetch previous profiles for trend comparison (ordered newest first, excluding current)
        $previousProfiles = OrgPulseProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('id', '!=', $profile->id)
            ->orderByDesc('computed_at')
            ->limit(10)
            ->get();

        $prev = $previousProfiles->first();

        $triggeredDrivers = [];
        $totalWeight = 0;

        // 1. stay_intent_low: retention_intent_score < 40 → +30
        if ($profile->retention_intent_score < 40) {
            $triggeredDrivers[] = 'stay_intent_low';
            $totalWeight += 30;
        }

        // 2. stay_intent_declining: retention_intent dropped >=20pts from previous → +15
        if ($prev && ($prev->retention_intent_score - $profile->retention_intent_score) >= 20) {
            $triggeredDrivers[] = 'stay_intent_declining';
            $totalWeight += 15;
        }

        // 3. motivation_low: engagement_score < 40 → +20
        if ($profile->engagement_score < 40) {
            $triggeredDrivers[] = 'motivation_low';
            $totalWeight += 20;
        }

        // 4. motivation_declining: engagement dropped >=15pts from previous → +10
        if ($prev && ($prev->engagement_score - $profile->engagement_score) >= 15) {
            $triggeredDrivers[] = 'motivation_declining';
            $totalWeight += 10;
        }

        // 5. burnout_high: burnout_proxy >= 60 → +15
        if ($profile->burnout_proxy >= 60) {
            $triggeredDrivers[] = 'burnout_high';
            $totalWeight += 15;
        }

        // 6. wellbeing_low: wellbeing_score < 40 → +10
        if ($profile->wellbeing_score < 40) {
            $triggeredDrivers[] = 'wellbeing_low';
            $totalWeight += 10;
        }

        // 7. growth_stagnant: growth_score < 30 → +10
        if ($profile->growth_score < 30) {
            $triggeredDrivers[] = 'growth_stagnant';
            $totalWeight += 10;
        }

        // 8. alignment_weak: alignment_score < 35 → +10
        if ($profile->alignment_score < 35) {
            $triggeredDrivers[] = 'alignment_weak';
            $totalWeight += 10;
        }

        // 9. overall_low: overall_score < 35 → +10
        if ($profile->overall_score < 35) {
            $triggeredDrivers[] = 'overall_low';
            $totalWeight += 10;
        }

        // 10. consecutive_decline: overall dropped 3 consecutive pulses → +15
        if ($previousProfiles->count() >= 2) {
            $scores = collect([$profile->overall_score]);
            foreach ($previousProfiles->take(2) as $pp) {
                $scores->push($pp->overall_score);
            }
            // Check if each is lower than the next (3 consecutive declines)
            if ($scores->count() >= 3 &&
                $scores[0] < $scores[1] &&
                $scores[1] < $scores[2]) {
                $triggeredDrivers[] = 'consecutive_decline';
                $totalWeight += 15;
            }
        }

        $riskScore = min($totalWeight, 100);

        if ($riskScore >= 65) {
            $riskLevel = 'elevated';
        } elseif ($riskScore >= 40) {
            $riskLevel = 'moderate';
        } else {
            $riskLevel = 'low';
        }

        return OrgPulseRiskSnapshot::create([
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'pulse_profile_id' => $profile->id,
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'drivers' => $triggeredDrivers,
            'suggestions' => null,
            'computed_at' => Carbon::now(),
        ]);
    }
}
