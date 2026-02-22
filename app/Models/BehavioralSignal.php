<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase-3 skeleton. No logic yet.
 */
class BehavioralSignal extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'candidate_id',
        'signal_type',
        'signal_value',
        'observed_at',
        'created_at',
    ];

    protected $casts = [
        'signal_value' => 'array',
        'observed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }
}
