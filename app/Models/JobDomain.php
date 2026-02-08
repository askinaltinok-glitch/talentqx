<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class JobDomain extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
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

    public function subdomains(): HasMany
    {
        return $this->hasMany(JobSubdomain::class, 'domain_id');
    }

    public function positions(): HasManyThrough
    {
        return $this->hasManyThrough(
            JobPosition::class,
            JobSubdomain::class,
            'domain_id',
            'subdomain_id'
        );
    }

    public function getName(string $locale = 'tr'): string
    {
        return $locale === 'en' ? $this->name_en : $this->name_tr;
    }

    public function getDescription(string $locale = 'tr'): ?string
    {
        return $locale === 'en' ? $this->description_en : $this->description_tr;
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
