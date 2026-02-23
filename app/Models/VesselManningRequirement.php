<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselManningRequirement extends Model
{
    use HasUuids;

    protected $table = 'vessel_manning_requirements';

    protected $fillable = [
        'vessel_id', 'rank_code', 'required_count',
        'required_certs', 'min_english_level', 'notes',
    ];

    protected $casts = [
        'required_count' => 'integer',
        'required_certs' => 'array',
    ];

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(FleetVessel::class, 'vessel_id');
    }
}
