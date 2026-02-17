<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaritimeJobApplication extends Model
{
    use HasUuids;

    protected $table = 'maritime_job_applications';

    protected $fillable = [
        'maritime_job_id',
        'pool_candidate_id',
        'status',
    ];

    public const STATUS_APPLIED = 'applied';
    public const STATUS_SHORTLISTED = 'shortlisted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_HIRED = 'hired';

    public const STATUSES = [
        self::STATUS_APPLIED,
        self::STATUS_SHORTLISTED,
        self::STATUS_REJECTED,
        self::STATUS_HIRED,
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(MaritimeJob::class, 'maritime_job_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }

    public function scopeForCandidate($query, string $candidateId)
    {
        return $query->where('pool_candidate_id', $candidateId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
