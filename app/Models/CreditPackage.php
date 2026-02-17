<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CreditPackage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'credits',
        'price_try',
        'price_eur',
        'description',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'credits' => 'integer',
        'price_try' => 'decimal:2',
        'price_eur' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($package) {
            if (empty($package->slug)) {
                $package->slug = Str::slug($package->name);
            }
        });
    }

    /**
     * Scope for active packages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for sorted packages.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('credits');
    }

    /**
     * Get payments for this package.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'package_id');
    }

    /**
     * Get price in specified currency.
     */
    public function getPrice(string $currency = 'TRY'): float
    {
        return match (strtoupper($currency)) {
            'EUR' => (float) ($this->price_eur ?? 0),
            default => (float) $this->price_try,
        };
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPrice(string $currency = 'TRY'): string
    {
        $price = $this->getPrice($currency);
        $symbol = match (strtoupper($currency)) {
            'EUR' => '€',
            default => '₺',
        };
        return $symbol . number_format($price, 2, ',', '.');
    }

    /**
     * Get price per credit.
     */
    public function getPricePerCredit(string $currency = 'TRY'): float
    {
        if ($this->credits <= 0) {
            return 0;
        }
        return $this->getPrice($currency) / $this->credits;
    }

    /**
     * Convert to array for API response.
     */
    public function toApiResponse(string $currency = 'TRY'): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'credits' => $this->credits,
            'price' => $this->getPrice($currency),
            'price_formatted' => $this->getFormattedPrice($currency),
            'price_per_credit' => round($this->getPricePerCredit($currency), 2),
            'currency' => $currency,
            'description' => $this->description,
            'is_featured' => $this->is_featured,
        ];
    }
}
