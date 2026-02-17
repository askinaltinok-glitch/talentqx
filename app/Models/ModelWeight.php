<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModelWeight extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'model_version',
        'weights_json',
        'is_active',
        'is_frozen',
        'frozen_at',
        'frozen_notes',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'weights_json' => 'array',
        'is_active' => 'boolean',
        'is_frozen' => 'boolean',
        'frozen_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get the currently active model weights.
     */
    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Get the latest model weights (prefer active, fallback to newest).
     */
    public static function latest(): ?self
    {
        $active = static::active();
        if ($active) {
            return $active;
        }

        return static::orderByDesc('created_at')->first();
    }

    /**
     * Get weights by version.
     */
    public static function version(string $version): ?self
    {
        return static::where('model_version', $version)->first();
    }

    /**
     * List all versions with their status.
     */
    public static function listVersions(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'model_version', 'is_active', 'notes', 'created_at']);
    }

    /**
     * Activate this version and deactivate others.
     */
    public function activate(): bool
    {
        if ($this->is_frozen) {
            return false;
        }
        static::where('is_active', true)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
        return true;
    }

    public function freeze(?string $notes = null): void
    {
        $this->update([
            'is_frozen' => true,
            'frozen_at' => now(),
            'frozen_notes' => $notes,
        ]);
    }

    public function unfreeze(): void
    {
        $this->update([
            'is_frozen' => false,
            'frozen_at' => null,
            'frozen_notes' => null,
        ]);
    }

    /**
     * Get the next version number.
     */
    public static function nextVersion(): string
    {
        $latest = static::orderByDesc('created_at')->first();
        if (!$latest) {
            return 'ml_v0.0.1';
        }

        // Parse version: ml_v0.0.1 -> 0.0.1
        if (preg_match('/ml_v(\d+)\.(\d+)\.(\d+)/', $latest->model_version, $m)) {
            $patch = (int) $m[3] + 1;
            return "ml_v{$m[1]}.{$m[2]}.{$patch}";
        }

        if (preg_match('/ml_v(\d+)/', $latest->model_version, $m)) {
            return "ml_v{$m[1]}.0.1";
        }

        // Handle timestamp-based versions
        if (preg_match('/ml_v1_\d{8}_\d{4}/', $latest->model_version)) {
            return 'ml_v1_' . now()->format('Ymd_Hi');
        }

        return 'ml_v0.0.1';
    }
}
