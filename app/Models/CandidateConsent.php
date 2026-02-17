<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateConsent extends Model
{
    use HasUuids;

    protected $fillable = [
        'form_interview_id',
        'consent_type',
        'consent_version',
        'regulation',
        'granted',
        'ip_address',
        'user_agent',
        'collection_method',
        'consented_at',
        'withdrawn_at',
        'expires_at',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'consented_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Consent types
    public const TYPE_DATA_PROCESSING = 'data_processing';
    public const TYPE_DATA_RETENTION = 'data_retention';
    public const TYPE_DATA_SHARING = 'data_sharing';
    public const TYPE_MARKETING = 'marketing';

    // Regulations
    public const REGULATION_KVKK = 'KVKK';
    public const REGULATION_GDPR = 'GDPR';

    // Collection methods
    public const METHOD_WEB_FORM = 'web_form';
    public const METHOD_API = 'api';
    public const METHOD_IMPORT = 'import';

    public function formInterview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class);
    }

    /**
     * Check if consent is currently valid (granted and not expired/withdrawn).
     */
    public function isValid(): bool
    {
        if (!$this->granted) {
            return false;
        }

        if ($this->withdrawn_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Withdraw consent.
     */
    public function withdraw(): void
    {
        $this->update([
            'withdrawn_at' => now(),
        ]);
    }

    /**
     * Scope for valid (active) consents.
     */
    public function scopeValid($query)
    {
        return $query
            ->where('granted', true)
            ->whereNull('withdrawn_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for specific consent type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }
}
