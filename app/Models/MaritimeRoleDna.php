<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaritimeRoleDna extends Model
{
    use HasUuids;

    protected $table = 'maritime_role_dna';

    protected $fillable = [
        'role_key',
        'dna_dimensions',
        'behavioral_profile',
        'mismatch_signals',
        'integration_rules',
        'version',
    ];

    protected $casts = [
        'dna_dimensions' => 'array',
        'behavioral_profile' => 'array',
        'mismatch_signals' => 'array',
        'integration_rules' => 'array',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(MaritimeRoleRecord::class, 'role_key', 'role_key');
    }

    public static function forRole(string $roleKey, string $version = 'v1'): ?self
    {
        return static::where('role_key', $roleKey)
            ->where('version', $version)
            ->first();
    }

    public function behavioralWeight(): float
    {
        return (float) ($this->dna_dimensions['behavioral_weight'] ?? 0.30);
    }

    public function technicalWeight(): float
    {
        return (float) ($this->dna_dimensions['technical_weight'] ?? 0.30);
    }

    public function leadershipExpectation(): float
    {
        return (float) ($this->dna_dimensions['leadership_expectation'] ?? 0.50);
    }

    public function safetyOwnership(): float
    {
        return (float) ($this->dna_dimensions['safety_ownership'] ?? 0.50);
    }

    public function supervisionDependency(): float
    {
        return (float) ($this->dna_dimensions['supervision_dependency'] ?? 0.50);
    }

    public function criticalDimensions(): array
    {
        return array_keys(array_filter(
            $this->behavioral_profile ?? [],
            fn($level) => $level === 'critical'
        ));
    }
}
