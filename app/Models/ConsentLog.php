<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentLog extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'candidate_id',
        'consent_type',
        'consent_version',
        'consent_text',
        'action',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public const TYPE_KVKK = 'kvkk';
    public const TYPE_VIDEO_RECORDING = 'video_recording';
    public const TYPE_DATA_PROCESSING = 'data_processing';

    public const ACTION_GIVEN = 'given';
    public const ACTION_WITHDRAWN = 'withdrawn';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            $log->created_at = $log->created_at ?? now();
        });
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public static function logConsent(
        Candidate $candidate,
        string $type,
        string $version,
        string $text,
        string $action,
        ?string $ip = null,
        ?string $userAgent = null
    ): self {
        return static::create([
            'candidate_id' => $candidate->id,
            'consent_type' => $type,
            'consent_version' => $version,
            'consent_text' => $text,
            'action' => $action,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }
}
