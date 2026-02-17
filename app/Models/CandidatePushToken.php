<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidatePushToken extends Model
{
    use HasUuids;

    protected $table = 'candidate_push_tokens';

    protected $fillable = [
        'pool_candidate_id',
        'device_type',
        'token',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public const DEVICE_IOS = 'ios';
    public const DEVICE_ANDROID = 'android';
    public const DEVICE_WEB = 'web';

    public const DEVICE_TYPES = [self::DEVICE_IOS, self::DEVICE_ANDROID, self::DEVICE_WEB];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }

    public function scopeForCandidate($query, string $candidateId)
    {
        return $query->where('pool_candidate_id', $candidateId);
    }

    public function touchLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
