<?php

namespace App\Models;

use App\Models\Traits\IsDemoScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelFeature extends Model
{
    use HasUuids, IsDemoScoped;

    protected $fillable = [
        'form_interview_id',
        'industry_code',
        'position_code',
        'language',
        'country_code',
        'source_channel',
        'competency_scores_json',
        'risk_flags_json',
        'raw_final_score',
        'calibrated_score',
        'z_score',
        'policy_decision',
        'policy_code',
        'template_json_sha256',
        'answers_meta_json',
        // Assessment fields (maritime)
        'english_score',
        'english_provider',
        'video_present',
        'video_provider',
        'video_url',
        'is_demo',
    ];

    protected $casts = [
        'competency_scores_json' => 'array',
        'risk_flags_json' => 'array',
        'answers_meta_json' => 'array',
        'raw_final_score' => 'integer',
        'calibrated_score' => 'integer',
        'z_score' => 'decimal:3',
        'english_score' => 'integer',
        'video_present' => 'boolean',
        'is_demo' => 'boolean',
    ];

    public function formInterview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class);
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class, 'form_interview_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(ModelPrediction::class, 'form_interview_id', 'form_interview_id');
    }
}
