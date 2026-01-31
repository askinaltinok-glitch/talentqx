<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class JobContext extends Model
{
    use HasUuids;

    protected $fillable = [
        'role_key',
        'context_key',
        'label_tr',
        'label_en',
        'description_tr',
        'description_en',
        'default_weights',
        'environment_tags',
        'risk_level',
        'is_active',
    ];

    protected $casts = [
        'default_weights' => 'array',
        'environment_tags' => 'array',
        'is_active' => 'boolean',
    ];

    // Default weights for all dimensions
    public const DEFAULT_WEIGHTS = [
        'clarity' => 1.0,
        'ownership' => 1.0,
        'problem' => 1.0,
        'stress' => 1.0,
        'consistency' => 1.0,
    ];

    /**
     * Get contexts for a role
     */
    public static function getForRole(string $roleKey): Collection
    {
        return static::where('role_key', $roleKey)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Find by role and context keys
     */
    public static function findByKeys(string $roleKey, string $contextKey): ?self
    {
        return static::where('role_key', $roleKey)
            ->where('context_key', $contextKey)
            ->first();
    }

    /**
     * Get weight multipliers with defaults
     */
    public function getWeights(): array
    {
        return array_merge(self::DEFAULT_WEIGHTS, $this->default_weights ?? []);
    }

    /**
     * Apply weights to dimension scores
     */
    public function applyWeights(array $dimensionScores): array
    {
        $weights = $this->getWeights();
        $weighted = [];

        foreach ($dimensionScores as $dimension => $score) {
            $multiplier = $weights[$dimension] ?? 1.0;
            $weighted[$dimension] = $score * $multiplier;
        }

        return $weighted;
    }

    /**
     * Get label by locale
     */
    public function getLabel(string $locale = 'tr'): string
    {
        return $locale === 'en' ? $this->label_en : $this->label_tr;
    }

    /**
     * Get description by locale
     */
    public function getDescription(string $locale = 'tr'): ?string
    {
        return $locale === 'en' ? $this->description_en : $this->description_tr;
    }
}
