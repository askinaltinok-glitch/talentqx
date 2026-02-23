<?php

namespace App\Services\Fleet;

class VesselProfileValidator
{
    /**
     * Validate a profile_json structure. Returns array of error strings (empty = valid).
     */
    public function validate(array $profile): array
    {
        $errors = [];

        // Weights: must exist, must sum to 1.0 ± 0.01
        if (!isset($profile['weights']) || !is_array($profile['weights'])) {
            $errors[] = 'weights is required and must be an object.';
        } else {
            $sum = array_sum($profile['weights']);
            if (abs($sum - 1.0) > 0.01) {
                $errors[] = "weights must sum to 1.0 (±0.01). Current sum: {$sum}";
            }
            foreach ($profile['weights'] as $key => $val) {
                if (!is_numeric($val) || $val < 0 || $val > 1) {
                    $errors[] = "weights.{$key} must be a number between 0 and 1.";
                }
            }
        }

        // Behavior thresholds: each must be 0..1
        if (isset($profile['behavior_thresholds'])) {
            if (!is_array($profile['behavior_thresholds'])) {
                $errors[] = 'behavior_thresholds must be an object.';
            } else {
                foreach ($profile['behavior_thresholds'] as $key => $val) {
                    if (!is_numeric($val) || $val < 0 || $val > 1) {
                        $errors[] = "behavior_thresholds.{$key} must be between 0.0 and 1.0.";
                    }
                }
            }
        }

        // Required certificates
        if (isset($profile['required_certificates'])) {
            if (!is_array($profile['required_certificates'])) {
                $errors[] = 'required_certificates must be an array.';
            } else {
                foreach ($profile['required_certificates'] as $i => $cert) {
                    if (!is_array($cert)) {
                        $errors[] = "required_certificates[{$i}] must be an object.";
                        continue;
                    }
                    if (empty($cert['certificate_type']) || !is_string($cert['certificate_type'])) {
                        $errors[] = "required_certificates[{$i}].certificate_type must be a non-empty string.";
                    }
                    if (isset($cert['min_remaining_months'])) {
                        if (!is_int($cert['min_remaining_months']) && !ctype_digit((string) $cert['min_remaining_months'])) {
                            $errors[] = "required_certificates[{$i}].min_remaining_months must be an integer.";
                        } elseif ((int) $cert['min_remaining_months'] < 0) {
                            $errors[] = "required_certificates[{$i}].min_remaining_months must be >= 0.";
                        }
                    }
                    if (isset($cert['hard_block'])) {
                        if (!is_bool($cert['hard_block'])) {
                            $errors[] = "required_certificates[{$i}].hard_block must be a boolean.";
                        } elseif ($cert['hard_block'] === true && empty($cert['mandatory'])) {
                            $errors[] = "required_certificates[{$i}].hard_block requires mandatory to be true.";
                        }
                    }
                    if (isset($cert['block_reason_key'])) {
                        if (!is_string($cert['block_reason_key']) || strlen($cert['block_reason_key']) === 0) {
                            $errors[] = "required_certificates[{$i}].block_reason_key must be a non-empty string.";
                        } elseif (strlen($cert['block_reason_key']) > 100) {
                            $errors[] = "required_certificates[{$i}].block_reason_key must be <= 100 characters.";
                        }
                    }
                }
            }
        }

        // Experience
        if (isset($profile['experience'])) {
            if (!is_array($profile['experience'])) {
                $errors[] = 'experience must be an object.';
            } else {
                foreach (['vessel_type_min_months', 'any_vessel_min_months'] as $field) {
                    if (isset($profile['experience'][$field])) {
                        $val = $profile['experience'][$field];
                        if (!is_int($val) && !ctype_digit((string) $val)) {
                            $errors[] = "experience.{$field} must be an integer.";
                        } elseif ((int) $val < 0) {
                            $errors[] = "experience.{$field} must be >= 0.";
                        }
                    }
                }
            }
        }

        return $errors;
    }
}
