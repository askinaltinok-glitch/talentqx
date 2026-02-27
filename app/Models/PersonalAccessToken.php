<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

/**
 * Custom PersonalAccessToken that supports cross-database authentication.
 *
 * Platform admins may exist in 'mysql' (talentqx) but log in via the
 * TalentQX brand frontend which uses 'mysql_talentqx' (talentqx_hr).
 * Sanctum's default findToken() only checks the current default connection.
 * This override checks both connections.
 */
class PersonalAccessToken extends SanctumToken
{
    /**
     * Find the token instance matching the given token.
     * Checks both database connections if needed.
     */
    public static function findToken($token): ?self
    {
        // Try the default connection first (normal path)
        $result = parent::findToken($token);

        if ($result) {
            return $result;
        }

        // Token not found — try the alternate connection
        $altConnection = config('database.default') === 'mysql_talentqx' ? 'mysql' : 'mysql_talentqx';

        if (! str_contains($token, '|')) {
            // Plain token (hashed lookup)
            $instance = static::on($altConnection)->where('token', hash('sha256', $token))->first();
        } else {
            // Prefixed token: "id|plainText"
            [$id, $plainToken] = explode('|', $token, 2);
            $instance = static::on($altConnection)->find($id);

            if ($instance && ! hash_equals($instance->token, hash('sha256', $plainToken))) {
                return null;
            }
        }

        return $instance;
    }

    /**
     * Override the tokenable relationship to support cross-database users.
     * When the user doesn't exist in the token's DB, check the alternate DB.
     */
    public function tokenable(): MorphTo
    {
        $relation = parent::tokenable();

        return $relation;
    }

    /**
     * Resolve the tokenable (user) model, checking both DBs if needed.
     */
    public function getTokenableAttribute()
    {
        // Try default resolution
        $tokenable = $this->getRelationValue('tokenable')
            ?: $this->morphTo('tokenable')->getResults();

        if ($tokenable) {
            return $tokenable;
        }

        // Not found on token's connection — try alternate DB
        $altConnection = $this->getConnectionName() === 'mysql_talentqx' ? 'mysql' : 'mysql_talentqx';

        if ($this->tokenable_type === User::class || $this->tokenable_type === 'App\\Models\\User') {
            $tokenable = User::on($altConnection)->find($this->tokenable_id);
            if ($tokenable) {
                $this->setRelation('tokenable', $tokenable);
            }
        }

        return $tokenable;
    }
}
