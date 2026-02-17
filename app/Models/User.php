<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'company_id',
        'role_id',
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'avatar_url',
        'is_active',
        'is_platform_admin',
        'is_octopus_admin',
        'must_change_password',
        'password_changed_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'is_active' => 'boolean',
            'is_platform_admin' => 'boolean',
            'is_octopus_admin' => 'boolean',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is a platform administrator.
     * Platform admins can access system-wide features like AI costs, billing, analytics.
     */
    public function isPlatformAdmin(): bool
    {
        return $this->is_platform_admin === true;
    }

    public function isOctopusAdmin(): bool
    {
        return $this->is_octopus_admin === true;
    }

    /**
     * Check if user must change their password on next action.
     */
    public function mustChangePassword(): bool
    {
        return $this->must_change_password === true;
    }

    /**
     * Mark password as changed.
     */
    public function markPasswordAsChanged(): void
    {
        $this->update([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }
}
