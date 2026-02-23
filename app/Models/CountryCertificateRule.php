<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryCertificateRule extends Model
{
    protected $fillable = [
        'country_code',
        'certificate_type',
        'validity_months',
        'notes',
    ];

    protected $casts = [
        'validity_months' => 'integer',
    ];

    /**
     * Get validity months for a country + certificate type combination.
     */
    public static function getValidityMonths(string $countryCode, string $certType): ?int
    {
        $rule = static::where('country_code', strtoupper($countryCode))
            ->where('certificate_type', $certType)
            ->first();

        return $rule?->validity_months;
    }
}
