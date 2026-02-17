<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmtpCircuitBreaker extends Model
{
    protected $table = 'smtp_circuit_breakers';

    protected $fillable = [
        'key', 'failures', 'successes', 'last_failure_at', 'opened_until', 'state',
    ];

    protected $casts = [
        'last_failure_at' => 'datetime',
        'opened_until' => 'datetime',
    ];

    public static function forKey(string $key = 'smtp'): self
    {
        return static::firstOrCreate(['key' => $key], [
            'failures' => 0,
            'successes' => 0,
            'state' => 'closed',
        ]);
    }

    public function isOpen(): bool
    {
        if ($this->state !== 'open') {
            return false;
        }
        if ($this->opened_until && $this->opened_until->isPast()) {
            $this->update(['state' => 'half_open']);
            return false;
        }
        return true;
    }

    public function isHalfOpen(): bool
    {
        if ($this->state === 'open' && $this->opened_until && $this->opened_until->isPast()) {
            $this->update(['state' => 'half_open']);
            return true;
        }
        return $this->state === 'half_open';
    }

    public function isClosed(): bool
    {
        return $this->state === 'closed';
    }

    public function recordFailure(): bool
    {
        $threshold = config('crm_mail.smtp_circuit_breaker.failure_threshold', 8);
        $openMinutes = config('crm_mail.smtp_circuit_breaker.open_minutes', 30);

        $this->increment('failures');
        $this->update(['last_failure_at' => now(), 'successes' => 0]);

        if ($this->failures >= $threshold) {
            $this->trip($openMinutes);
            return true;
        }
        return false;
    }

    public function recordSuccess(): void
    {
        $this->increment('successes');

        if ($this->state === 'half_open' && $this->successes >= 3) {
            $this->close();
        }
    }

    public function trip(int $minutes): void
    {
        $this->update([
            'state' => 'open',
            'opened_until' => now()->addMinutes($minutes),
        ]);
    }

    public function close(): void
    {
        $this->update([
            'state' => 'closed',
            'failures' => 0,
            'successes' => 0,
            'opened_until' => null,
        ]);
    }
}
