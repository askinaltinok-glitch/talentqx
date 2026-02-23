<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertTenantFeatureFlagRequest;
use App\Models\TenantFeatureFlag;
use App\Services\FeatureFlagService;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;

class TenantFeatureFlagController extends Controller
{
    public function __construct(
        private FeatureFlagService $flags,
        private AuditLogService $audit,
    ) {}

    /**
     * List all feature flags for a tenant.
     */
    public function index(string $tenantId): JsonResponse
    {
        $rows = TenantFeatureFlag::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('feature_key')
            ->get()
            ->map(fn ($r) => [
                'feature_key' => $r->feature_key,
                'is_enabled' => (bool) $r->is_enabled,
                'payload' => $r->payload ?? [],
                'enabled_at' => optional($r->enabled_at)->toISOString(),
                'enabled_by' => $r->enabled_by,
                'updated_at' => optional($r->updated_at)->toISOString(),
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    /**
     * Create or update a feature flag for a tenant.
     */
    public function upsert(UpsertTenantFeatureFlagRequest $req, string $tenantId, string $featureKey): JsonResponse
    {
        $userId = (string) optional($req->user())->id;

        // Snapshot before state
        $before = TenantFeatureFlag::query()
            ->where('tenant_id', $tenantId)
            ->where('feature_key', $featureKey)
            ->first();

        $beforeArr = $before ? [
            'is_enabled' => (bool) $before->is_enabled,
            'payload' => $before->payload ?? [],
        ] : null;

        $isEnabled = (bool) $req->boolean('is_enabled');
        $payload = (array) ($req->input('payload') ?? []);

        // Upsert
        $row = TenantFeatureFlag::updateOrCreate(
            ['tenant_id' => $tenantId, 'feature_key' => $featureKey],
            [
                'is_enabled' => $isEnabled,
                'payload' => $payload,
                'enabled_at' => $isEnabled ? now() : null,
                'enabled_by' => $isEnabled ? $userId : null,
            ]
        );

        $afterArr = [
            'is_enabled' => (bool) $row->is_enabled,
            'payload' => $row->payload ?? [],
        ];

        // Cache invalidation
        $this->flags->forget($tenantId, $featureKey);

        // Audit log with payload diff
        $diff = $this->audit->diff($beforeArr, $afterArr);

        $this->audit->log('tenant_feature_flag.upsert', [
            'tenant_id' => $tenantId,
            'feature_key' => $featureKey,
            'actor_user_id' => $userId,
            'before' => $beforeArr,
            'after' => $afterArr,
            'diff' => $diff,
            'request_id' => (string) ($req->headers->get('X-Request-Id') ?? ''),
            'ip' => (string) ($req->ip() ?? ''),
            'ua' => (string) ($req->userAgent() ?? ''),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'feature_key' => $row->feature_key,
                'is_enabled' => (bool) $row->is_enabled,
                'payload' => $row->payload ?? [],
                'enabled_at' => optional($row->enabled_at)->toISOString(),
                'enabled_by' => $row->enabled_by,
                'updated_at' => optional($row->updated_at)->toISOString(),
            ],
        ]);
    }
}
