<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrgEmployeeConsent extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'org_employee_consents';
    protected $fillable = [
        'tenant_id','employee_id','consent_version','consented_at','withdrawn_at','delete_requested_at',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'delete_requested_at' => 'datetime',
    ];

    public function employee() { return $this->belongsTo(OrgEmployee::class, 'employee_id'); }
}
