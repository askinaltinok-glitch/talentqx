<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrgQuestion extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'org_questions';
    protected $fillable = ['questionnaire_id','dimension','is_reverse','sort_order','text'];
    protected $casts = ['is_reverse' => 'bool', 'text' => 'array'];

    public function questionnaire() { return $this->belongsTo(OrgQuestionnaire::class, 'questionnaire_id'); }
}
