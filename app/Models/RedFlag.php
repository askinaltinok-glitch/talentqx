<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TalentQX Karar Motoru - Kirmizi Bayraklar
 */
class RedFlag extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name_tr',
        'name_en',
        'severity',
        'description_tr',
        'description_en',
        'trigger_phrases',
        'behavioral_patterns',
        'detection_method',
        'impact',
        'analysis_note_tr',
        'analysis_note_en',
        'causes_auto_reject',
        'max_score_override',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'trigger_phrases' => 'array',
        'behavioral_patterns' => 'array',
        'impact' => 'array',
        'causes_auto_reject' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Severity levels
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_LOW = 'low';

    // Red flag codes
    public const CODE_BLAME = 'RF_BLAME';
    public const CODE_INCONSIST = 'RF_INCONSIST';
    public const CODE_EGO = 'RF_EGO';
    public const CODE_AVOID = 'RF_AVOID';
    public const CODE_AGGRESSION = 'RF_AGGRESSION';
    public const CODE_UNSTABLE = 'RF_UNSTABLE';

    // Detection methods
    public const DETECT_PHRASE_MATCH = 'phrase_match';
    public const DETECT_PATTERN_ANALYSIS = 'pattern_analysis';
    public const DETECT_CROSS_REFERENCE = 'cross_reference';

    /**
     * Scope: Only active flags
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: Critical flags only
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Get all active flags ordered
     */
    public static function getAllActive()
    {
        return static::active()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Find by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get name by locale
     */
    public function getName(string $locale = 'tr'): string
    {
        return $locale === 'en' ? ($this->name_en ?? $this->name_tr) : $this->name_tr;
    }

    /**
     * Get analysis note by locale
     */
    public function getAnalysisNote(string $locale = 'tr'): string
    {
        return $locale === 'en' ? ($this->analysis_note_en ?? $this->analysis_note_tr) : $this->analysis_note_tr;
    }

    /**
     * Check if text contains any trigger phrases
     */
    public function checkTriggerPhrases(string $text): array
    {
        $detected = [];
        $textLower = mb_strtolower($text, 'UTF-8');

        foreach ($this->trigger_phrases as $phrase) {
            $phraseLower = mb_strtolower($phrase, 'UTF-8');
            if (mb_strpos($textLower, $phraseLower) !== false) {
                $detected[] = $phrase;
            }
        }

        return $detected;
    }

    /**
     * Get for AI prompt
     */
    public static function getForAIPrompt(): array
    {
        return static::active()
            ->orderBy('severity')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($f) => [
                'code' => $f->code,
                'name' => $f->name_tr,
                'severity' => $f->severity,
                'trigger_phrases' => $f->trigger_phrases,
                'behavioral_patterns' => $f->behavioral_patterns,
                'causes_auto_reject' => $f->causes_auto_reject,
            ])
            ->toArray();
    }

    /**
     * Get severity label
     */
    public function getSeverityLabel(string $locale = 'tr'): string
    {
        $labels = [
            self::SEVERITY_CRITICAL => ['tr' => 'Kritik', 'en' => 'Critical'],
            self::SEVERITY_HIGH => ['tr' => 'Yuksek', 'en' => 'High'],
            self::SEVERITY_MEDIUM => ['tr' => 'Orta', 'en' => 'Medium'],
            self::SEVERITY_LOW => ['tr' => 'Dusuk', 'en' => 'Low'],
        ];

        return $labels[$this->severity][$locale] ?? $this->severity;
    }

    /**
     * Get severity color
     */
    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => '#b71c1c',
            self::SEVERITY_HIGH => '#c62828',
            self::SEVERITY_MEDIUM => '#ef6c00',
            self::SEVERITY_LOW => '#ffc107',
            default => '#9e9e9e',
        };
    }
}
