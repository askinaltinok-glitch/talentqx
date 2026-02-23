<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyCertificateRule extends Model
{
    protected $fillable = [
        'company_id',
        'certificate_type',
        'validity_months',
        'notes',
    ];

    protected $casts = [
        'validity_months' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get validity months for a company + certificate type combination.
     */
    public static function getValidityMonths(string $companyId, string $certType): ?int
    {
        $rule = static::where('company_id', $companyId)
            ->where('certificate_type', $certType)
            ->first();

        return $rule?->validity_months;
    }
}
