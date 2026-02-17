<?php

namespace App\Http\Requests;

use App\Models\PoolCandidate;
use App\Services\Consent\ConsentService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StartCandidateInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'position_code' => ['nullable', 'string', 'max:128'],
            'industry_code' => [
                'nullable',
                'string',
                'in:' . implode(',', PoolCandidate::INDUSTRIES),
            ],
            'country_code' => ['required', 'string', 'size:2'],
            'consents' => ['required', 'array', 'min:1'],
            'consents.*' => [
                'required',
                'string',
                'in:' . implode(',', ConsentService::VALID_CONSENT_TYPES),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $countryCode = $this->input('country_code');
            $consents = $this->input('consents', []);

            $regulation = $this->detectRegulation($countryCode);
            $requiredConsents = $this->getRequiredConsents($regulation);

            $missing = array_diff($requiredConsents, $consents);

            if (!empty($missing)) {
                $validator->errors()->add(
                    'consents',
                    "Missing required consents for {$regulation}: " . implode(', ', $missing)
                );
            }
        });
    }

    public function getRegulation(): string
    {
        return $this->detectRegulation($this->input('country_code'));
    }

    private function detectRegulation(string $countryCode): string
    {
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
            // UK (post-Brexit still GDPR-aligned)
            'GB',
        ];

        if (in_array($countryCode, $euCountries, true)) {
            return 'GDPR';
        }

        return 'GENERIC';
    }

    private function getRequiredConsents(string $regulation): array
    {
        return ConsentService::REQUIRED_BY_REGULATION[$regulation] ?? ['data_processing'];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422)
        );
    }
}
