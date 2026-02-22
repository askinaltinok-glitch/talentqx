<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyQuestion extends Model
{
    use HasUuids;

    protected $fillable = [
        'dimension_id',
        'role_scope',
        'operation_scope',
        'vessel_scope',
        'difficulty',
        'question_text',
        'rubric',
        'is_active',
    ];

    protected $casts = [
        'question_text' => 'array',
        'rubric' => 'array',
        'difficulty' => 'integer',
        'is_active' => 'boolean',
    ];

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(CompetencyDimension::class, 'dimension_id');
    }

    public function scopeForRole($query, string $roleScope)
    {
        if ($roleScope === 'ALL') {
            // Generic scope: only return questions marked for ALL roles
            return $query->where('role_scope', 'ALL');
        }

        // Specific role: return role-specific + generic questions
        return $query->whereIn('role_scope', [$roleScope, 'ALL']);
    }

    public function scopeForVessel($query, string $vesselScope)
    {
        return $query->whereIn('vessel_scope', [$vesselScope, 'all']);
    }

    public function scopeForOperation($query, string $operationScope)
    {
        return $query->whereIn('operation_scope', [$operationScope, 'both']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
