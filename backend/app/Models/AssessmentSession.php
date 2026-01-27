<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class AssessmentSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_id',
        'template_id',
        'initiated_by',
        'access_token',
        'token_expires_at',
        'used_at',
        'max_attempts',
        'attempts_count',
        'one_time_use',
        'status',
        'started_at',
        'completed_at',
        'time_spent_seconds',
        'responses',
        'ip_address',
        'last_ip_address',
        'user_agent',
        'device_info',
        'access_log',
        'total_cost_usd',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'used_at' => 'datetime',
        'max_attempts' => 'integer',
        'attempts_count' => 'integer',
        'one_time_use' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_spent_seconds' => 'integer',
        'responses' => 'array',
        'device_info' => 'array',
        'access_log' => 'array',
        'total_cost_usd' => 'decimal:6',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->access_token)) {
                // Generate 32+ byte secure random token (64 hex characters)
                $session->access_token = bin2hex(random_bytes(32));
            }
            if (empty($session->token_expires_at)) {
                $session->token_expires_at = now()->addHours(72);
            }
            if (empty($session->max_attempts)) {
                $session->max_attempts = 3;
            }
        });
    }

    /**
     * Check if token can still be used
     */
    public function canUseToken(): bool
    {
        // Check expiration
        if ($this->isExpired()) {
            return false;
        }

        // Check one-time use
        if ($this->one_time_use && $this->used_at !== null) {
            return false;
        }

        // Check max attempts
        if ($this->attempts_count >= $this->max_attempts) {
            return false;
        }

        return true;
    }

    /**
     * Record token access
     */
    public function recordAccess(string $action, ?string $ipAddress = null): void
    {
        $log = $this->access_log ?? [];
        $log[] = [
            'action' => $action,
            'ip' => $ipAddress ?? request()->ip(),
            'timestamp' => now()->toIso8601String(),
            'user_agent' => request()->userAgent(),
        ];

        $this->update([
            'access_log' => $log,
            'last_ip_address' => $ipAddress ?? request()->ip(),
        ]);
    }

    /**
     * Increment attempts counter
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts_count');
    }

    /**
     * Mark token as used (for one-time tokens)
     */
    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    /**
     * Check if IP address changed (potential security risk)
     */
    public function hasIpChanged(?string $currentIp = null): bool
    {
        $currentIp = $currentIp ?? request()->ip();

        if ($this->ip_address && $this->ip_address !== $currentIp) {
            return true;
        }

        return false;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssessmentTemplate::class, 'template_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function result(): HasOne
    {
        return $this->hasOne(AssessmentResult::class, 'session_id');
    }

    public function isExpired(): bool
    {
        return $this->token_expires_at->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canStart(): bool
    {
        return $this->status === 'pending' && $this->canUseToken();
    }

    public function canContinue(): bool
    {
        return $this->status === 'in_progress' && !$this->isExpired();
    }

    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts(): int
    {
        return max(0, $this->max_attempts - $this->attempts_count);
    }

    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'time_spent_seconds' => $this->started_at?->diffInSeconds(now()),
        ]);
    }

    public function addResponse(int $questionOrder, $answer, int $timeSpent): void
    {
        $responses = $this->responses ?? [];
        $responses[] = [
            'question_order' => $questionOrder,
            'answer' => $answer,
            'time_spent' => $timeSpent,
            'answered_at' => now()->toIso8601String(),
        ];
        $this->update(['responses' => $responses]);
    }

    public function getResponseForQuestion(int $questionOrder): ?array
    {
        return collect($this->responses)->firstWhere('question_order', $questionOrder);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeNotExpired($query)
    {
        return $query->where('token_expires_at', '>', now());
    }
}
