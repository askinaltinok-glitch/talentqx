<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CandidatePresentation - Company Consumption Layer
 *
 * Tracks which candidates were presented to companies for which requests.
 * This is the core link between Supply (pool candidates) and Consumption (companies).
 */
class CandidatePresentation extends Model
{
    use HasUuids;

    protected $fillable = [
        'talent_request_id',
        'pool_candidate_id',
        'presented_at',
        'presentation_status',
        'client_feedback',
        'client_score',
        'rejection_reason',
        'interview_scheduled_at',
        'interviewed_at',
        'hired_at',
        'start_date',
        'interview_outcome_id',
    ];

    protected $casts = [
        'presented_at' => 'datetime',
        'interview_scheduled_at' => 'datetime',
        'interviewed_at' => 'datetime',
        'hired_at' => 'datetime',
        'start_date' => 'date',
    ];

    // Status constants
    public const STATUS_SENT = 'sent';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_INTERVIEWED = 'interviewed';
    public const STATUS_HIRED = 'hired';

    public const STATUSES = [
        self::STATUS_SENT,
        self::STATUS_VIEWED,
        self::STATUS_REJECTED,
        self::STATUS_INTERVIEWED,
        self::STATUS_HIRED,
    ];

    /**
     * Get the talent request.
     */
    public function talentRequest(): BelongsTo
    {
        return $this->belongsTo(TalentRequest::class);
    }

    /**
     * Get the pool candidate.
     */
    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class);
    }

    /**
     * Get the interview outcome (for model health tracking).
     */
    public function interviewOutcome(): BelongsTo
    {
        return $this->belongsTo(InterviewOutcome::class);
    }

    /**
     * Mark as viewed by client.
     */
    public function markViewed(): void
    {
        if ($this->presentation_status === self::STATUS_SENT) {
            $this->update(['presentation_status' => self::STATUS_VIEWED]);
        }
    }

    /**
     * Record client feedback.
     */
    public function recordFeedback(string $feedback, ?int $score = null): void
    {
        $this->update([
            'client_feedback' => $feedback,
            'client_score' => $score,
        ]);
    }

    /**
     * Mark as rejected.
     */
    public function markRejected(?string $reason = null): void
    {
        $this->update([
            'presentation_status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Schedule interview.
     */
    public function scheduleInterview(\DateTimeInterface $scheduledAt): void
    {
        $this->update([
            'interview_scheduled_at' => $scheduledAt,
        ]);
    }

    /**
     * Mark as interviewed.
     */
    public function markInterviewed(): void
    {
        $this->update([
            'presentation_status' => self::STATUS_INTERVIEWED,
            'interviewed_at' => now(),
        ]);
    }

    /**
     * Mark as hired.
     */
    public function markHired(?\DateTimeInterface $startDate = null, ?string $outcomeId = null): void
    {
        $this->update([
            'presentation_status' => self::STATUS_HIRED,
            'hired_at' => now(),
            'start_date' => $startDate,
            'interview_outcome_id' => $outcomeId,
        ]);

        // Update talent request hired count
        $this->talentRequest->incrementHiredCount();

        // Update pool candidate status
        $this->poolCandidate->markAsHired();
    }

    /**
     * Check if hired.
     */
    public function isHired(): bool
    {
        return $this->presentation_status === self::STATUS_HIRED;
    }

    /**
     * Check if rejected.
     */
    public function isRejected(): bool
    {
        return $this->presentation_status === self::STATUS_REJECTED;
    }

    /**
     * Check if pending (sent or viewed, waiting for response).
     */
    public function isPending(): bool
    {
        return in_array($this->presentation_status, [
            self::STATUS_SENT,
            self::STATUS_VIEWED,
        ]);
    }

    /**
     * Scope: filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('presentation_status', $status);
    }

    /**
     * Scope: hired only.
     */
    public function scopeHired($query)
    {
        return $query->where('presentation_status', self::STATUS_HIRED);
    }

    /**
     * Scope: pending (sent or viewed).
     */
    public function scopePending($query)
    {
        return $query->whereIn('presentation_status', [
            self::STATUS_SENT,
            self::STATUS_VIEWED,
        ]);
    }

    /**
     * Scope: with feedback.
     */
    public function scopeWithFeedback($query)
    {
        return $query->whereNotNull('client_feedback');
    }
}
