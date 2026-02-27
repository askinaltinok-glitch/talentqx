<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrgAssessmentAnswer extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'org_assessment_answers';
    protected $fillable = ['assessment_id','question_id','value'];

    public function assessment() { return $this->belongsTo(OrgAssessment::class, 'assessment_id'); }
}
