<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResearchJob extends Model
{
    use HasUuids;

    protected $table = 'research_jobs';

    protected $fillable = [
        'industry_code', 'query', 'status',
        'result_count', 'started_at', 'finished_at',
        'meta', 'created_by',
    ];

    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_RUNNING,
        self::STATUS_COMPLETED, self::STATUS_FAILED,
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(ResearchCompanyCandidate::class, 'job_id');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function start(): void
    {
        $this->update(['status' => self::STATUS_RUNNING, 'started_at' => now()]);
    }

    public function complete(int $resultCount): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'finished_at' => now(),
            'result_count' => $resultCount,
        ]);
    }

    public function fail(): void
    {
        $this->update(['status' => self::STATUS_FAILED, 'finished_at' => now()]);
    }
}
