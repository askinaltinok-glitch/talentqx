<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VesselRegistryCache extends Model
{
    use HasUuids;

    protected $table = 'vessel_registry_cache';

    protected $fillable = [
        'imo', 'name', 'flag', 'vessel_type',
        'year_built', 'dwt', 'gt', 'last_seen_at', 'source',
    ];

    protected $casts = [
        'year_built' => 'integer',
        'dwt' => 'integer',
        'gt' => 'integer',
        'last_seen_at' => 'datetime',
    ];
}
