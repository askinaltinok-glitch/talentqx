<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResolverAuditLog extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'form_interview_id',
        'candidate_id',
        'company_id',
        'phase',
        'input_snapshot',
        'class_detection_output',
        'scenario_set_json',
        'selection_reason',
        'capability_output',
        'final_packet',
    ];

    protected $casts = [
        'phase'                  => 'integer',
        'input_snapshot'         => 'array',
        'class_detection_output' => 'array',
        'scenario_set_json'      => 'array',
        'capability_output'      => 'array',
        'final_packet'           => 'array',
    ];

    public function formInterview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }
}
