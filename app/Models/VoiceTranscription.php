<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceTranscription extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_DONE    = 'done';
    public const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'company_id',
        'interview_id',
        'candidate_id',
        'question_id',
        'slot',
        'audio_path',
        'audio_mime',
        'audio_size_bytes',
        'audio_sha256',
        'duration_ms',
        'provider',
        'model',
        'language',
        'status',
        'transcript_text',
        'confidence',
        'raw_response',
        'error_message',
    ];

    protected $casts = [
        'slot'             => 'integer',
        'audio_size_bytes' => 'integer',
        'duration_ms'      => 'integer',
        'confidence'       => 'float',
        'raw_response'     => 'array',
    ];

    // ── Scopes ──────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeDone($query)
    {
        return $query->where('status', self::STATUS_DONE);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // ── State helpers ───────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function markDone(string $transcript, ?float $confidence = null, ?array $raw = null, ?string $model = null, ?string $provider = null): void
    {
        $this->update([
            'status'          => self::STATUS_DONE,
            'transcript_text' => $transcript,
            'confidence'      => $confidence,
            'raw_response'    => $raw,
            'model'           => $model ?? $this->model,
            'provider'        => $provider ?? $this->provider,
        ]);
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status'        => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    // ── Relations ───────────────────────────────────────────

    public function interview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class, 'interview_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }
}
