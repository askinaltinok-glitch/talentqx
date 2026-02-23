<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SynergyWeightSet extends Model
{
    use HasUuids;

    protected $fillable = [
        'scope',
        'company_id',
        'weights_json',
        'deltas_json',
        'audit_log_json',
        'last_training_window',
        'sample_size',
    ];

    protected $casts = [
        'weights_json' => 'array',
        'deltas_json' => 'array',
        'audit_log_json' => 'array',
        'sample_size' => 'integer',
    ];

    const SCOPE_GLOBAL = 'global';
    const SCOPE_COMPANY = 'company';

    const MIN_SAMPLE_SIZE = 20;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the active global weight set (or create default).
     */
    public static function globalSet(): self
    {
        return static::firstOrCreate(
            ['scope' => self::SCOPE_GLOBAL, 'company_id' => null],
            [
                'weights_json' => config('maritime.synergy_v2.component_weights', [
                    'captain_fit' => 0.25,
                    'team_balance' => 0.20,
                    'vessel_fit' => 0.30,
                    'operational_risk' => 0.25,
                ]),
                'sample_size' => 0,
            ]
        );
    }

    /**
     * Get the company-specific weight set (or null).
     */
    public static function forCompany(string $companyId): ?self
    {
        return static::where('scope', self::SCOPE_COMPANY)
            ->where('company_id', $companyId)
            ->first();
    }

    /**
     * Whether this weight set has enough data for learning.
     */
    public function isTrainable(): bool
    {
        return $this->sample_size >= self::MIN_SAMPLE_SIZE;
    }
}
