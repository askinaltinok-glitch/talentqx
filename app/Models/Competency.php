<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competency extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name_tr',
        'name_en',
        'description_tr',
        'description_en',
        'category',
        'icon',
        'indicators',
        'evaluation_criteria',
        'red_flags',
        'is_universal',
        'is_active',
    ];

    protected $casts = [
        'indicators' => 'array',
        'evaluation_criteria' => 'array',
        'red_flags' => 'array',
        'is_universal' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Category constants
    const CATEGORY_SOFT_SKILL = 'soft_skill';
    const CATEGORY_HARD_SKILL = 'hard_skill';
    const CATEGORY_TECHNICAL = 'technical';
    const CATEGORY_BEHAVIORAL = 'behavioral';

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(JobPosition::class, 'position_competencies', 'competency_id', 'position_id')
            ->withPivot(['weight', 'is_critical', 'min_score', 'position_specific_criteria_tr', 'position_specific_criteria_en', 'sort_order'])
            ->withTimestamps();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(PositionQuestion::class, 'competency_id');
    }

    public function getName(string $locale = 'tr'): string
    {
        return $locale === 'en' ? $this->name_en : $this->name_tr;
    }

    public function getDescription(string $locale = 'tr'): ?string
    {
        return $locale === 'en' ? $this->description_en : $this->description_tr;
    }

    public function getCategoryLabel(string $locale = 'tr'): string
    {
        $labels = [
            self::CATEGORY_SOFT_SKILL => ['tr' => 'Yumuşak Beceri', 'en' => 'Soft Skill'],
            self::CATEGORY_HARD_SKILL => ['tr' => 'Teknik Beceri', 'en' => 'Hard Skill'],
            self::CATEGORY_TECHNICAL => ['tr' => 'Teknik', 'en' => 'Technical'],
            self::CATEGORY_BEHAVIORAL => ['tr' => 'Davranışsal', 'en' => 'Behavioral'],
        ];

        return $labels[$this->category][$locale] ?? $this->category;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUniversal($query)
    {
        return $query->where('is_universal', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
