<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobSubdomain extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'domain_id',
        'code',
        'name_tr',
        'name_en',
        'icon',
        'description_tr',
        'description_en',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(JobDomain::class, 'domain_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(JobPosition::class, 'subdomain_id');
    }

    public function getName(string $locale = 'tr'): string
    {
        return $locale === 'en' ? $this->name_en : $this->name_tr;
    }

    public function getDescription(string $locale = 'tr'): ?string
    {
        return $locale === 'en' ? $this->description_en : $this->description_tr;
    }

    public function getFullName(string $locale = 'tr'): string
    {
        $domainName = $this->domain?->getName($locale) ?? '';
        return $domainName ? "{$domainName} > {$this->getName($locale)}" : $this->getName($locale);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_tr');
    }
}
