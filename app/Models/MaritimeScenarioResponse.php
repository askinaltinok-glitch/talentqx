<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaritimeScenarioResponse extends Model
{
    use HasUuids;

    protected $fillable = [
        'form_interview_id',
        'scenario_id',
        'slot',
        'raw_answer_text',
        'structured_actions_json',
        'regulation_mentions_json',
    ];

    protected $casts = [
        'slot'                     => 'integer',
        'structured_actions_json'  => 'array',
        'regulation_mentions_json' => 'array',
    ];

    public function formInterview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class);
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(MaritimeScenario::class, 'scenario_id');
    }
}
