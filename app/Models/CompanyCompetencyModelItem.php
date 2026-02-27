<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyCompetencyModelItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'model_id',
        'competency_code',
        'weight',
        'priority',
        'min_score',
    ];

    protected $casts = [
        'weight' => 'float',
        'min_score' => 'integer',
    ];

    public function competencyModel(): BelongsTo
    {
        return $this->belongsTo(CompanyCompetencyModel::class, 'model_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'competency_code', 'code');
    }

    public function getCompetencyNameAttribute(): string
    {
        $locale = app()->getLocale();
        $competency = $this->competency;

        if (!$competency) {
            return $this->competency_code;
        }

        return $locale === 'en' ? $competency->name_en : $competency->name_tr;
    }
}
