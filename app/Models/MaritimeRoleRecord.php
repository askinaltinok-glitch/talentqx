<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaritimeRoleRecord extends Model
{
    protected $table = 'maritime_roles';
    protected $primaryKey = 'role_key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'role_key',
        'label',
        'department',
        'domain',
        'is_active',
        'is_selectable',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_selectable' => 'boolean',
        'sort_order' => 'integer',
        'meta' => 'array',
    ];

    public function dna(): HasOne
    {
        return $this->hasOne(MaritimeRoleDna::class, 'role_key', 'role_key');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeSelectable($query)
    {
        return $query->where('is_selectable', true);
    }

    public function scopeActiveSelectable($query)
    {
        return $query->where('is_active', true)->where('is_selectable', true);
    }

    public static function findByKey(string $key): ?self
    {
        return static::find($key);
    }
}
