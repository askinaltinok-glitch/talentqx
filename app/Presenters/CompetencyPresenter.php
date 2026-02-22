<?php

namespace App\Presenters;

use App\Models\CandidateTrustProfile;

class CompetencyPresenter
{
    /**
     * Present competency data from trust profile.
     */
    public static function fromTrustProfile(?CandidateTrustProfile $trustProfile): ?array
    {
        if (!$trustProfile) {
            return null;
        }

        $detail = $trustProfile->detail_json ?? [];
        $competency = $detail['competency_engine'] ?? null;

        if (!$competency) {
            return null;
        }

        return [
            'score_total' => $competency['score_total'] ?? null,
            'status' => $competency['status'] ?? null,
            'score_by_dimension' => $competency['score_by_dimension'] ?? [],
            'flags' => $competency['flags'] ?? [],
            'evidence_summary' => $competency['evidence_summary'] ?? [],
            'questions_evaluated' => $competency['questions_evaluated'] ?? 0,
            'computed_at' => $competency['computed_at'] ?? null,
        ];
    }
}
