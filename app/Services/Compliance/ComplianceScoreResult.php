<?php

namespace App\Services\Compliance;

class ComplianceScoreResult
{
    public function __construct(
        public int $score,
        public array $sectionScores,
        public int $availableSections,
        public float $effectiveWeightSum,
    ) {}

    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'section_scores' => $this->sectionScores,
            'available_sections' => $this->availableSections,
            'effective_weight_sum' => $this->effectiveWeightSum,
        ];
    }
}
