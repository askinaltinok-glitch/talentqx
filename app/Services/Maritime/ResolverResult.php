<?php

namespace App\Services\Maritime;

/**
 * DTO for Resolver Engine output.
 *
 * Immutable after construction.
 */
class ResolverResult
{
    public function __construct(
        public readonly string $commandClass,
        public readonly float $confidence,
        public readonly array $scenarios,
        public readonly bool $needsReview,
        public readonly ?string $secondaryClass,
        public readonly ?string $resolverStatus,
        public readonly array $flags = [],
    ) {}

    public function toArray(): array
    {
        return [
            'command_class' => $this->commandClass,
            'confidence' => $this->confidence,
            'scenarios' => array_map(fn($s) => [
                'id' => $s->id,
                'scenario_code' => $s->scenario_code,
                'slot' => $s->slot,
                'domain' => $s->domain,
                'primary_capability' => $s->primary_capability,
                'difficulty_tier' => $s->difficulty_tier,
            ], $this->scenarios),
            'needs_review' => $this->needsReview,
            'secondary_class' => $this->secondaryClass,
            'resolver_status' => $this->resolverStatus,
            'flags' => $this->flags,
        ];
    }
}
