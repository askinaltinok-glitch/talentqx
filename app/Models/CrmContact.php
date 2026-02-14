<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmContact extends Model
{
    use HasUuids;

    protected $table = 'crm_contacts';

    protected $fillable = [
        'company_id', 'full_name', 'title', 'email', 'phone',
        'linkedin_url', 'preferred_language',
        'consent_marketing', 'consent_meta',
    ];

    protected $casts = [
        'consent_marketing' => 'boolean',
        'consent_meta' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(CrmCompany::class, 'company_id');
    }

    public function leads()
    {
        return $this->hasMany(CrmLead::class, 'contact_id');
    }

    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }
}
