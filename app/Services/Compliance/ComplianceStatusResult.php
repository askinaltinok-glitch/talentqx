<?php

namespace App\Services\Compliance;

class ComplianceStatusResult
{
    public function __construct(
        public string $status,
        public array $flags,
        public array $recommendations,
    ) {}

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'flags' => $this->flags,
            'recommendations' => $this->recommendations,
        ];
    }
}
