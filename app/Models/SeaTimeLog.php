<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeaTimeLog extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    const OP_SEA = 'sea';
    const OP_RIVER = 'river';

    protected $fillable = [
        'pool_candidate_id',
        'candidate_contract_id',
        'vessel_id',
        'rank_code',
        'original_start_date',
        'original_end_date',
        'effective_start_date',
        'effective_end_date',
        'vessel_type',
        'operation_type',
        'raw_days',
        'calculated_days',
        'overlap_deducted_days',
        'computation_batch_id',
        'computed_at',
    ];

    protected $casts = [
        'original_start_date' => 'date',
        'original_end_date' => 'date',
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
        'raw_days' => 'integer',
        'calculated_days' => 'integer',
        'overlap_deducted_days' => 'integer',
        'computed_at' => 'datetime',
    ];

    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(CandidateContract::class, 'candidate_contract_id');
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function scopeForCandidate($query, string $candidateId)
    {
        return $query->where('pool_candidate_id', $candidateId);
    }

    public function scopeForBatch($query, string $batchId)
    {
        return $query->where('computation_batch_id', $batchId);
    }

    public function scopeLatestBatch($query, string $candidateId)
    {
        $latestBatch = static::where('pool_candidate_id', $candidateId)
            ->orderByDesc('computed_at')
            ->value('computation_batch_id');

        return $latestBatch
            ? $query->where('computation_batch_id', $latestBatch)
            : $query->whereRaw('1 = 0');
    }
}
