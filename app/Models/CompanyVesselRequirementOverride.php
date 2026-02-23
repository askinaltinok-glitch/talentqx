<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyVesselRequirementOverride extends Model
{
    protected $table = 'company_vessel_requirement_overrides';

    protected $fillable = [
        'company_id',
        'vessel_type_key',
        'overrides_json',
    ];

    protected $casts = [
        'overrides_json' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function forCompanyAndType(string $companyId, string $typeKey): ?self
    {
        return static::where('company_id', $companyId)
            ->where('vessel_type_key', $typeKey)
            ->first();
    }
}
