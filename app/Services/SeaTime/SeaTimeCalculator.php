<?php

namespace App\Services\SeaTime;

use App\Models\CandidateContract;
use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Models\SeaTimeLog;
use App\Models\TrustEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SeaTimeCalculator
{
    private OverlapCorrector $overlapCorrector;

    public function __construct(OverlapCorrector $overlapCorrector)
    {
        $this->overlapCorrector = $overlapCorrector;
    }

    /**
     * Compute sea-time intelligence for a candidate.
     * Deletes previous logs and recomputes from scratch.
     *
     * Returns the summary array or null if feature disabled / error.
     */
    public function compute(string $poolCandidateId): ?array
    {
        if (!config('maritime.sea_time_v1')) {
            return null;
        }

        try {
            return $this->doCompute($poolCandidateId);
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('[SeaTime] compute failed', [
                'candidate' => $poolCandidateId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function doCompute(string $poolCandidateId): array
    {
        $candidate = PoolCandidate::findOrFail($poolCandidateId);

        $contracts = CandidateContract::where('pool_candidate_id', $poolCandidateId)
            ->whereNotNull('start_date')
            ->orderBy('start_date')
            ->get();

        if ($contracts->isEmpty()) {
            return $this->buildEmptySummary($poolCandidateId);
        }

        // Run overlap correction
        $corrected = $this->overlapCorrector->correct($contracts);
        $mergedTotal = $this->overlapCorrector->mergedTotalDays($contracts);

        $batchId = (string) Str::uuid();
        $now = now();

        // Delete old logs and insert new ones in a transaction
        DB::transaction(function () use ($poolCandidateId, $corrected, $batchId, $now) {
            SeaTimeLog::where('pool_candidate_id', $poolCandidateId)->delete();

            foreach ($corrected as $entry) {
                SeaTimeLog::create([
                    'pool_candidate_id' => $poolCandidateId,
                    'candidate_contract_id' => $entry['contract_id'],
                    'vessel_id' => $entry['vessel_id'],
                    'rank_code' => $entry['rank_code'],
                    'original_start_date' => $entry['original_start'],
                    'original_end_date' => $entry['original_end'],
                    'effective_start_date' => $entry['effective_start'],
                    'effective_end_date' => $entry['effective_end'],
                    'vessel_type' => $entry['vessel_type'],
                    'operation_type' => OperationTypeClassifier::classify($entry['vessel_type']),
                    'raw_days' => $entry['raw_days'],
                    'calculated_days' => $entry['calculated_days'],
                    'overlap_deducted_days' => $entry['overlap_deducted'],
                    'computation_batch_id' => $batchId,
                    'computed_at' => $now,
                ]);
            }
        });

        // Build summary
        $summary = $this->buildSummary($corrected, $mergedTotal);

        // Store in trust profile
        $this->storeSummary($poolCandidateId, $summary);

        // Audit event
        TrustEvent::create([
            'pool_candidate_id' => $poolCandidateId,
            'event_type' => 'sea_time_computed',
            'payload_json' => [
                'batch_id' => $batchId,
                'total_sea_days' => $summary['total_sea_days'],
                'total_contracts' => $summary['total_contracts'],
                'overlap_days' => $summary['overlap_days'],
            ],
        ]);

        return $summary;
    }

    private function buildSummary(array $corrected, int $mergedTotal): array
    {
        $totalRawDays = 0;
        $totalCalculatedDays = 0;
        $totalOverlapDays = 0;
        $rankDays = [];
        $vesselTypeDays = [];
        $operationTypeDays = ['sea' => 0, 'river' => 0];

        foreach ($corrected as $entry) {
            $totalRawDays += $entry['raw_days'];
            $totalCalculatedDays += $entry['calculated_days'];
            $totalOverlapDays += $entry['overlap_deducted'];

            // Rank sea days
            $rank = $entry['rank_code'] ?? 'unknown';
            $rankDays[$rank] = ($rankDays[$rank] ?? 0) + $entry['calculated_days'];

            // Vessel type days
            $vtype = $entry['vessel_type'] ?? 'unknown';
            $vesselTypeDays[$vtype] = ($vesselTypeDays[$vtype] ?? 0) + $entry['calculated_days'];

            // Operation type days
            $opType = OperationTypeClassifier::classify($entry['vessel_type']);
            $operationTypeDays[$opType] += $entry['calculated_days'];
        }

        // Vessel experience percentages
        $vesselExperiencePct = [];
        if ($totalCalculatedDays > 0) {
            foreach ($vesselTypeDays as $type => $days) {
                $vesselExperiencePct[$type] = round(($days / $totalCalculatedDays) * 100, 1);
            }
            arsort($vesselExperiencePct);
        }

        // Rank experience percentages
        $rankExperiencePct = [];
        if ($totalCalculatedDays > 0) {
            foreach ($rankDays as $rank => $days) {
                $rankExperiencePct[$rank] = round(($days / $totalCalculatedDays) * 100, 1);
            }
            arsort($rankExperiencePct);
        }

        return [
            'total_contracts' => count($corrected),
            'total_raw_days' => $totalRawDays,
            'total_sea_days' => $totalCalculatedDays,
            'merged_total_days' => $mergedTotal,
            'overlap_days' => $totalOverlapDays,
            'rank_days' => $rankDays,
            'rank_experience_pct' => $rankExperiencePct,
            'vessel_type_days' => $vesselTypeDays,
            'vessel_experience_pct' => $vesselExperiencePct,
            'operation_type_days' => $operationTypeDays,
            'sea_days' => $operationTypeDays['sea'],
            'river_days' => $operationTypeDays['river'],
        ];
    }

    private function buildEmptySummary(string $poolCandidateId): array
    {
        // Clear old logs
        SeaTimeLog::where('pool_candidate_id', $poolCandidateId)->delete();

        $summary = [
            'total_contracts' => 0,
            'total_raw_days' => 0,
            'total_sea_days' => 0,
            'merged_total_days' => 0,
            'overlap_days' => 0,
            'rank_days' => [],
            'rank_experience_pct' => [],
            'vessel_type_days' => [],
            'vessel_experience_pct' => [],
            'operation_type_days' => ['sea' => 0, 'river' => 0],
            'sea_days' => 0,
            'river_days' => 0,
        ];

        $this->storeSummary($poolCandidateId, $summary);

        return $summary;
    }

    private function storeSummary(string $poolCandidateId, array $summary): void
    {
        $trustProfile = CandidateTrustProfile::firstOrNew(
            ['pool_candidate_id' => $poolCandidateId]
        );

        $detailJson = $trustProfile->detail_json ?? [];
        $detailJson['sea_time'] = $summary;

        $trustProfile->detail_json = $detailJson;

        if (!$trustProfile->exists) {
            $trustProfile->pool_candidate_id = $poolCandidateId;
            $trustProfile->cri_score = 0;
            $trustProfile->confidence_level = 'low';
            $trustProfile->computed_at = now();
        }

        $trustProfile->save();
    }
}
