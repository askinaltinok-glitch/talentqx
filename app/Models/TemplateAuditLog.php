<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TemplateAuditLog extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'admin_user_id',
        'admin_email',
        'action',
        'template_id',
        'template_title',
        'template_version',
        'template_language',
        'template_position_code',
        'before_sha',
        'after_sha',
        'changes',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InterviewTemplate::class, 'template_id');
    }

    /**
     * Create an audit log entry
     */
    public static function log(
        string $action,
        InterviewTemplate $template,
        ?User $admin = null,
        ?string $beforeSha = null,
        ?array $changes = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        $afterSha = $template->template_json ? hash('sha256', $template->template_json) : null;

        return self::create([
            'admin_user_id' => $admin?->id,
            'admin_email' => $admin?->email ?? 'system',
            'action' => $action,
            'template_id' => $template->id,
            'template_title' => $template->title,
            'template_version' => $template->version,
            'template_language' => $template->language,
            'template_position_code' => $template->position_code,
            'before_sha' => $beforeSha,
            'after_sha' => $afterSha,
            'changes' => $changes,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Get recent logs for a template
     */
    public static function forTemplate(string $templateId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('template_id', $templateId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
