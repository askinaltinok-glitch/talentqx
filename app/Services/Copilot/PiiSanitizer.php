<?php

namespace App\Services\Copilot;

/**
 * PII Sanitizer for KVKK Compliance
 *
 * Removes personally identifiable information patterns from text output.
 * Used as an additional safety layer on LLM responses.
 */
class PiiSanitizer
{
    /**
     * Patterns that might indicate PII leakage in AI responses.
     */
    private static array $patterns = [
        // Email addresses
        '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL REDACTED]',

        // Phone numbers (various formats)
        '/\b(?:\+90|0090|90)?[-.\s]?(?:\d{3})[-.\s]?(?:\d{3})[-.\s]?(?:\d{2})[-.\s]?(?:\d{2})\b/' => '[PHONE REDACTED]',
        '/\b\d{3}[-.\s]?\d{3}[-.\s]?\d{4}\b/' => '[PHONE REDACTED]',

        // Turkish TC Kimlik No (11-digit ID)
        '/\b[1-9]\d{10}\b/' => '[TC NO REDACTED]',

        // Credit card numbers (basic patterns)
        '/\b(?:\d{4}[-.\s]?){3}\d{4}\b/' => '[CARD REDACTED]',

        // IBAN numbers
        '/\bTR\d{2}[-.\s]?\d{4}[-.\s]?\d{4}[-.\s]?\d{4}[-.\s]?\d{4}[-.\s]?\d{2}\b/i' => '[IBAN REDACTED]',

        // IP addresses (optional - may be useful in some contexts)
        // '/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/' => '[IP REDACTED]',
    ];

    /**
     * Sanitize text by removing potential PII patterns.
     *
     * @param string $text Input text to sanitize
     * @return string Sanitized text
     */
    public static function sanitizeText(string $text): string
    {
        foreach (self::$patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
    }

    /**
     * Check if text contains potential PII.
     *
     * @param string $text Input text to check
     * @return bool True if PII detected
     */
    public static function containsPii(string $text): bool
    {
        foreach (self::$patterns as $pattern => $replacement) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of detected PII types in text.
     *
     * @param string $text Input text to analyze
     * @return array List of detected PII type names
     */
    public static function detectPiiTypes(string $text): array
    {
        $detected = [];

        $typeNames = [
            'email' => '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/',
            'phone' => '/\b(?:\+90|0090|90)?[-.\s]?(?:\d{3})[-.\s]?(?:\d{3})[-.\s]?(?:\d{2})[-.\s]?(?:\d{2})\b/',
            'tc_no' => '/\b[1-9]\d{10}\b/',
            'credit_card' => '/\b(?:\d{4}[-.\s]?){3}\d{4}\b/',
            'iban' => '/\bTR\d{2}[-.\s]?\d{4}[-.\s]?\d{4}[-.\s]?\d{4}[-.\s]?\d{4}[-.\s]?\d{2}\b/i',
        ];

        foreach ($typeNames as $type => $pattern) {
            if (preg_match($pattern, $text)) {
                $detected[] = $type;
            }
        }

        return $detected;
    }
}
