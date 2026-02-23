<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VesselRequirementTemplate extends Model
{
    protected $table = 'vessel_requirement_templates';

    protected $fillable = [
        'vessel_type_key',
        'label',
        'profile_json',
        'draft_profile_json',
        'is_active',
        'status',
        'published_version',
        'version_history',
    ];

    protected $casts = [
        'profile_json' => 'array',
        'draft_profile_json' => 'array',
        'version_history' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function findByTypeKey(string $key): ?self
    {
        return static::where('vessel_type_key', $key)->first();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Get the effective profile (published profile_json, ignoring draft).
     */
    public function getPublishedProfile(): array
    {
        return $this->profile_json ?? [];
    }

    /**
     * Get available version numbers from history.
     */
    public function getVersionNumbers(): array
    {
        $history = $this->version_history ?? [];

        return array_map(fn($entry) => $entry['version'] ?? 0, $history);
    }

    /**
     * Get a specific version's profile from history.
     */
    public function getVersionProfile(int $version): ?array
    {
        $history = $this->version_history ?? [];

        foreach ($history as $entry) {
            if (($entry['version'] ?? 0) === $version) {
                return $entry['profile_json'] ?? null;
            }
        }

        return null;
    }
}
