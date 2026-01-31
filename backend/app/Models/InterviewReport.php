<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InterviewReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id',
        'tenant_id',
        'locale',
        'status',
        'storage_disk',
        'storage_path',
        'file_size',
        'checksum',
        'metadata',
        'generated_at',
        'expires_at',
        'error_message',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function session(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class, 'session_id');
    }

    /**
     * Get branding from metadata
     */
    public function getBranding(): array
    {
        return $this->metadata['branding'] ?? [
            'logo_url' => null,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'company_name' => null,
        ];
    }

    /**
     * Check if file exists
     */
    public function fileExists(): bool
    {
        if (!$this->storage_path) {
            return false;
        }
        return Storage::disk($this->storage_disk ?? 'private')->exists($this->storage_path);
    }

    /**
     * Delete associated file
     */
    public function deleteFile(): bool
    {
        if ($this->storage_path && $this->fileExists()) {
            return Storage::disk($this->storage_disk ?? 'private')->delete($this->storage_path);
        }
        return true;
    }

    /**
     * Check if report is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get download URL (signed)
     */
    public function getDownloadUrl(int $expiresInMinutes = 60): ?string
    {
        if (!$this->storage_path || !$this->fileExists()) {
            return null;
        }

        return Storage::disk($this->storage_disk ?? 'private')
            ->temporaryUrl($this->storage_path, now()->addMinutes($expiresInMinutes));
    }
}
