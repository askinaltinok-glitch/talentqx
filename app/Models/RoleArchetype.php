<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoleArchetype extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name_tr',
        'name_en',
        'description_tr',
        'description_en',
        'level',
        'typical_competencies',
        'interview_focus',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'typical_competencies' => 'array',
        'interview_focus' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // Level constants
    const LEVEL_ENTRY = 1;
    const LEVEL_SPECIALIST = 2;
    const LEVEL_COORDINATOR = 3;
    const LEVEL_MANAGER = 4;
    const LEVEL_LEADER = 5;
    const LEVEL_EXECUTIVE = 6;

    public function positions(): HasMany
    {
        return $this->hasMany(JobPosition::class, 'archetype_id');
    }

    public function getName(string $locale = 'tr'): string
    {
        return $locale === 'en' ? $this->name_en : $this->name_tr;
    }

    public function getDescription(string $locale = 'tr'): ?string
    {
        return $locale === 'en' ? $this->description_en : $this->description_tr;
    }

    public function getLevelName(string $locale = 'tr'): string
    {
        $names = [
            self::LEVEL_ENTRY => ['tr' => 'Giriş Seviye', 'en' => 'Entry Level'],
            self::LEVEL_SPECIALIST => ['tr' => 'Uzman', 'en' => 'Specialist'],
            self::LEVEL_COORDINATOR => ['tr' => 'Koordinatör', 'en' => 'Coordinator'],
            self::LEVEL_MANAGER => ['tr' => 'Yönetici', 'en' => 'Manager'],
            self::LEVEL_LEADER => ['tr' => 'Lider', 'en' => 'Leader'],
            self::LEVEL_EXECUTIVE => ['tr' => 'Üst Düzey Yönetici', 'en' => 'Executive'],
        ];

        return $names[$this->level][$locale] ?? $names[self::LEVEL_ENTRY][$locale];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('level')->orderBy('sort_order');
    }
}
