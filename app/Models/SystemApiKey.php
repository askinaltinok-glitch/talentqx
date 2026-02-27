<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemApiKey extends Model
{
    use HasUuids;

    protected $fillable = [
        'service_name',
        'api_key',
        'secret_key',
        'is_active',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'api_key',
        'secret_key',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'secret_key' => 'encrypted',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
