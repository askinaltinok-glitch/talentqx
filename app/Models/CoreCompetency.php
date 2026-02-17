<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TalentQX Karar Motoru - Temel Yetkinlik Modeli
 * 8 core competencies for AI scoring engine
 */
class CoreCompetency extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'core_competencies';

    protected $fillable = [
        'code',
        'name_tr',
        'name_en',
        'description_tr',
        'description_en',
        'category',
        'weight',
        'measurable_signals',
        'negative_signals',
        'scoring_rubric',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'measurable_signals' => 'array',
        'negative_signals' => 'array',
        'scoring_rubric' => 'array',
        'is_active' => 'boolean',
    ];

    // Categories
    public const CATEGORY_CORE = 'core';
    public const CATEGORY_ROLE_SPECIFIC = 'role_specific';

    // Competency codes
    public const CODE_COMMUNICATION = 'communication';
    public const CODE_ACCOUNTABILITY = 'accountability';
    public const CODE_TEAMWORK = 'teamwork';
    public const CODE_STRESS_RESILIENCE = 'stress_resilience';
    public const CODE_ADAPTABILITY = 'adaptability';
    public const CODE_LEARNING_AGILITY = 'learning_agility';
    public const CODE_INTEGRITY = 'integrity';
    public const CODE_ROLE_COMPETENCE = 'role_competence';

    /**
     * Scope: Only active competencies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Core competencies only
     */
    public function scopeCore($query)
    {
        return $query->where('category', self::CATEGORY_CORE);
    }

    /**
     * Get all active competencies ordered
     */
    public static function getAllActive()
    {
        return static::active()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get competency by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get scoring rubric for a specific level
     */
    public function getRubricForLevel(int $level): ?string
    {
        return $this->scoring_rubric[(string)$level] ?? null;
    }

    /**
     * Convert raw score (1-5) to percentage (0-100)
     */
    public function rawScoreToPercentage(int $rawScore): int
    {
        return min(100, max(0, $rawScore * 20));
    }

    /**
     * Get name by locale
     */
    public function getName(string $locale = 'tr'): string
    {
        return $locale === 'en' ? ($this->name_en ?? $this->name_tr) : $this->name_tr;
    }

    /**
     * Get description by locale
     */
    public function getDescription(string $locale = 'tr'): string
    {
        return $locale === 'en' ? ($this->description_en ?? $this->description_tr) : $this->description_tr;
    }

    /**
     * Get for API response
     */
    public function toApiArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name_tr,
            'name_en' => $this->name_en,
            'description' => $this->description_tr,
            'category' => $this->category,
            'weight' => $this->weight,
            'measurable_signals' => $this->measurable_signals,
            'negative_signals' => $this->negative_signals,
            'scoring_rubric' => $this->scoring_rubric,
        ];
    }

    /**
     * Get all competencies as array for AI prompt building
     */
    public static function getForAIPrompt(): array
    {
        return static::active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn($c) => [
                'code' => $c->code,
                'name' => $c->name_tr,
                'weight' => $c->weight,
                'measurable_signals' => $c->measurable_signals,
                'negative_signals' => $c->negative_signals,
                'scoring_rubric' => $c->scoring_rubric,
            ])
            ->toArray();
    }
}
