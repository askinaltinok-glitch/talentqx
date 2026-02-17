<?php

namespace App\Models;

use App\Models\Traits\IsDemoScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelPrediction extends Model
{
    use HasUuids, IsDemoScoped;

    public $timestamps = false;

    protected $fillable = [
        'form_interview_id',
        'model_version',
        'predicted_outcome_score',
        'predicted_label',
        'explain_json',
        'prediction_type',
        'prediction_reason',
        'is_demo',
        'created_at',
    ];

    protected $casts = [
        'predicted_outcome_score' => 'integer',
        'explain_json' => 'array',
        'is_demo' => 'boolean',
        'created_at' => 'datetime',
    ];

    public const LABEL_GOOD = 'GOOD';
    public const LABEL_BAD = 'BAD';
    public const LABEL_UNKNOWN = 'UNKNOWN';

    public const TYPE_BASELINE = 'baseline';
    public const TYPE_POST_ASSESSMENT = 'post_assessment';
    public const TYPE_MANUAL = 'manual';

    public function formInterview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class);
    }
}
