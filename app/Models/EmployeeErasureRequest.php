<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeErasureRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_id',
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

    const TYPE_EMPLOYEE_REQUEST = 'employee_request';
    const TYPE_KVKK_REQUEST = 'kvkk_request';
    const TYPE_RETENTION_EXPIRED = 'retention_expired';
    const TYPE_COMPANY_POLICY = 'company_policy';

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function markProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markCompleted(array $erasedDataTypes): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'erased_data_types' => $erasedDataTypes,
            'processed_at' => now(),
        ]);
    }

    public function markFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $reason,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
