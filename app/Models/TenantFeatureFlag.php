<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TenantFeatureFlag extends Model
{
    use HasUuids;

    protected $table = 'tenant_feature_flags';

    protected $fillable = [
        'tenant_id',
        'feature_key',
        'is_enabled',
        'payload',
        'enabled_at',
        'enabled_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'payload' => 'array',
        'enabled_at' => 'datetime',
    ];
}
