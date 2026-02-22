<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MaritimeScenario extends Model
{
    use HasUuids;

    protected $table = 'maritime_scenarios';

    public const ROLE_FAMILIES = ['deck', 'engine', 'galley', 'general'];

    protected $fillable = [
        'scenario_code',
        'command_class',
        'role_family',
        'slot',
        'domain',
        'primary_capability',
        'secondary_capabilities',
        'difficulty_tier',
        'briefing_json',
        'decision_prompt',
        'decision_prompt_i18n',
        'evaluation_axes_json',
        'critical_omission_flags_json',
        'expected_references_json',
        'red_flags_json',
        'version',
        'is_active',
    ];

    protected $casts = [
        'slot' => 'integer',
        'difficulty_tier' => 'integer',
        'briefing_json' => 'array',
        'secondary_capabilities' => 'array',
        'decision_prompt_i18n' => 'array',
        'evaluation_axes_json' => 'array',
        'critical_omission_flags_json' => 'array',
        'expected_references_json' => 'array',
        'red_flags_json' => 'array',
        'is_active' => 'boolean',
    ];

    // ── Domains ──

    public const DOMAINS = [
        'PORT_OPS', 'NAV_HAZ', 'WX_DEC', 'ENG_MACH', 'CARGO_EMG',
        'CREW_EMG', 'COLAV', 'ENV_COMP', 'COMM_PRESS', 'REG_ENC', 'TRADEOFF',
    ];

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForClass($query, string $code)
    {
        return $query->where('command_class', $code);
    }

    public function scopeForSlot($query, int $slot)
    {
        return $query->where('slot', $slot);
    }

    public function scopeForDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    public function scopeForRoleFamily($query, string $family)
    {
        return $query->where('role_family', $family);
    }

    // ── Language helpers ──

    public function getBriefingForLanguage(string $lang): array
    {
        $briefing = $this->briefing_json ?? [];

        return $briefing[$lang] ?? $briefing['en'] ?? [];
    }

    public function getDecisionPromptForLanguage(string $lang): ?string
    {
        $i18n = $this->decision_prompt_i18n;

        if ($i18n && !empty($i18n[$lang])) {
            return $i18n[$lang];
        }

        return $this->decision_prompt;
    }
}
