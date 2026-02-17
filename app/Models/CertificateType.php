<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CertificateType — Master list of maritime certificate types.
 *
 * Categories: STCW, MEDICAL, FLAG, COMPANY, MLC, OFFICER, ENGINE, SPECIAL
 * Seeded with real IMO/STCW certificate structure.
 */
class CertificateType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'is_mandatory',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Categories
    public const CATEGORY_STCW = 'STCW';
    public const CATEGORY_MEDICAL = 'MEDICAL';
    public const CATEGORY_FLAG = 'FLAG';
    public const CATEGORY_COMPANY = 'COMPANY';
    public const CATEGORY_MLC = 'MLC';
    public const CATEGORY_OFFICER = 'OFFICER';
    public const CATEGORY_ENGINE = 'ENGINE';
    public const CATEGORY_SPECIAL = 'SPECIAL';

    public const CATEGORIES = [
        self::CATEGORY_STCW,
        self::CATEGORY_OFFICER,
        self::CATEGORY_ENGINE,
        self::CATEGORY_SPECIAL,
        self::CATEGORY_MEDICAL,
        self::CATEGORY_FLAG,
        self::CATEGORY_MLC,
        self::CATEGORY_COMPANY,
    ];

    /**
     * Scope: active certificates only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: mandatory only.
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Get all active types grouped by category.
     */
    public static function getGroupedByCategory(): array
    {
        return static::active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category')
            ->toArray();
    }

    /**
     * Find by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
