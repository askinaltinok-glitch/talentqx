<?php

namespace App\Models;

use App\Models\Traits\IsDemoScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProfileView extends Model
{
    use HasUuids, IsDemoScoped;

    protected $table = 'candidate_profile_views';

    public $timestamps = false;

    protected $fillable = [
        'pool_candidate_id', 'viewer_type', 'viewer_id',
        'viewer_name', 'company_name', 'view_duration_seconds',
        'context', 'context_meta', 'viewed_at',
        'is_demo',
    ];

    protected $casts = [
        'context_meta' => 'array',
        'viewed_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public const VIEWER_COMPANY = 'company';
    public const VIEWER_ADMIN = 'admin';

    public const CONTEXT_PRESENTATION = 'presentation';
    public const CONTEXT_SEARCH = 'search';
    public const CONTEXT_BROWSE = 'browse';

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }

    public function scopeForCandidate($query, string $candidateId)
    {
        return $query->where('pool_candidate_id', $candidateId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('viewed_at', '>=', now()->subDays($days));
    }
}
