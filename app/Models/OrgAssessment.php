<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrgAssessment extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'org_assessments';
    protected $fillable = [
        'tenant_id','employee_id','questionnaire_id','status',
        'started_at','completed_at','next_due_at',
    ];
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_due_at' => 'datetime',
    ];

    public function employee() { return $this->belongsTo(OrgEmployee::class, 'employee_id'); }
    public function questionnaire() { return $this->belongsTo(OrgQuestionnaire::class, 'questionnaire_id'); }
    public function answers() { return $this->hasMany(OrgAssessmentAnswer::class, 'assessment_id'); }
    public function profile() { return $this->hasOne(OrgWorkstyleProfile::class, 'assessment_id'); }
}
