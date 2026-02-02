<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'job_postings';

    protected $fillable = [
        'company_id',
        'branch_id',
        'template_id',
        'created_by',
        'title',
        'slug',
        'role_code',
        'description',
        'location',
        'employment_type',
        'experience_years',
        'competencies',
        'red_flags',
        'question_rules',
        'scoring_rubric',
        'interview_settings',
        'status',
        'published_at',
        'closes_at',
        'qr_file_path',
        'apply_url',
    ];

    protected $casts = [
        'competencies' => 'array',
        'red_flags' => 'array',
        'question_rules' => 'array',
        'scoring_rubric' => 'array',
        'interview_settings' => 'array',
        'published_at' => 'datetime',
        'closes_at' => 'datetime',
        'experience_years' => 'integer',
    ];

    protected $attributes = [
        'status' => 'draft',
        'interview_settings' => '{"max_duration_minutes":30,"questions_count":10,"allow_video":true,"allow_audio_only":true,"time_per_question_seconds":180}',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PositionTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(JobQuestion::class)->orderBy('question_order');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function getEffectiveCompetencies(): array
    {
        return $this->competencies ?? $this->template?->competencies ?? [];
    }

    public function getEffectiveRedFlags(): array
    {
        return $this->red_flags ?? $this->template?->red_flags ?? [];
    }

    public function getEffectiveQuestionRules(): array
    {
        return $this->question_rules ?? $this->template?->question_rules ?? [];
    }

    public function getEffectiveScoringRubric(): array
    {
        return $this->scoring_rubric ?? $this->template?->scoring_rubric ?? [];
    }

    public function isActive(): bool
    {
        return $this->status === 'active' &&
               ($this->closes_at === null || $this->closes_at->isFuture());
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('closes_at')
                  ->orWhere('closes_at', '>', now());
            });
    }
}
