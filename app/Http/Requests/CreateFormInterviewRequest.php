<?php

namespace App\Http\Requests;

use App\Services\Consent\ConsentService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateFormInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'max:32'],
            'language' => ['required', 'string', 'max:8'],
            'position_code' => ['nullable', 'string', 'max:128'],
            'industry_code' => ['nullable', 'string', 'max:64'],
            'role_code' => ['nullable', 'string', 'max:60'],
            'department' => ['nullable', 'string', 'in:deck,engine,galley,cadet'],
            'country_code' => ['required', 'string', 'size:2'],
            'consents' => ['required', 'array', 'min:1'],
            'consents.*' => ['required', 'string', 'in:data_processing,data_retention,data_sharing,marketing'],
            'meta' => ['nullable', 'array'],
            'operation_type' => ['nullable', 'string', 'in:sea,river'],
        ];
    }

    public function messages(): array
    {
        return [
            'country_code.required' => 'country_code is required for regulation detection',
            'country_code.size' => 'country_code must be a 2-letter ISO code (e.g., TR, DE)',
            'consents.required' => 'consents is required',
            'consents.min' => 'consents array cannot be empty',
            'consents.*.in' => 'Invalid consent type. Allowed: data_processing, data_retention, data_sharing, marketing',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->any()) {
                return; // Skip if basic validation failed
            }

            $countryCode = $this->input('country_code');
            $consents = $this->input('consents', []);

            // Detect regulation
            $regulation = $this->detectRegulation($countryCode);

            // Get required consents for this regulation
            $requiredConsents = $this->getRequiredConsents($regulation);

            // Check for missing required consents
            $missing = array_diff($requiredConsents, $consents);

            if (!empty($missing)) {
                $validator->errors()->add(
                    'consents',
                    "Missing required consents for {$regulation}: " . implode(', ', $missing)
                );
            }
        });
    }

    /**
     * Detect regulation based on country code.
     */
    private function detectRegulation(string $countryCode): string
    {
        $countryCode = strtoupper($countryCode);

        // Turkey = KVKK
        if ($countryCode === 'TR') {
            return 'KVKK';
        }

        // EU/EEA countries = GDPR
        $euCountries = [
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
            'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
            'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
            // EEA
            'IS', 'LI', 'NO',
            // UK (post-Brexit, similar rules)
            'GB',
        ];

        if (in_array($countryCode, $euCountries)) {
            return 'GDPR';
        }

        return 'GENERIC';
    }

    /**
     * Get required consents for a regulation.
     */
    private function getRequiredConsents(string $regulation): array
    {
        return match ($regulation) {
            'KVKK' => ['data_processing', 'data_retention'],
            'GDPR' => ['data_processing', 'data_retention'],
            'GENERIC' => ['data_processing'],
            default => ['data_processing'],
        };
    }

    /**
     * Get the detected regulation (for use in controller).
     */
    public function getRegulation(): string
    {
        return $this->detectRegulation($this->input('country_code'));
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
