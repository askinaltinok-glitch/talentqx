<?php

namespace App\Services\Ais;

class ConfidenceResult
{
    public float $score;
    /** @var array<array{code: string, weight: float, detail: string}> */
    public array $reasons;
    /** @var array<array{type: string, detail: string, severity: string}> */
    public array $anomalies;
    public string $status;

    public function __construct(float $score, array $reasons, array $anomalies, string $status)
    {
        $this->score = $score;
        $this->reasons = $reasons;
        $this->anomalies = $anomalies;
        $this->status = $status;
    }
}
