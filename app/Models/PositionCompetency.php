<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PositionCompetency extends Pivot
{
    protected $table = 'position_competencies';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'position_id',
        'competency_id',
        'weight',
        'is_critical',
        'min_score',
        'position_specific_criteria_tr',
        'position_specific_criteria_en',
        'sort_order',
    ];

    protected $casts = [
        'weight' => 'integer',
        'is_critical' => 'boolean',
        'min_score' => 'integer',
        'sort_order' => 'integer',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class, 'position_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'competency_id');
    }

    public function getPositionSpecificCriteria(string $locale = 'tr'): ?string
    {
        return $locale === 'en'
            ? $this->position_specific_criteria_en
            : $this->position_specific_criteria_tr;
    }
}
