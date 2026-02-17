<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmDealStageHistory extends Model
{
    use HasUuids;

    protected $table = 'crm_deal_stage_history';

    public $timestamps = false;

    protected $fillable = [
        'deal_id', 'from_stage', 'to_stage', 'changed_by', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(CrmDeal::class, 'deal_id');
    }
}
