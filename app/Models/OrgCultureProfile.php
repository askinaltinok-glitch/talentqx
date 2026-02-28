<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrgCultureProfile extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'org_culture_profiles';
    protected $fillable = [
        'tenant_id', 'employee_id', 'assessment_id',
        'clan_current', 'clan_preferred',
        'adhocracy_current', 'adhocracy_preferred',
        'market_current', 'market_preferred',
        'hierarchy_current', 'hierarchy_preferred',
        'computed_at',
    ];
    protected $casts = ['computed_at' => 'datetime'];
}
