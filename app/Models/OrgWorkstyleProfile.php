<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrgWorkstyleProfile extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'org_workstyle_profiles';
    protected $fillable = [
        'tenant_id','employee_id','assessment_id',
        'planning_score','social_score','cooperation_score','stability_score','adaptability_score',
        'computed_at',
    ];
    protected $casts = ['computed_at' => 'datetime'];
}
