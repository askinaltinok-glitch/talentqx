<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchSignal extends Model
{
    use HasUuids;

    protected $table = 'research_signals';

    protected $fillable = [
        'research_company_id', 'signal_type', 'confidence_score',
        'source_url', 'raw_data', 'detected_at',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'detected_at' => 'datetime',
    ];

    // Signal type constants
    public const TYPE_JOB_POST = 'job_post_detected';
    public const TYPE_HIRING_SPIKE = 'hiring_spike';
    public const TYPE_MARITIME_CREW = 'maritime_crew_needed';
    public const TYPE_EXPANSION = 'expansion';
    public const TYPE_CAREER_PAGE = 'career_page';
    public const TYPE_CREW_CALL = 'crew_call';

    public const TYPES = [
        self::TYPE_JOB_POST, self::TYPE_HIRING_SPIKE,
        self::TYPE_MARITIME_CREW, self::TYPE_EXPANSION,
        self::TYPE_CAREER_PAGE, self::TYPE_CREW_CALL,
    ];

    // Relationships

    public function company(): BelongsTo
    {
        return $this->belongsTo(ResearchCompany::class, 'research_company_id');
    }

    // Scopes

    public function scopeType($query, string $type)
    {
        return $query->where('signal_type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('detected_at', '>=', now()->subDays($days));
    }
}
