<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyCrewMember extends Model
{
    protected $fillable = [
        'company_id',
        'full_name',
        'email',
        'phone',
        'role_code',
        'department',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
