<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmFile extends Model
{
    use HasUuids;

    protected $table = 'crm_files';

    protected $fillable = [
        'lead_id', 'company_id', 'storage_disk', 'path',
        'original_name', 'mime', 'size', 'sha256',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(CrmCompany::class, 'company_id');
    }
}
