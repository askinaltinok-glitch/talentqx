<?php

namespace App\Services\AI;

use App\Models\AiSetting;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AiSettingService
{
    /**
     * Get platform-wide AI settings.
     */
    public function getPlatformSettings(): AiSetting
    {
        return AiSetting::getOrCreatePlatformSettings();
    }

    /**
     * Get AI settings for a company (with fallback to platform).
     */
    public function getSettingsForCompany(?string $companyId): AiSetting
    {
        $settings = AiSetting::getForCompany($companyId);

        if (!$settings) {
            return $this->getPlatformSettings();
        }

        return $settings;
    }

    /**
     * Update platform-wide settings.
     */
    public function updatePlatformSettings(array $data, ?User $updatedBy = null): AiSetting
    {
        $settings = $this->getPlatformSettings();

        $updateData = $this->prepareUpdateData($data);
        $updateData['updated_by'] = $updatedBy?->id;

        $settings->update($updateData);

        Log::info('Platform AI settings updated', [
            'updated_by' => $updatedBy?->id,
            'provider' => $settings->provider,
        ]);

        return $settings->fresh();
    }

    /**
     * Update or create company-specific settings.
     */
    public function updateCompanySettings(string $companyId, array $data, ?User $updatedBy = null): AiSetting
    {
        $settings = AiSetting::where('company_id', $companyId)->first();

        $updateData = $this->prepareUpdateData($data);
        $updateData['updated_by'] = $updatedBy?->id;
        $updateData['company_id'] = $companyId;

        if ($settings) {
            $settings->update($updateData);
        } else {
            $settings = AiSetting::create($updateData);
        }

        Log::info('Company AI settings updated', [
            'company_id' => $companyId,
            'updated_by' => $updatedBy?->id,
            'provider' => $settings->provider,
        ]);

        return $settings->fresh();
    }

    /**
     * Delete company-specific settings (will fall back to platform).
     */
    public function deleteCompanySettings(string $companyId): bool
    {
        $deleted = AiSetting::where('company_id', $companyId)->delete();

        Log::info('Company AI settings deleted', [
            'company_id' => $companyId,
        ]);

        return $deleted > 0;
    }

    /**
     * Get available providers with their status.
     */
    public function getAvailableProviders(?string $companyId = null): array
    {
        $settings = $this->getSettingsForCompany($companyId);

        return [
            [
                'id' => 'openai',
                'name' => 'OpenAI',
                'model' => $settings->openai_model,
                'available' => $settings->hasOpenaiKey(),
                'enabled' => $settings->openai_enabled,
                'is_selected' => $settings->provider === 'openai',
            ],
            [
                'id' => 'kimi',
                'name' => 'Kimi AI (Moonshot)',
                'model' => $settings->kimi_model,
                'available' => $settings->hasKimiKey(),
                'enabled' => $settings->kimi_enabled,
                'is_selected' => $settings->provider === 'kimi',
            ],
        ];
    }

    /**
     * Get only enabled providers for dropdown.
     * Falls back to platform settings for API keys if company doesn't have them.
     */
    public function getEnabledProviders(?string $companyId = null): array
    {
        $companySettings = $companyId ? AiSetting::where('company_id', $companyId)->where('is_active', true)->first() : null;
        $platformSettings = $this->getPlatformSettings();

        $providers = [];

        // Check OpenAI - use company enabled flag, but check platform for key if company doesn't have one
        $openaiEnabled = $companySettings ? $companySettings->openai_enabled : $platformSettings->openai_enabled;
        $hasOpenaiKey = ($companySettings && $companySettings->hasOpenaiKey()) || $platformSettings->hasOpenaiKey();

        if ($openaiEnabled && $hasOpenaiKey) {
            $providers[] = [
                'id' => 'openai',
                'name' => 'OpenAI',
                'model' => $companySettings?->openai_model ?? $platformSettings->openai_model,
            ];
        }

        // Check Kimi - use company enabled flag, but check platform for key if company doesn't have one
        $kimiEnabled = $companySettings ? $companySettings->kimi_enabled : $platformSettings->kimi_enabled;
        $hasKimiKey = ($companySettings && $companySettings->hasKimiKey()) || $platformSettings->hasKimiKey();

        if ($kimiEnabled && $hasKimiKey) {
            $providers[] = [
                'id' => 'kimi',
                'name' => 'Moonshot AI',
                'model' => $companySettings?->kimi_model ?? $platformSettings->kimi_model,
            ];
        }

        return $providers;
    }

    /**
     * Test a provider connection.
     */
    public function testProvider(string $provider, ?string $companyId = null): array
    {
        $settings = $this->getSettingsForCompany($companyId);

        try {
            $llmProvider = LLMProviderFactory::createWithSettings($provider, $settings);
            return $llmProvider->test();
        } catch (\Exception $e) {
            Log::error('AI provider test failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format settings for API response.
     */
    public function formatForResponse(AiSetting $settings): array
    {
        return [
            'id' => $settings->id,
            'company_id' => $settings->company_id,
            'provider' => $settings->provider,
            'openai' => [
                'configured' => $settings->hasOpenaiKey(),
                'api_key_masked' => $settings->getMaskedOpenaiKey(),
                'model' => $settings->openai_model,
                'whisper_model' => $settings->openai_whisper_model,
                'enabled' => $settings->openai_enabled,
            ],
            'kimi' => [
                'configured' => $settings->hasKimiKey(),
                'api_key_masked' => $settings->getMaskedKimiKey(),
                'base_url' => $settings->kimi_base_url,
                'model' => $settings->kimi_model,
                'enabled' => $settings->kimi_enabled,
            ],
            'timeout' => $settings->timeout,
            'is_active' => $settings->is_active,
            'updated_at' => $settings->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Prepare update data, only including provided fields.
     */
    private function prepareUpdateData(array $data): array
    {
        $allowed = [
            'provider',
            'openai_api_key',
            'openai_model',
            'openai_whisper_model',
            'openai_enabled',
            'kimi_api_key',
            'kimi_base_url',
            'kimi_model',
            'kimi_enabled',
            'timeout',
            'is_active',
        ];

        return array_filter(
            array_intersect_key($data, array_flip($allowed)),
            fn($value) => $value !== null
        );
    }
}
