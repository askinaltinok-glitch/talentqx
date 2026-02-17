<?php

namespace App\Models;

use App\Services\Demo\DemoProvisioningService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Lead extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'company_type',
        'company_size',
        'industry',
        'city',
        'status',
        'lost_reason',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'assigned_to',
        'lead_score',
        'is_hot',
        'first_contact_at',
        'demo_scheduled_at',
        'demo_completed_at',
        'pilot_started_at',
        'pilot_ended_at',
        'won_at',
        'lost_at',
        'next_follow_up_at',
        'estimated_value',
        'actual_value',
        'notes',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_hot' => 'boolean',
        'lead_score' => 'integer',
        'estimated_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'first_contact_at' => 'datetime',
        'demo_scheduled_at' => 'datetime',
        'demo_completed_at' => 'datetime',
        'pilot_started_at' => 'datetime',
        'pilot_ended_at' => 'datetime',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_DEMO = 'demo';
    public const STATUS_PILOT = 'pilot';
    public const STATUS_NEGOTIATION = 'negotiation';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_DEMO,
        self::STATUS_PILOT,
        self::STATUS_NEGOTIATION,
        self::STATUS_WON,
        self::STATUS_LOST,
    ];

    public const STATUS_LABELS = [
        self::STATUS_NEW => 'Yeni',
        self::STATUS_CONTACTED => 'İletişime Geçildi',
        self::STATUS_DEMO => 'Demo',
        self::STATUS_PILOT => 'Pilot',
        self::STATUS_NEGOTIATION => 'Görüşme',
        self::STATUS_WON => 'Kazanıldı',
        self::STATUS_LOST => 'Kaybedildi',
    ];

    // Relationships
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderBy('created_at', 'desc');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(LeadChecklistItem::class);
    }

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeHot($query)
    {
        return $query->where('is_hot', true);
    }

    public function scopeNeedsFollowUp($query)
    {
        return $query->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', now())
            ->whereNotIn('status', [self::STATUS_WON, self::STATUS_LOST]);
    }

    public function scopeAssignedTo($query, string $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    // Methods
    public function updateStatus(string $newStatus, ?string $lostReason = null): void
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;

        // Set timestamps based on status
        switch ($newStatus) {
            case self::STATUS_CONTACTED:
                if (!$this->first_contact_at) {
                    $this->first_contact_at = now();
                }
                break;
            case self::STATUS_DEMO:
                // Auto-provision demo account when status changes to "demo"
                $this->provisionDemoAccount();
                break;
            case self::STATUS_WON:
                $this->won_at = now();
                break;
            case self::STATUS_LOST:
                $this->lost_at = now();
                $this->lost_reason = $lostReason;
                break;
        }

        $this->save();

        // Log activity
        $this->activities()->create([
            'type' => 'status_change',
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'description' => $lostReason ? "Kayıp nedeni: {$lostReason}" : null,
        ]);
    }

    /**
     * Provision a demo account for this lead.
     */
    public function provisionDemoAccount(): array
    {
        try {
            $service = app(DemoProvisioningService::class);
            $result = $service->provisionForLead($this);

            Log::info('Demo provisioning triggered for lead', [
                'lead_id' => $this->id,
                'email' => $this->email,
                'success' => $result['success'],
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Demo provisioning failed', [
                'lead_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function calculateScore(): int
    {
        $score = 0;

        // Company type scoring
        $score += match ($this->company_type) {
            'franchise' => 30,
            'chain' => 25,
            'single' => 10,
            default => 0,
        };

        // Company size scoring
        $score += match ($this->company_size) {
            '200+' => 25,
            '51-200' => 20,
            '11-50' => 15,
            '1-10' => 5,
            default => 0,
        };

        // Engagement scoring
        if ($this->demo_completed_at) $score += 20;
        elseif ($this->demo_scheduled_at) $score += 10;
        if ($this->pilot_started_at) $score += 15;

        // Recent activity
        $recentActivity = $this->activities()->where('created_at', '>=', now()->subDays(7))->exists();
        if ($recentActivity) $score += 10;

        $this->lead_score = min(100, $score);
        $this->is_hot = $this->lead_score >= 70;
        $this->save();

        return $this->lead_score;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getDaysInPipelineAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }
}
