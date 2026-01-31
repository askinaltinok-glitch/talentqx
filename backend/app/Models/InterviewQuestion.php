<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewQuestion extends Model
{
    protected $fillable = [
        'role_key',
        'context_key',
        'locale',
        'type',
        'prompt',
        'order_no',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_no' => 'integer',
        'meta' => 'array',
    ];

    // Accessor for backward compatibility
    public function getQuestionTextAttribute(): string
    {
        return $this->prompt;
    }

    public function getQuestionKeyAttribute(): ?string
    {
        return $this->meta['key'] ?? null;
    }

    public function getDimensionAttribute(): ?string
    {
        return $this->meta['dimension'] ?? $this->type;
    }

    public function getOrderAttribute(): int
    {
        return $this->order_no;
    }

    /**
     * Get questions for a role and context
     */
    public static function getForRoleAndContext(
        string $roleKey,
        ?string $contextKey,
        string $locale = 'tr'
    ): \Illuminate\Database\Eloquent\Collection {
        return static::where('role_key', $roleKey)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->where(function ($query) use ($contextKey) {
                $query->whereNull('context_key')
                    ->orWhere('context_key', $contextKey);
            })
            ->orderBy('order_no')
            ->get();
    }

    /**
     * Get shared questions (no context)
     */
    public static function getSharedQuestions(string $roleKey, string $locale = 'tr'): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('role_key', $roleKey)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->whereNull('context_key')
            ->orderBy('order_no')
            ->get();
    }

    /**
     * Get context-specific questions
     */
    public static function getContextQuestions(
        string $roleKey,
        string $contextKey,
        string $locale = 'tr'
    ): \Illuminate\Database\Eloquent\Collection {
        return static::where('role_key', $roleKey)
            ->where('context_key', $contextKey)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->orderBy('order_no')
            ->get();
    }
}
