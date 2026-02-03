<?php

namespace App\Services\Copilot;

class GuardrailResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly ?string $reason = null,
        public readonly ?string $alternativeResponse = null
    ) {}

    public static function allowed(): self
    {
        return new self(true);
    }

    public static function blocked(string $reason, ?string $alternativeResponse = null): self
    {
        return new self(false, $reason, $alternativeResponse);
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function isBlocked(): bool
    {
        return !$this->allowed;
    }
}
