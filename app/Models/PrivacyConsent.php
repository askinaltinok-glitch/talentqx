<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PrivacyConsent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'email',
        'phone',
        'full_name',
        'consent_type',
        'regime',
        'policy_version',
        'locale',
        'country',
        'ip_address',
        'user_agent',
        'source',
        'form_type',
        'accepted_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    /**
     * Get the subject of the consent (Lead, Employee, User)
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for active (not withdrawn) consents
     */
    public function scopeActive($query)
    {
        return $query->whereNull('withdrawn_at');
    }

    /**
     * Scope by regime
     */
    public function scopeByRegime($query, string $regime)
    {
        return $query->where('regime', $regime);
    }

    /**
     * Scope by source
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Check if consent is still active
     */
    public function isActive(): bool
    {
        return is_null($this->withdrawn_at);
    }

    /**
     * Withdraw consent
     */
    public function withdraw(): void
    {
        $this->update(['withdrawn_at' => now()]);
    }
}
