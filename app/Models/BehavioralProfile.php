<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BehavioralProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'candidate_id',
        'company_id',
        'interview_id',
        'language',
        'version',
        'status',
        'confidence',
        'dimensions_json',
        'fit_json',
        'flags_json',
        'computed_at',
    ];

    protected $casts = [
        'confidence' => 'float',
        'dimensions_json' => 'array',
        'fit_json' => 'array',
        'flags_json' => 'array',
        'computed_at' => 'datetime',
    ];

    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FINAL = 'final';

    public const DIMENSIONS = [
        'DISCIPLINE_COMPLIANCE',
        'TEAM_COOPERATION',
        'COMM_CLARITY',
        'STRESS_CONTROL',
        'CONFLICT_RISK',
        'LEARNING_GROWTH',
        'RELIABILITY_STABILITY',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class, 'interview_id');
    }

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }

    public static function emptyDimensions(): array
    {
        $dims = [];
        foreach (self::DIMENSIONS as $dim) {
            $dims[$dim] = [
                'score' => 0,
                'level' => 'low',
                'evidence' => [],
                'flags' => [],
            ];
        }
        return $dims;
    }
}
