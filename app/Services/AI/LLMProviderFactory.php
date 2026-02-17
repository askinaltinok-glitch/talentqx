<?php

namespace App\Services\AI;

use App\Models\AiSetting;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class LLMProviderFactory
{
    public const PROVIDER_OPENAI = 'openai';
    public const PROVIDER_KIMI = 'kimi';

    public const VALID_PROVIDERS = [
        self::PROVIDER_OPENAI,
        self::PROVIDER_KIMI,
    ];

    /**
     * Create a provider with settings from database.
     */
    public static function create(?string $provider = null, ?string $companyId = null): LLMProviderInterface
    {
        $settings = AiSetting::getForCompany($companyId) ?? AiSetting::getOrCreatePlatformSettings();
        $provider = $provider ?? $settings->provider ?? self::PROVIDER_OPENAI;

        return self::createWithSettings($provider, $settings);
    }

    /**
     * Create a provider with explicit settings object.
     */
    public static function createWithSettings(string $provider, AiSetting $settings): LLMProviderInterface
    {
        return match ($provider) {
            self::PROVIDER_OPENAI => new OpenAIProvider(
                apiKey: $settings->openai_api_key,
                model: $settings->openai_model,
                whisperModel: $settings->openai_whisper_model,
                timeout: $settings->timeout
            ),
            self::PROVIDER_KIMI => new KimiProvider(
                apiKey: $settings->kimi_api_key,
                baseUrl: $settings->kimi_base_url,
                model: $settings->kimi_model,
                timeout: $settings->timeout,
                openaiApiKey: $settings->openai_api_key // For Whisper fallback
            ),
            default => throw new InvalidArgumentException("Unknown AI provider: {$provider}"),
        };
    }

    /**
     * Create provider for a specific company.
     */
    public static function createForCompany(Company $company): LLMProviderInterface
    {
        $settings = AiSetting::getForCompany($company->id);

        if (!$settings) {
            $settings = AiSetting::getOrCreatePlatformSettings();
        }

        return self::createWithSettings($settings->provider, $settings);
    }

    /**
     * Create provider for the currently authenticated user.
     */
    public static function createForCurrentUser(): LLMProviderInterface
    {
        $user = Auth::user();

        if ($user && $user->company_id) {
            return self::create(null, $user->company_id);
        }

        return self::create();
    }

    /**
     * Get the default provider from settings.
     */
    public static function getDefaultProvider(): string
    {
        $settings = AiSetting::getPlatformSettings();
        return $settings?->provider ?? self::PROVIDER_OPENAI;
    }

    /**
     * Check if a provider is valid.
     */
    public static function isValidProvider(string $provider): bool
    {
        return in_array($provider, self::VALID_PROVIDERS, true);
    }

    /**
     * Get available providers with their status from database.
     */
    public static function getAvailableProviders(?string $companyId = null): array
    {
        $settings = AiSetting::getForCompany($companyId) ?? AiSetting::getOrCreatePlatformSettings();

        return [
            [
                'id' => self::PROVIDER_OPENAI,
                'name' => 'OpenAI',
                'model' => $settings->openai_model,
                'available' => $settings->hasOpenaiKey(),
                'is_selected' => $settings->provider === self::PROVIDER_OPENAI,
            ],
            [
                'id' => self::PROVIDER_KIMI,
                'name' => 'Kimi AI (Moonshot)',
                'model' => $settings->kimi_model,
                'available' => $settings->hasKimiKey(),
                'is_selected' => $settings->provider === self::PROVIDER_KIMI,
            ],
        ];
    }
}
