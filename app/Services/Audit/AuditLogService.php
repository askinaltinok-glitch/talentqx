<?php

namespace App\Services\Audit;

use App\Models\AuditLog;

class AuditLogService
{
    /**
     * Recursive diff between before/after arrays.
     *
     * @return array{changed: array, added: array, removed: array}
     */
    public function diff(?array $before, ?array $after): array
    {
        $before = $before ?? [];
        $after = $after ?? [];

        $out = ['changed' => [], 'added' => [], 'removed' => []];

        $walk = function ($b, $a, string $path) use (&$walk, &$out) {
            $bIsArr = is_array($b);
            $aIsArr = is_array($a);

            if ($bIsArr && $aIsArr) {
                $keys = array_unique(array_merge(array_keys($b), array_keys($a)));
                foreach ($keys as $k) {
                    $p = $path === '' ? (string) $k : $path . '.' . $k;

                    $bHas = array_key_exists($k, $b);
                    $aHas = array_key_exists($k, $a);

                    if ($bHas && $aHas) {
                        $walk($b[$k], $a[$k], $p);
                    } elseif (!$bHas && $aHas) {
                        $out['added'][$p] = ['to' => $a[$k]];
                    } elseif ($bHas && !$aHas) {
                        $out['removed'][$p] = ['from' => $b[$k]];
                    }
                }
                return;
            }

            if ($b !== $a) {
                $out['changed'][$path] = ['from' => $b, 'to' => $a];
            }
        };

        $walk($before, $after, '');

        return $out;
    }

    /**
     * Write an audit log entry using the existing audit_logs table schema.
     */
    public function log(string $eventKey, array $data): void
    {
        $tenantId = $data['tenant_id'] ?? null;
        $featureKey = $data['feature_key'] ?? null;

        AuditLog::create([
            'action' => $eventKey,
            'user_id' => $data['actor_user_id'] ?? null,
            'company_id' => $tenantId,
            'entity_type' => $data['entity_type'] ?? 'tenant_feature_flag',
            'entity_id' => $tenantId && $featureKey ? "{$tenantId}:{$featureKey}" : null,
            'old_values' => $data['before'] ?? null,
            'new_values' => $data['after'] ?? null,
            'metadata' => [
                'diff' => $data['diff'] ?? null,
                'feature_key' => $featureKey,
                'request_id' => $data['request_id'] ?? null,
            ],
            'ip_address' => $data['ip'] ?? null,
            'user_agent' => $data['ua'] ?? null,
        ]);
    }
}
