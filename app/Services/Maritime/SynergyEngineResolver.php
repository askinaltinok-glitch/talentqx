<?php

namespace App\Services\Maritime;

use App\Services\FeatureFlagService;
use App\Services\Fleet\CrewSynergyService;
use App\Services\Fleet\CrewSynergyEngineV2;

class SynergyEngineResolver
{
    public function __construct(
        private FeatureFlagService $flags,
        private CrewSynergyService $v1,
        private CrewSynergyEngineV2 $v2,
    ) {}

    public function resolve(string $tenantId, string $positionCode): array
    {
        $featureKey = 'crew_synergy_engine_v2';

        if (!$this->flags->enabled($tenantId, $featureKey)) {
            return ['engine' => 'v1', 'payload' => []];
        }

        $payload = $this->flags->payload($tenantId, $featureKey);

        // payload.positions varsa whitelist uygula
        $positions = $payload['positions'] ?? null;
        if (is_array($positions) && !in_array($positionCode, $positions, true)) {
            return ['engine' => 'v1', 'payload' => []];
        }

        return ['engine' => 'v2', 'payload' => $payload];
    }

    public function run(string $tenantId, string $positionCode, array $input): array
    {
        $r = $this->resolve($tenantId, $positionCode);

        if ($r['engine'] === 'v1') {
            return $this->v1Score($input);
        }

        $dryRun = (bool) ($r['payload']['dry_run'] ?? false);

        $result = $this->v2->score($input, $r['payload']);
        $result['meta']['dry_run'] = $dryRun;

        return $result;
    }

    /**
     * V1 scoring: simple wrapper around CrewSynergyService.
     */
    private function v1Score(array $input): array
    {
        $candidateId = $input['candidate_id'] ?? null;
        $vesselId = $input['vessel_id'] ?? null;

        if (!$candidateId || !$vesselId) {
            return [
                'synergy_01' => 0.5,
                'components' => ['hard_fit_01' => 0.5, 'soft_fit_01' => 0.5],
                'meta' => ['engine' => 'v1', 'error' => 'missing_ids'],
            ];
        }

        $result = $this->v1->compute($candidateId, $vesselId);

        if (!$result) {
            return [
                'synergy_01' => 0.5,
                'components' => ['hard_fit_01' => 0.5, 'soft_fit_01' => 0.5],
                'meta' => ['engine' => 'v1', 'error' => 'compute_failed'],
            ];
        }

        $synergyScore = ($result['synergy']['score'] ?? 50) / 100.0;

        return [
            'synergy_01' => max(0, min(1, $synergyScore)),
            'components' => [
                'hard_fit_01' => $synergyScore,
                'soft_fit_01' => $synergyScore,
            ],
            'meta' => ['engine' => 'v1'],
        ];
    }
}
