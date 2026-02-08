<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpectationQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'category',
        'question_tr',
        'question_en',
        'answer_type',
        'answer_options',
        'evaluation_note_tr',
        'evaluation_note_en',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'answer_options' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // Category constants
    const CATEGORY_SALARY = 'salary';
    const CATEGORY_WORK_HOURS = 'work_hours';
    const CATEGORY_BENEFITS = 'benefits';
    const CATEGORY_GROWTH = 'growth';
    const CATEGORY_CULTURE = 'culture';
    const CATEGORY_LOCATION = 'location';
    const CATEGORY_GENERAL = 'general';

    // Answer type constants
    const ANSWER_OPEN = 'open';
    const ANSWER_SINGLE_CHOICE = 'single_choice';
    const ANSWER_MULTI_CHOICE = 'multi_choice';
    const ANSWER_SCALE = 'scale';
    const ANSWER_NUMERIC = 'numeric';

    public function getQuestion(string $locale = 'tr'): string
    {
        return $locale === 'en' ? $this->question_en : $this->question_tr;
    }

    public function getEvaluationNote(string $locale = 'tr'): ?string
    {
        return $locale === 'en' ? $this->evaluation_note_en : $this->evaluation_note_tr;
    }

    public function getCategoryLabel(string $locale = 'tr'): string
    {
        $labels = [
            self::CATEGORY_SALARY => ['tr' => 'Maaş ve Ücret', 'en' => 'Salary & Compensation'],
            self::CATEGORY_WORK_HOURS => ['tr' => 'Çalışma Saatleri', 'en' => 'Work Hours'],
            self::CATEGORY_BENEFITS => ['tr' => 'Yan Haklar', 'en' => 'Benefits'],
            self::CATEGORY_GROWTH => ['tr' => 'Kariyer Gelişimi', 'en' => 'Career Growth'],
            self::CATEGORY_CULTURE => ['tr' => 'Şirket Kültürü', 'en' => 'Company Culture'],
            self::CATEGORY_LOCATION => ['tr' => 'Lokasyon', 'en' => 'Location'],
            self::CATEGORY_GENERAL => ['tr' => 'Genel', 'en' => 'General'],
        ];

        return $labels[$this->category][$locale] ?? $this->category;
    }

    public function getAnswerTypeLabel(string $locale = 'tr'): string
    {
        $labels = [
            self::ANSWER_OPEN => ['tr' => 'Açık Uçlu', 'en' => 'Open-Ended'],
            self::ANSWER_SINGLE_CHOICE => ['tr' => 'Tek Seçim', 'en' => 'Single Choice'],
            self::ANSWER_MULTI_CHOICE => ['tr' => 'Çoklu Seçim', 'en' => 'Multiple Choice'],
            self::ANSWER_SCALE => ['tr' => 'Ölçek', 'en' => 'Scale'],
            self::ANSWER_NUMERIC => ['tr' => 'Sayısal', 'en' => 'Numeric'],
        ];

        return $labels[$this->answer_type][$locale] ?? $this->answer_type;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }
}
