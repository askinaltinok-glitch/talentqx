<?php

namespace App\Services\Copilot;

/**
 * Guardrail Result with locale-independent status codes.
 *
 * Status codes are used for API responses to enable frontend localization.
 */
class GuardrailResult
{
    /**
     * Standard status codes for blocked reasons.
     */
    public const STATUS_ALLOWED = 'ALLOWED';
    public const STATUS_NO_INTERVIEW = 'NO_INTERVIEW';
    public const STATUS_DISALLOWED_TOPIC = 'DISALLOWED_TOPIC';
    public const STATUS_ASSUMPTION_LANGUAGE = 'ASSUMPTION_LANGUAGE';
    public const STATUS_HIRING_DECISION = 'HIRING_DECISION';
    public const STATUS_SALARY_RECOMMENDATION = 'SALARY_RECOMMENDATION';
    public const STATUS_DISCRIMINATION = 'DISCRIMINATION';
    public const STATUS_LEGAL_ADVICE = 'LEGAL_ADVICE';
    public const STATUS_PII_INFERENCE = 'PII_INFERENCE';
    public const STATUS_EMPTY_INPUT = 'EMPTY_INPUT';
    public const STATUS_INPUT_TOO_LONG = 'INPUT_TOO_LONG';
    public const STATUS_KVKK_VIOLATION = 'KVKK_VIOLATION';
    public const STATUS_INSUFFICIENT_EVIDENCE = 'INSUFFICIENT_EVIDENCE';
    public const STATUS_INTERNAL_ERROR = 'INTERNAL_ERROR';

    /**
     * Minimum requirements for analysis.
     */
    public const MIN_ANSWERED_QUESTIONS = 3;
    public const MIN_ANSWER_CHARS = 40;

    public function __construct(
        public readonly bool $allowed,
        public readonly ?string $reason = null,
        public readonly ?string $statusCode = null,
        public readonly ?string $alternativeResponse = null
    ) {}

    public static function allowed(): self
    {
        return new self(true, null, self::STATUS_ALLOWED);
    }

    /**
     * Create a blocked result with status code.
     *
     * @param string $reason Human-readable reason (for logging)
     * @param string|null $statusCode Machine-readable status code (for API)
     * @param string|null $alternativeResponse Alternative response text
     */
    public static function blocked(string $reason, ?string $statusCode = null, ?string $alternativeResponse = null): self
    {
        return new self(false, $reason, $statusCode ?? self::mapReasonToStatusCode($reason), $alternativeResponse);
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function isBlocked(): bool
    {
        return !$this->allowed;
    }

    /**
     * Get the status code for API response.
     */
    public function getStatusCode(): string
    {
        return $this->statusCode ?? self::STATUS_ALLOWED;
    }

    /**
     * Map legacy reason strings to status codes.
     */
    public static function mapReasonToStatusCode(string $reason): string
    {
        $mapping = [
            'hiring_decision' => self::STATUS_HIRING_DECISION,
            'salary_recommendation' => self::STATUS_SALARY_RECOMMENDATION,
            'discrimination' => self::STATUS_DISCRIMINATION,
            'legal_advice' => self::STATUS_LEGAL_ADVICE,
            'pii_inference' => self::STATUS_PII_INFERENCE,
            'empty_input' => self::STATUS_EMPTY_INPUT,
            'input_too_long' => self::STATUS_INPUT_TOO_LONG,
            'disallowed_topic' => self::STATUS_DISALLOWED_TOPIC,
            'assumption_language' => self::STATUS_ASSUMPTION_LANGUAGE,
            'no_interview' => self::STATUS_NO_INTERVIEW,
            'kvkk_violation' => self::STATUS_KVKK_VIOLATION,
            'insufficient_evidence' => self::STATUS_INSUFFICIENT_EVIDENCE,
        ];

        return $mapping[$reason] ?? self::STATUS_INTERNAL_ERROR;
    }

    /**
     * Convert to array for API response.
     */
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'blocked' => !$this->allowed,
            'status_code' => $this->getStatusCode(),
            'reason' => $this->reason,
        ];
    }
}
