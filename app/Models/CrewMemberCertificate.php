<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CrewMemberCertificate extends Model
{
    use HasUuids;

    protected $fillable = [
        'crew_member_id',
        'certificate_type',
        'certificate_code',
        'issuing_country',
        'expires_at',
        'expiry_source',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    public function crewMember(): BelongsTo
    {
        return $this->belongsTo(CompanyCrewMember::class, 'crew_member_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function daysRemaining(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return (int) Carbon::now()->startOfDay()->diffInDays($this->expires_at->startOfDay(), false);
    }
}
