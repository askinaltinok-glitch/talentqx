<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyCrewMember extends Model
{
    protected $fillable = [
        'company_id',
        'full_name',
        'email',
        'phone',
        'role_code',
        'department',
        'nationality',
        'language',
        'passport_no',
        'seamans_book_no',
        'date_of_birth',
        'vessel_name',
        'vessel_country',
        'contract_start_at',
        'contract_end_at',
        'rank_raw',
        'import_run_id',
        'meta',
    ];

    protected $casts = [
        'meta'              => 'array',
        'date_of_birth'     => 'date',
        'contract_start_at' => 'date',
        'contract_end_at'   => 'date',
    ];

    public function certificates(): HasMany
    {
        return $this->hasMany(CrewMemberCertificate::class, 'crew_member_id');
    }
}
