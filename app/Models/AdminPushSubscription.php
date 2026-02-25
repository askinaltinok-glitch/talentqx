<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Minishlink\WebPush\Subscription;

class AdminPushSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'admin_user_id',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
        'user_agent',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $sub) {
            $sub->endpoint_hash = hash('sha256', $sub->endpoint);
        });
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Convert to minishlink Subscription object for WebPush sending.
     */
    public function toWebPushSubscription(): Subscription
    {
        return Subscription::create([
            'endpoint'        => $this->endpoint,
            'publicKey'       => $this->public_key,
            'authToken'       => $this->auth_token,
            'contentEncoding' => 'aesgcm',
        ]);
    }
}
