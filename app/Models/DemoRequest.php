<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DemoRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'full_name',
        'company',
        'email',
        'country',
        'company_type',
        'fleet_size',
        'active_crew',
        'monthly_hires',
        'vessel_types',
        'main_ranks',
        'message',
        'locale',
        'source',
        'ip',
        'user_agent',
        'status',
        'notes',
    ];

    protected $casts = [
        'vessel_types' => 'array',
        'main_ranks' => 'array',
    ];
}
