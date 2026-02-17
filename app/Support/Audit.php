<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class Audit
{
    /**
     * Log an audit event
     *
     * @param string $action Action name (e.g., 'employee_created', 'employee_deleted')
     * @param string|null $entityType Entity type (e.g., 'employee', 'job')
     * @param mixed $entityId Entity ID
     * @param array $meta Additional metadata
     * @param Request|null $request HTTP request (for IP/user agent)
     */
    public static function log(
        string $action,
        ?string $entityType = null,
        $entityId = null,
        array $meta = [],
        ?Request $request = null
    ): void {
        $request = $request ?: request();

        try {
            AuditLog::create([
                'user_id' => optional($request->user())->id,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId !== null ? (string) $entityId : null,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'meta' => !empty($meta) ? $meta : null,
            ]);
        } catch (\Exception $e) {
            // Don't let audit logging break the main flow
            \Log::error('Audit log failed: ' . $e->getMessage());
        }
    }
}
