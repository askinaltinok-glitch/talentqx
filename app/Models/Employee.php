<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'import_batch_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'department',
        'current_role',
        'branch',
        'hire_date',
        'manager_name',
        'status',
        'is_erased',
        'erased_at',
        'erasure_reason',
        'retention_days',
        'metadata',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'metadata' => 'array',
        'is_erased' => 'boolean',
        'erased_at' => 'datetime',
        'retention_days' => 'integer',
    ];

    protected $appends = ['full_name'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(EmployeeImportBatch::class, 'import_batch_id');
    }

    public function assessmentSessions(): HasMany
    {
        return $this->hasMany(AssessmentSession::class);
    }

    public function latestAssessment(): HasOne
    {
        return $this->hasOne(AssessmentSession::class)
            ->where('status', 'completed')
            ->latest('completed_at');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getTenureInMonths(): ?int
    {
        if (!$this->hire_date) return null;
        return $this->hire_date->diffInMonths(now());
    }

    public function getLatestScore(): ?float
    {
        return $this->latestAssessment?->result?->overall_score;
    }

    public function getRiskLevel(): ?string
    {
        return $this->latestAssessment?->result?->risk_level;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('current_role', $role);
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereHas('latestAssessment.result', function ($q) {
            $q->whereIn('risk_level', ['high', 'critical']);
        });
    }

    public function scopeNotErased($query)
    {
        return $query->where('is_erased', false);
    }

    public function scopeErased($query)
    {
        return $query->where('is_erased', true);
    }

    public function erasureRequests(): HasMany
    {
        return $this->hasMany(EmployeeErasureRequest::class);
    }

    /**
     * Check if employee data has been erased
     */
    public function isErased(): bool
    {
        return $this->is_erased === true;
    }

    /**
     * Check if retention period has expired
     */
    public function isRetentionExpired(): bool
    {
        if (!$this->hire_date) {
            return false;
        }

        // Check from last assessment or hire date
        $lastActivity = $this->latestAssessment?->completed_at ?? $this->hire_date;

        return $lastActivity->addDays($this->retention_days ?? 180)->isPast();
    }

    /**
     * Mark as erased
     */
    public function markErased(string $reason): void
    {
        $this->update([
            'is_erased' => true,
            'erased_at' => now(),
            'erasure_reason' => $reason,
            // Clear PII
            'first_name' => '[ERASED]',
            'last_name' => '[ERASED]',
            'email' => null,
            'phone' => null,
            'metadata' => null,
        ]);
    }

    /**
     * Get the latest cheating risk score
     */
    public function getCheatingRiskScore(): ?int
    {
        return $this->latestAssessment?->result?->cheating_risk_score;
    }

    /**
     * Get the latest cheating level
     */
    public function getCheatingLevel(): ?string
    {
        return $this->latestAssessment?->result?->cheating_level;
    }
}
