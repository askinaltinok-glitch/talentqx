<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosition extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'subdomain_id',
        'archetype_id',
        'code',
        'name_tr',
        'name_en',
        'description_tr',
        'description_en',
        'responsibilities_tr',
        'responsibilities_en',
        'requirements_tr',
        'requirements_en',
        'experience_min_years',
        'experience_max_years',
        'education_level',
        'keywords',
        'scoring_rubric',
        'critical_behaviors',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'keywords' => 'array',
        'scoring_rubric' => 'array',
        'critical_behaviors' => 'array',
        'experience_min_years' => 'integer',
        'experience_max_years' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // Education level constants
    const EDUCATION_NONE = 'none';
    const EDUCATION_HIGH_SCHOOL = 'high_school';
    const EDUCATION_ASSOCIATE = 'associate';
    const EDUCATION_BACHELOR = 'bachelor';
    const EDUCATION_MASTER = 'master';
    const EDUCATION_DOCTORATE = 'doctorate';

    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(JobSubdomain::class, 'subdomain_id');
    }

    public function domain(): BelongsTo
    {
        return $this->subdomain?->domain();
    }

    public function archetype(): BelongsTo
    {
        return $this->belongsTo(RoleArchetype::class, 'archetype_id');
    }

    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(Competency::class, 'position_competencies', 'position_id', 'competency_id')
            ->withPivot(['weight', 'is_critical', 'min_score', 'position_specific_criteria_tr', 'position_specific_criteria_en', 'sort_order'])
            ->withTimestamps();
    }

    public function criticalCompetencies(): BelongsToMany
    {
        return $this->competencies()->wherePivot('is_critical', true);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(PositionQuestion::class, 'position_id');
    }

    public function mandatoryQuestions(): HasMany
    {
        return $this->questions()->where('is_mandatory', true);
    }

    public function positionTemplates(): HasMany
    {
        return $this->hasMany(PositionTemplate::class, 'job_position_id');
    }

    public function getName(string $locale = 'tr'): string
    {
        return $locale === 'en' ? $this->name_en : $this->name_tr;
    }

    public function getDescription(string $locale = 'tr'): ?string
    {
        return $locale === 'en' ? $this->description_en : $this->description_tr;
    }

    public function getFullPath(string $locale = 'tr'): string
    {
        $parts = [];

        if ($this->subdomain) {
            if ($this->subdomain->domain) {
                $parts[] = $this->subdomain->domain->getName($locale);
            }
            $parts[] = $this->subdomain->getName($locale);
        }

        $parts[] = $this->getName($locale);

        return implode(' > ', $parts);
    }

    public function getExperienceRange(): string
    {
        if ($this->experience_max_years) {
            return "{$this->experience_min_years}-{$this->experience_max_years} yıl";
        }
        return "{$this->experience_min_years}+ yıl";
    }

    public function getEducationLabel(string $locale = 'tr'): string
    {
        $labels = [
            self::EDUCATION_NONE => ['tr' => 'Eğitim Şartı Yok', 'en' => 'No Education Required'],
            self::EDUCATION_HIGH_SCHOOL => ['tr' => 'Lise', 'en' => 'High School'],
            self::EDUCATION_ASSOCIATE => ['tr' => 'Ön Lisans', 'en' => 'Associate Degree'],
            self::EDUCATION_BACHELOR => ['tr' => 'Lisans', 'en' => 'Bachelor\'s Degree'],
            self::EDUCATION_MASTER => ['tr' => 'Yüksek Lisans', 'en' => 'Master\'s Degree'],
            self::EDUCATION_DOCTORATE => ['tr' => 'Doktora', 'en' => 'Doctorate'],
        ];

        return $labels[$this->education_level][$locale] ?? '';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_tr');
    }

    public function scopeBySubdomain($query, $subdomainId)
    {
        return $query->where('subdomain_id', $subdomainId);
    }

    public function scopeByArchetype($query, $archetypeId)
    {
        return $query->where('archetype_id', $archetypeId);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name_tr', 'like', "%{$term}%")
              ->orWhere('name_en', 'like', "%{$term}%")
              ->orWhereJsonContains('keywords', $term);
        });
    }
}
