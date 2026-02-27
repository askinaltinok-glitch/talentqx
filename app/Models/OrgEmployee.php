<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrgEmployee extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'org_employees';
    protected $fillable = [
        'tenant_id','external_employee_ref','full_name','email','phone_e164',
        'department_code','position_code','status',
    ];

    public function consents() { return $this->hasMany(OrgEmployeeConsent::class, 'employee_id'); }
    public function assessments() { return $this->hasMany(OrgAssessment::class, 'employee_id'); }
    public function workstyleProfiles() { return $this->hasMany(OrgWorkstyleProfile::class, 'employee_id'); }
}
