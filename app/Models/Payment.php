<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory, HasUuids;

    // Payment statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    // Payment providers
    public const PROVIDER_IYZICO = 'iyzico';
    public const PROVIDER_STRIPE = 'stripe';
    public const PROVIDER_MANUAL = 'manual';

    protected $fillable = [
        'company_id',
        'package_id',
        'payment_provider',
        'payment_id',
        'conversation_id',
        'status',
        'amount',
        'currency',
        'credits_added',
        'provider_response',
        'metadata',
        'failure_reason',
        'paid_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credits_added' => 'integer',
        'provider_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * Get the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the package.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(CreditPackage::class, 'package_id');
    }

    /**
     * Get the invoice for this payment.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark payment as completed and add credits.
     */
    public function markAsCompleted(string $paymentId, array $providerResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'payment_id' => $paymentId,
            'provider_response' => $providerResponse,
            'paid_at' => now(),
        ]);

        // Add credits to company
        if ($this->credits_added > 0) {
            $this->company->increment('bonus_credits', $this->credits_added);
        }
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(string $reason, array $providerResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'provider_response' => $providerResponse,
        ]);
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        $symbol = match ($this->currency) {
            'EUR' => '€',
            'USD' => '$',
            default => '₺',
        };
        return $symbol . number_format($this->amount, 2, ',', '.');
    }

    /**
     * Scope for completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Convert to API response.
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'package' => $this->package ? [
                'id' => $this->package->id,
                'name' => $this->package->name,
                'credits' => $this->package->credits,
            ] : null,
            'amount' => (float) $this->amount,
            'amount_formatted' => $this->getFormattedAmount(),
            'currency' => $this->currency,
            'credits_added' => $this->credits_added,
            'status' => $this->status,
            'payment_provider' => $this->payment_provider,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
