<?php

namespace App\Http\Requests;

use App\Models\PoolCandidate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreatePoolCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:128'],
            'last_name' => ['required', 'string', 'max:128'],
            'email' => ['required', 'email', 'max:255', 'unique:pool_candidates,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'country_code' => ['required', 'string', 'size:2'],
            'preferred_language' => ['nullable', 'string', 'max:8'],
            'english_level_self' => [
                'nullable',
                'string',
                'in:' . implode(',', PoolCandidate::ENGLISH_LEVELS),
            ],
            'source_channel' => [
                'required',
                'string',
                'in:' . implode(',', PoolCandidate::SOURCE_CHANNELS),
            ],
            'source_meta' => ['nullable', 'array'],
            'source_meta.event' => ['nullable', 'string', 'max:128'],
            'source_meta.city' => ['nullable', 'string', 'max:64'],
            'source_meta.campaign' => ['nullable', 'string', 'max:128'],
            'source_meta.referrer' => ['nullable', 'string', 'max:128'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A candidate with this email already exists.',
            'source_channel.in' => 'Invalid source channel. Valid options: ' . implode(', ', PoolCandidate::SOURCE_CHANNELS),
            'english_level_self.in' => 'Invalid English level. Valid options: ' . implode(', ', PoolCandidate::ENGLISH_LEVELS),
        ];
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
