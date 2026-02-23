<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterviewQuestionSet extends Model
{
    use HasUuids;

    protected $fillable = [
        'code',
        'version',
        'industry_code',
        'position_code',
        'country_code',
        'locale',
        'is_active',
        'rules_json',
        'questions_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rules_json' => 'array',
        'questions_json' => 'array',
    ];

    public function attempts(): HasMany
    {
        return $this->hasMany(CandidateQuestionAttempt::class, 'question_set_id');
    }

    /**
     * Scope: only active sets.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
