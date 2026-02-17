<?php

namespace App\Services\System;

use App\Models\SystemEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SystemEventService
{
    public static function log(string $type, string $severity, string $source, string $message, array $meta = []): SystemEvent
    {
        // Auto-tag brand from industry_code in meta if not already set
        if (!isset($meta['brand']) && isset($meta['industry_code'])) {
            $meta['brand'] = $meta['industry_code'] === 'maritime' ? 'octopus' : 'talentqx';
        }

        Log::channel('single')->info("SystemEvent [{$severity}] {$type}: {$message}", [
            'source' => $source,
            'meta' => $meta,
        ]);

        return SystemEvent::create([
            'type' => $type,
            'severity' => $severity,
            'source' => $source,
            'message' => $message,
            'meta' => !empty($meta) ? $meta : null,
            'created_at' => now(),
        ]);
    }

    public static function alert(string $type, string $source, string $message, array $meta = []): SystemEvent
    {
        return static::log($type, SystemEvent::SEVERITY_CRITICAL, $source, $message, $meta);
    }

    public static function warn(string $type, string $source, string $message, array $meta = []): SystemEvent
    {
        return static::log($type, SystemEvent::SEVERITY_WARN, $source, $message, $meta);
    }

    public static function getRecent(int $limit = 50, ?string $type = null, ?string $severity = null): Collection
    {
        return SystemEvent::query()
            ->when($type, fn($q) => $q->type($type))
            ->when($severity, fn($q) => $q->severity($severity))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
