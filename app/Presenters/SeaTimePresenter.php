<?php

namespace App\Presenters;

use App\Models\CandidateTrustProfile;
use App\Models\SeaTimeLog;

class SeaTimePresenter
{
    /**
     * Present sea-time intelligence from trust profile detail_json.
     */
    public static function fromTrustProfile(?CandidateTrustProfile $trustProfile): ?array
    {
        if (!$trustProfile) {
            return null;
        }

        $detail = $trustProfile->detail_json ?? [];
        $seaTime = $detail['sea_time'] ?? null;

        if (!$seaTime) {
            return null;
        }

        return [
            'total_contracts' => $seaTime['total_contracts'] ?? 0,
            'total_raw_days' => $seaTime['total_raw_days'] ?? 0,
            'total_sea_days' => $seaTime['total_sea_days'] ?? 0,
            'overlap_days' => $seaTime['overlap_days'] ?? 0,
            'sea_days' => $seaTime['sea_days'] ?? 0,
            'river_days' => $seaTime['river_days'] ?? 0,
            'rank_days' => $seaTime['rank_days'] ?? [],
            'rank_experience_pct' => $seaTime['rank_experience_pct'] ?? [],
            'vessel_type_days' => $seaTime['vessel_type_days'] ?? [],
            'vessel_experience_pct' => $seaTime['vessel_experience_pct'] ?? [],
        ];
    }

    /**
     * Present per-contract sea-time log entries for a candidate.
     */
    public static function contractLogs(string $poolCandidateId): array
    {
        $logs = SeaTimeLog::where('pool_candidate_id', $poolCandidateId)
            ->orderBy('effective_start_date')
            ->get();

        if ($logs->isEmpty()) {
            return [];
        }

        // Only show logs from the latest batch
        $latestBatchId = $logs->sortByDesc('computed_at')->first()->computation_batch_id;
        $logs = $logs->where('computation_batch_id', $latestBatchId);

        return $logs->map(fn(SeaTimeLog $log) => [
            'contract_id' => $log->candidate_contract_id,
            'vessel_type' => $log->vessel_type,
            'rank_code' => $log->rank_code,
            'operation_type' => $log->operation_type,
            'original_start' => $log->original_start_date->toDateString(),
            'original_end' => $log->original_end_date->toDateString(),
            'effective_start' => $log->effective_start_date->toDateString(),
            'effective_end' => $log->effective_end_date->toDateString(),
            'raw_days' => $log->raw_days,
            'calculated_days' => $log->calculated_days,
            'overlap_deducted' => $log->overlap_deducted_days,
        ])->values()->all();
    }
}
