<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'position_id',
        'competency_id',
        'question_type',
        'question_tr',
        'question_en',
        'question_de',
        'question_fr',
        'question_ar',
        'follow_up_tr',
        'follow_up_en',
        'follow_up_de',
        'follow_up_fr',
        'follow_up_ar',
        'expected_indicators',
        'red_flag_indicators',
        'scoring_guide',
        'difficulty_level',
        'time_limit_seconds',
        'sort_order',
        'is_mandatory',
        'is_active',
    ];

    protected $casts = [
        'expected_indicators' => 'array',
        'red_flag_indicators' => 'array',
        'scoring_guide' => 'array',
        'difficulty_level' => 'integer',
        'time_limit_seconds' => 'integer',
        'sort_order' => 'integer',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Question type constants
    const TYPE_BEHAVIORAL = 'behavioral';
    const TYPE_SITUATIONAL = 'situational';
    const TYPE_TECHNICAL = 'technical';
    const TYPE_EXPERIENCE = 'experience';

    // Difficulty level constants
    const DIFFICULTY_EASY = 1;
    const DIFFICULTY_MEDIUM = 2;
    const DIFFICULTY_HARD = 3;

    public function position(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class, 'position_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'competency_id');
    }

    public function getQuestion(string $locale = 'tr'): string
    {
        $col = "question_{$locale}";
        return $this->{$col} ?? $this->question_tr ?? $this->question_en ?? '';
    }

    public function getFollowUp(string $locale = 'tr'): ?string
    {
        $col = "follow_up_{$locale}";
        return $this->{$col} ?? $this->follow_up_tr ?? $this->follow_up_en;
    }

    public function getTypeLabel(string $locale = 'tr'): string
    {
        $labels = [
            self::TYPE_BEHAVIORAL => ['tr' => 'Davranışsal', 'en' => 'Behavioral'],
            self::TYPE_SITUATIONAL => ['tr' => 'Durumsal', 'en' => 'Situational'],
            self::TYPE_TECHNICAL => ['tr' => 'Teknik', 'en' => 'Technical'],
            self::TYPE_EXPERIENCE => ['tr' => 'Deneyim', 'en' => 'Experience'],
        ];

        return $labels[$this->question_type][$locale] ?? $this->question_type;
    }

    public function getDifficultyLabel(string $locale = 'tr'): string
    {
        $labels = [
            self::DIFFICULTY_EASY => ['tr' => 'Kolay', 'en' => 'Easy'],
            self::DIFFICULTY_MEDIUM => ['tr' => 'Orta', 'en' => 'Medium'],
            self::DIFFICULTY_HARD => ['tr' => 'Zor', 'en' => 'Hard'],
        ];

        return $labels[$this->difficulty_level][$locale] ?? '';
    }

    public function getTimeLimitFormatted(): string
    {
        $minutes = floor($this->time_limit_seconds / 60);
        $seconds = $this->time_limit_seconds % 60;

        if ($minutes > 0 && $seconds > 0) {
            return "{$minutes} dk {$seconds} sn";
        } elseif ($minutes > 0) {
            return "{$minutes} dakika";
        }
        return "{$seconds} saniye";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('question_type', $type);
    }

    public function scopeByDifficulty($query, int $level)
    {
        return $query->where('difficulty_level', $level);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }
}
