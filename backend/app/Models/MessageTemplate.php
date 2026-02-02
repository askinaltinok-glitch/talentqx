<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'channel',
        'locale',
        'subject',
        'body',
        'available_variables',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'available_variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function outboxMessages(): HasMany
    {
        return $this->hasMany(MessageOutbox::class, 'template_id');
    }

    /**
     * Render the template with given data.
     */
    public function render(array $data): array
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject ?? '');
            $body = str_replace($placeholder, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for a specific channel.
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Find template for tenant or fallback to system template.
     */
    public static function findForTenant(string $code, string $channel, ?string $companyId = null, string $locale = 'tr'): ?self
    {
        // First try company-specific template
        if ($companyId) {
            $template = static::where('code', $code)
                ->where('channel', $channel)
                ->where('company_id', $companyId)
                ->where('locale', $locale)
                ->active()
                ->first();

            if ($template) {
                return $template;
            }
        }

        // Fallback to system template
        return static::where('code', $code)
            ->where('channel', $channel)
            ->whereNull('company_id')
            ->where('is_system', true)
            ->where('locale', $locale)
            ->active()
            ->first();
    }
}
