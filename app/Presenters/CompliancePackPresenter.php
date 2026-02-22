<?php

namespace App\Presenters;

use App\Models\CandidateTrustProfile;

class CompliancePackPresenter
{
    public static function fromTrustProfile(?CandidateTrustProfile $trustProfile): ?array
    {
        if (!$trustProfile) {
            return null;
        }

        $detail = $trustProfile->detail_json ?? [];
        $compliancePack = $detail['compliance_pack'] ?? null;

        if (!$compliancePack) {
            return null;
        }

        return [
            'score' => $trustProfile->compliance_score,
            'status' => $trustProfile->compliance_status,
            'section_scores' => $compliancePack['section_scores'] ?? [],
            'available_sections' => $compliancePack['available_sections'] ?? 0,
            'flags' => $compliancePack['flags'] ?? [],
            'recommendations' => $compliancePack['recommendations'] ?? [],
            'computed_at' => $trustProfile->compliance_computed_at?->toIso8601String(),
        ];
    }
}
