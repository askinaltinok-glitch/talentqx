<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandDetectionLog extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'candidate_id',
        'profile_snapshot',
        'scoring_output',
        'detected_class',
        'confidence',
    ];

    protected $casts = [
        'profile_snapshot' => 'array',
        'scoring_output' => 'array',
        'confidence' => 'float',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
