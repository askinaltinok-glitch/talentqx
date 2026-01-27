<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataErasureRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'candidate_id',
        'requested_by',
        'request_type',
        'status',
        'erased_data_types',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'erased_data_types' => 'array',
        'processed_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function markProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markCompleted(array $erasedTypes): void
    {
        $this->update([
            'status' => 'completed',
            'erased_data_types' => $erasedTypes,
            'processed_at' => now(),
        ]);
    }

    public function markFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
