<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AiSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
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
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'openai_enabled' => 'boolean',
        'kimi_enabled' => 'boolean',
        'timeout' => 'integer',
    ];

    protected $hidden = [
        'openai_api_key',
        'kimi_api_key',
    ];

    public const PROVIDERS = ['openai', 'kimi'];

    /**
     * Get the company that owns the settings.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who last updated the settings.
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Encrypt OpenAI API key before storing.
     */
    public function setOpenaiApiKeyAttribute(?string $value): void
    {
        $this->attributes['openai_api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt OpenAI API key when retrieving.
     */
    public function getOpenaiApiKeyAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Encrypt Kimi API key before storing.
     */
    public function setKimiApiKeyAttribute(?string $value): void
    {
        $this->attributes['kimi_api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt Kimi API key when retrieving.
     */
    public function getKimiApiKeyAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if OpenAI is configured.
     */
    public function hasOpenaiKey(): bool
    {
        return !empty($this->openai_api_key);
    }

    /**
     * Check if Kimi is configured.
     */
    public function hasKimiKey(): bool
    {
        return !empty($this->kimi_api_key);
    }

    /**
     * Check if OpenAI is enabled and configured.
     */
    public function isOpenaiEnabled(): bool
    {
        return $this->openai_enabled && $this->hasOpenaiKey();
    }

    /**
     * Check if Kimi is enabled and configured.
     */
    public function isKimiEnabled(): bool
    {
        return $this->kimi_enabled && $this->hasKimiKey();
    }

    /**
     * Get list of enabled providers.
     */
    public function getEnabledProviders(): array
    {
        $providers = [];

        if ($this->isOpenaiEnabled()) {
            $providers[] = [
                'id' => 'openai',
                'name' => 'OpenAI',
                'model' => $this->openai_model,
            ];
        }

        if ($this->isKimiEnabled()) {
            $providers[] = [
                'id' => 'kimi',
                'name' => 'Moonshot AI',
                'model' => $this->kimi_model,
            ];
        }

        return $providers;
    }

    /**
     * Get masked API key for display (last 4 chars only).
     */
    public function getMaskedOpenaiKey(): ?string
    {
        $key = $this->openai_api_key;
        if (!$key) {
            return null;
        }
        return '••••••••' . substr($key, -4);
    }

    /**
     * Get masked Kimi API key for display.
     */
    public function getMaskedKimiKey(): ?string
    {
        $key = $this->kimi_api_key;
        if (!$key) {
            return null;
        }
        return '••••••••' . substr($key, -4);
    }

    /**
     * Get platform-wide default settings.
     */
    public static function getPlatformSettings(): ?self
    {
        return self::whereNull('company_id')->where('is_active', true)->first();
    }

    /**
     * Get settings for a specific company (falls back to platform settings).
     */
    public static function getForCompany(?string $companyId): ?self
    {
        if ($companyId) {
            $companySettings = self::where('company_id', $companyId)
                ->where('is_active', true)
                ->first();

            if ($companySettings) {
                return $companySettings;
            }
        }

        return self::getPlatformSettings();
    }

    /**
     * Get or create platform settings with defaults from env.
     */
    public static function getOrCreatePlatformSettings(): self
    {
        $settings = self::getPlatformSettings();

        if (!$settings) {
            $settings = self::create([
                'company_id' => null,
                'provider' => config('services.ai.default_provider', 'openai'),
                'openai_api_key' => config('services.openai.api_key'),
                'openai_model' => config('services.openai.model', 'gpt-4o-mini'),
                'openai_whisper_model' => config('services.openai.whisper_model', 'whisper-1'),
                'kimi_api_key' => config('services.kimi.api_key'),
                'kimi_base_url' => config('services.kimi.base_url', 'https://api.moonshot.ai/v1'),
                'kimi_model' => config('services.kimi.model', 'moonshot-v1-128k'),
                'timeout' => config('services.openai.timeout', 120),
                'is_active' => true,
            ]);
        }

        return $settings;
    }
}
