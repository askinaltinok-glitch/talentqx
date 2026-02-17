<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmSequence extends Model
{
    use HasUuids;

    protected $table = 'crm_sequences';

    protected $fillable = [
        'name', 'industry_code', 'language',
        'steps', 'active', 'description',
    ];

    protected $casts = [
        'steps' => 'array',
        'active' => 'boolean',
    ];

    // Relationships

    public function enrollments(): HasMany
    {
        return $this->hasMany(CrmSequenceEnrollment::class, 'sequence_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeIndustry($query, string $code)
    {
        return $query->where('industry_code', $code);
    }

    public function scopeLanguage($query, string $lang)
    {
        return $query->where('language', $lang);
    }
}
