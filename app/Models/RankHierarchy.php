<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankHierarchy extends Model
{
    protected $table = 'rank_hierarchy';

    protected $fillable = [
        'canonical_code',
        'stcw_rank_code',
        'department',
        'level',
        'min_sea_months_in_rank',
        'min_total_sea_months',
        'next_rank_code',
        'notes',
    ];

    protected $casts = [
        'level' => 'integer',
        'min_sea_months_in_rank' => 'integer',
        'min_total_sea_months' => 'integer',
    ];

    /**
     * Get rank hierarchy entry by canonical code.
     */
    public static function findByCanonical(string $code): ?self
    {
        return static::where('canonical_code', $code)->first();
    }

    /**
     * Get rank hierarchy entry by STCW seeder rank code.
     */
    public static function findByStcw(string $code): ?self
    {
        return static::where('stcw_rank_code', $code)->first();
    }

    /**
     * Get the next rank in the ladder.
     */
    public function nextRank(): ?self
    {
        if (!$this->next_rank_code) {
            return null;
        }
        return static::findByCanonical($this->next_rank_code);
    }

    /**
     * Get required days in this rank for promotion (months * 30.44).
     */
    public function requiredDaysInRank(): int
    {
        return (int) round($this->min_sea_months_in_rank * 30.44);
    }

    /**
     * Check if this is a top rank (no next rank).
     */
    public function isTopRank(): bool
    {
        return $this->next_rank_code === null;
    }

    /**
     * Get all ranks for a department ordered by level.
     */
    public static function forDepartment(string $department): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('department', $department)->orderBy('level')->get();
    }

    /**
     * Scope: by department.
     */
    public function scopeDepartment($query, string $dept)
    {
        return $query->where('department', $dept);
    }
}
