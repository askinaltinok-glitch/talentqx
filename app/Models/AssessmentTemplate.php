<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'role_category',
        'description',
        'competencies',
        'red_flags',
        'questions',
        'scoring_config',
        'time_limit_minutes',
        'is_active',
    ];

    protected $casts = [
        'competencies' => 'array',
        'red_flags' => 'array',
        'questions' => 'array',
        'scoring_config' => 'array',
        'time_limit_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(AssessmentSession::class, 'template_id');
    }

    public function getCompetencyByCode(string $code): ?array
    {
        return collect($this->competencies)->firstWhere('code', $code);
    }

    public function getCompetencyWeight(string $code): float
    {
        $competency = $this->getCompetencyByCode($code);
        return $competency['weight'] ?? 1.0;
    }

    public function getTotalWeight(): float
    {
        return collect($this->competencies)->sum('weight');
    }

    public function getLevelLabel(float $score): string
    {
        $thresholds = $this->scoring_config['level_thresholds'] ?? [
            'basarisiz' => 40,
            'gelisime_acik' => 55,
            'yeterli' => 70,
            'iyi' => 85,
            'mukemmel' => 100,
        ];

        if ($score < $thresholds['basarisiz']) return 'Basarisiz';
        if ($score < $thresholds['gelisime_acik']) return 'Gelisime Acik';
        if ($score < $thresholds['yeterli']) return 'Yeterli';
        if ($score < $thresholds['iyi']) return 'Iyi';
        return 'Mukemmel';
    }

    public function getLevelNumeric(float $score): int
    {
        $label = $this->getLevelLabel($score);
        return match($label) {
            'Basarisiz' => 1,
            'Gelisime Acik' => 2,
            'Yeterli' => 3,
            'Iyi' => 4,
            'Mukemmel' => 5,
            default => 0,
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $roleCategory)
    {
        return $query->where('role_category', $roleCategory);
    }
}
