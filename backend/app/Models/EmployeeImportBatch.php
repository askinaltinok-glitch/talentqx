<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'created_by_user_id',
        'filename',
        'imported_count',
        'skipped_count',
        'is_rolled_back',
        'rolled_back_at',
    ];

    protected $casts = [
        'is_rolled_back' => 'boolean',
        'rolled_back_at' => 'datetime',
        'imported_count' => 'integer',
        'skipped_count' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'import_batch_id');
    }
}
