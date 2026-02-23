<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpsertTenantFeatureFlagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth handled by platform.octopus_admin middleware
    }

    public function rules(): array
    {
        return [
            'is_enabled' => ['required', 'boolean'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
