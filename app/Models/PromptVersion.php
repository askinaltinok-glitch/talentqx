<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptVersion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'role_type',
        'version',
        'prompt_text',
        'variables',
        'is_active',
        'created_by',
        'change_notes',
    ];

    protected $casts = [
        'version' => 'integer',
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the active prompt for a specific name and role type
     */
    public static function getActive(string $name, ?string $roleType = null): ?self
    {
        return static::where('name', $name)
            ->where('role_type', $roleType)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Get the latest version number for a name/role combination
     */
    public static function getLatestVersion(string $name, ?string $roleType = null): int
    {
        return static::where('name', $name)
            ->where('role_type', $roleType)
            ->max('version') ?? 0;
    }

    /**
     * Create a new version based on existing one
     */
    public function createNewVersion(string $promptText, ?string $changeNotes = null): self
    {
        // Deactivate current version
        $this->update(['is_active' => false]);

        // Create new version
        return static::create([
            'name' => $this->name,
            'role_type' => $this->role_type,
            'version' => $this->version + 1,
            'prompt_text' => $promptText,
            'variables' => $this->variables,
            'is_active' => true,
            'created_by' => auth()->id(),
            'change_notes' => $changeNotes,
        ]);
    }

    /**
     * Render the prompt with variables
     */
    public function render(array $variables = []): string
    {
        $text = $this->prompt_text;

        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    public function scopeByRoleType($query, ?string $roleType)
    {
        return $query->where('role_type', $roleType);
    }
}
