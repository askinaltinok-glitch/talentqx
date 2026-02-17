<?php

namespace App\Services\Assessment;

use Illuminate\Support\Facades\Log;

class JsonSchemaValidator
{
    private array $errors = [];

    /**
     * Required fields for assessment analysis response
     */
    private const REQUIRED_FIELDS = [
        'overall_score' => 'numeric',
        'competency_scores' => 'array',
        'risk_flags' => 'array',
        'risk_level' => 'string',
        'level_numeric' => 'integer',
        'level_label' => 'string',
        'strengths' => 'array',
        'improvement_areas' => 'array',
        'development_plan' => 'array',
        'promotion_suitable' => 'boolean',
        'promotion_readiness' => 'string',
    ];

    /**
     * Valid enum values
     */
    private const ENUMS = [
        'risk_level' => ['low', 'medium', 'high', 'critical'],
        'level_label' => ['basarisiz', 'gelisime_acik', 'yeterli', 'iyi', 'mukemmel'],
        'promotion_readiness' => ['not_ready', 'developing', 'ready', 'highly_ready'],
        'severity' => ['low', 'medium', 'high', 'critical'],
        'priority' => ['low', 'medium', 'high'],
    ];

    /**
     * Validate the AI response against the schema
     */
    public function validate(array $response): bool
    {
        $this->errors = [];

        // Check required fields
        foreach (self::REQUIRED_FIELDS as $field => $type) {
            if (!isset($response[$field])) {
                $this->errors[] = "Missing required field: {$field}";
                continue;
            }

            if (!$this->validateType($response[$field], $type)) {
                $this->errors[] = "Field {$field} must be of type {$type}";
            }
        }

        // Validate overall_score range
        if (isset($response['overall_score'])) {
            $score = (float) $response['overall_score'];
            if ($score < 0 || $score > 100) {
                $this->errors[] = "overall_score must be between 0 and 100";
            }
        }

        // Validate level_numeric range
        if (isset($response['level_numeric'])) {
            $level = (int) $response['level_numeric'];
            if ($level < 1 || $level > 5) {
                $this->errors[] = "level_numeric must be between 1 and 5";
            }
        }

        // Validate enums
        foreach (self::ENUMS as $field => $validValues) {
            if (isset($response[$field]) && !in_array($response[$field], $validValues)) {
                $this->errors[] = "Invalid value for {$field}: {$response[$field]}. Valid values: " . implode(', ', $validValues);
            }
        }

        // Validate competency_scores structure
        if (isset($response['competency_scores']) && is_array($response['competency_scores'])) {
            foreach ($response['competency_scores'] as $code => $data) {
                if (!is_array($data)) {
                    $this->errors[] = "competency_scores.{$code} must be an object";
                    continue;
                }

                if (!isset($data['score']) || !is_numeric($data['score'])) {
                    $this->errors[] = "competency_scores.{$code}.score is required and must be numeric";
                }

                if (isset($data['score']) && ($data['score'] < 0 || $data['score'] > 100)) {
                    $this->errors[] = "competency_scores.{$code}.score must be between 0 and 100";
                }
            }
        }

        // Validate risk_flags structure
        if (isset($response['risk_flags']) && is_array($response['risk_flags'])) {
            foreach ($response['risk_flags'] as $index => $flag) {
                if (!is_array($flag)) {
                    $this->errors[] = "risk_flags[{$index}] must be an object";
                    continue;
                }

                if (!isset($flag['severity'])) {
                    $this->errors[] = "risk_flags[{$index}].severity is required";
                } elseif (!in_array($flag['severity'], self::ENUMS['severity'])) {
                    $this->errors[] = "risk_flags[{$index}].severity is invalid";
                }
            }
        }

        // Validate development_plan structure
        if (isset($response['development_plan']) && is_array($response['development_plan'])) {
            foreach ($response['development_plan'] as $index => $plan) {
                if (!is_array($plan)) {
                    $this->errors[] = "development_plan[{$index}] must be an object";
                    continue;
                }

                if (!isset($plan['area'])) {
                    $this->errors[] = "development_plan[{$index}].area is required";
                }

                if (isset($plan['priority']) && !in_array($plan['priority'], self::ENUMS['priority'])) {
                    $this->errors[] = "development_plan[{$index}].priority is invalid";
                }
            }
        }

        // Validate question_analyses if present
        if (isset($response['question_analyses']) && is_array($response['question_analyses'])) {
            foreach ($response['question_analyses'] as $index => $analysis) {
                if (!is_array($analysis)) {
                    $this->errors[] = "question_analyses[{$index}] must be an object";
                    continue;
                }

                if (!isset($analysis['question_order']) || !is_numeric($analysis['question_order'])) {
                    $this->errors[] = "question_analyses[{$index}].question_order is required and must be numeric";
                }

                if (isset($analysis['score']) && (!is_numeric($analysis['score']) || $analysis['score'] < 0)) {
                    $this->errors[] = "question_analyses[{$index}].score must be a non-negative number";
                }
            }
        }

        if (!empty($this->errors)) {
            Log::warning('Assessment analysis validation failed', [
                'errors' => $this->errors,
                'response_keys' => array_keys($response),
            ]);
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Validate type
     */
    private function validateType($value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value) || (is_numeric($value) && floor($value) == $value),
            'numeric' => is_numeric($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            default => true,
        };
    }

    /**
     * Attempt to fix common issues in the response
     */
    public function attemptFix(array $response): array
    {
        // Fix type coercion issues
        if (isset($response['overall_score']) && is_string($response['overall_score'])) {
            $response['overall_score'] = (float) $response['overall_score'];
        }

        if (isset($response['level_numeric']) && is_string($response['level_numeric'])) {
            $response['level_numeric'] = (int) $response['level_numeric'];
        }

        if (isset($response['promotion_suitable']) && is_string($response['promotion_suitable'])) {
            $response['promotion_suitable'] = in_array(strtolower($response['promotion_suitable']), ['true', '1', 'yes', 'evet']);
        }

        // Ensure arrays are arrays
        foreach (['competency_scores', 'risk_flags', 'strengths', 'improvement_areas', 'development_plan'] as $field) {
            if (isset($response[$field]) && !is_array($response[$field])) {
                $response[$field] = [];
            }
        }

        // Normalize level_label
        if (isset($response['level_label'])) {
            $response['level_label'] = strtolower(str_replace(' ', '_', $response['level_label']));
        }

        return $response;
    }
}
