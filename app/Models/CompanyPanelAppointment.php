<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPanelAppointment extends Model
{
    use HasUuids;

    protected $fillable = [
        'sales_rep_id',
        'lead_id',
        'title',
        'starts_at',
        'ends_at',
        'customer_timezone',
        'status',
        'notes',
        'reminder_sent',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reminder_sent' => 'boolean',
        ];
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }
}
