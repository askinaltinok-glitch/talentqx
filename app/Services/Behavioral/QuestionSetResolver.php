<?php

namespace App\Services\Behavioral;

use App\Models\InterviewQuestionSet;

/**
 * Resolves the best-matching question set for a candidate.
 *
 * Fallback order:
 * 1. Exact match: position_code + country_code + locale
 * 2. Country-agnostic: position_code + locale (country_code = null)
 * 3. Generic position: __generic__ + locale
 * 4. EN fallback: __generic__ + en
 */
class QuestionSetResolver
{
    public function resolve(
        string $positionCode,
        string $locale,
        ?string $countryCode = null,
        string $code = 'maritime_behavioral_v2',
    ): ?InterviewQuestionSet {
        // 1. Exact match
        if ($countryCode) {
            $set = $this->find($code, $positionCode, $locale, $countryCode);
            if ($set) {
                return $set;
            }
        }

        // 2. Country-agnostic with position
        $set = $this->find($code, $positionCode, $locale, null);
        if ($set) {
            return $set;
        }

        // 3. Generic position + locale
        $set = $this->find($code, '__generic__', $locale, null);
        if ($set) {
            return $set;
        }

        // 4. EN fallback
        return $this->find($code, '__generic__', 'en', null);
    }

    private function find(string $code, string $positionCode, string $locale, ?string $countryCode): ?InterviewQuestionSet
    {
        return InterviewQuestionSet::active()
            ->where('code', $code)
            ->where('position_code', $positionCode)
            ->where('locale', $locale)
            ->where('country_code', $countryCode)
            ->orderByDesc('version')
            ->first();
    }
}
