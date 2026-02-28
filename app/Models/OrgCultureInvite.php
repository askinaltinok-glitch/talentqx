<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrgCultureInvite extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'org_culture_invites';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'email',
        'token',
        'sent_at',
        'opened_at',
        'created_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(OrgEmployee::class, 'employee_id');
    }
}
