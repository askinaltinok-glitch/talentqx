<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CopilotConversation extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'user_id',
        'context_type',
        'context_id',
        'title',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CopilotMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(CopilotMessage::class, 'conversation_id')->latest()->limit(1);
    }

    /**
     * Get conversation history as array for prompt building.
     */
    public function getHistoryForPrompt(int $limit = 10): array
    {
        return $this->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Update the last message timestamp.
     */
    public function touchLastMessage(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Generate a title based on first user message if not set.
     */
    public function generateTitle(): void
    {
        if ($this->title) {
            return;
        }

        $firstMessage = $this->messages()
            ->where('role', 'user')
            ->first();

        if ($firstMessage) {
            $title = substr($firstMessage->content, 0, 100);
            if (strlen($firstMessage->content) > 100) {
                $title .= '...';
            }
            $this->update(['title' => $title]);
        }
    }
}
