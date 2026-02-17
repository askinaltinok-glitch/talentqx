<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PositionTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'job_position_id',
        'name',
        'slug',
        'description',
        'category',
        'competencies',
        'red_flags',
        'question_rules',
        'scoring_rubric',
        'critical_behaviors',
        'is_active',
    ];

    protected $casts = [
        'competencies' => 'array',
        'red_flags' => 'array',
        'question_rules' => 'array',
        'scoring_rubric' => 'array',
        'critical_behaviors' => 'array',
        'is_active' => 'boolean',
    ];

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'template_id');
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class, 'job_position_id');
    }

    public function getCompetencyByCode(string $code): ?array
    {
        foreach ($this->competencies ?? [] as $comp) {
            if ($comp['code'] === $code) {
                return $comp;
            }
        }
        return null;
    }

    public function getTotalWeight(): int
    {
        return collect($this->competencies ?? [])
            ->sum('weight');
    }
}
