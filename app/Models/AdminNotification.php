<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    const TYPE_NEW_CANDIDATE       = 'new_candidate';
    const TYPE_DEMO_REQUEST        = 'demo_request';
    const TYPE_INTERVIEW_COMPLETED = 'interview_completed';
    const TYPE_COMPANY_ONBOARD     = 'company_onboard';
    const TYPE_EMAIL_SENT          = 'email_sent';

    protected $fillable = [
        'type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data'       => 'array',
            'read_at'    => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /* ── Scopes ── */

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /* ── Helpers ── */

    public function markRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }
}
