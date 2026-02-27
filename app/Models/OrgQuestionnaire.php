<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrgQuestionnaire extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'org_questionnaires';
    protected $fillable = ['tenant_id','code','version','status','title','description','scoring_schema'];
    protected $casts = ['title' => 'array', 'description' => 'array', 'scoring_schema' => 'array'];

    public function questions() { return $this->hasMany(OrgQuestion::class, 'questionnaire_id')->orderBy('sort_order'); }
}
