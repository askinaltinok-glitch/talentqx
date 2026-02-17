<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditUsageLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'interview_id',
        'action',
        'amount',
        'balance_before',
        'balance_after',
        'reason',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'created_at' => 'datetime',
    ];

    // Action constants
    public const ACTION_DEDUCT = 'deduct';
    public const ACTION_ADD = 'add';
    public const ACTION_RESET = 'reset';
    public const ACTION_BONUS = 'bonus';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
