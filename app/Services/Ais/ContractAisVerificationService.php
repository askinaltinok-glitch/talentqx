<?php

namespace App\Services\Ais;

use App\Models\AisVerification;
use App\Models\CandidateContract;
use App\Models\ContractAisVerification;
use App\Models\TrustEvent;
use App\Models\Vessel;
use Illuminate\Support\Facades\Log;

class ContractAisVerificationService
{
    public function __construct(
        private AisProviderInterface $provider,
        private ConfidenceScorer $scorer,
    ) {}

    /**
     * Run AIS verification for a contract.
     * Returns the new ContractAisVerification record, or null if feature disabled.
     */
    public function verify(
        CandidateContract $contract,
        string $triggeredBy = 'system',
        ?string $userId = null,
    ): ?ContractAisVerification {
        // Gate 1: Feature flag
        if (!config('maritime.ais_v1')) {
            return null;
        }

        // Gate 2: No IMO
        if (!$contract->vessel_imo) {
            return $this->createRecord($contract, [
                'status' => ContractAisVerification::STATUS_NOT_APPLICABLE,
                'reasons_json' => [['code' => 'MISSING_IMO', 'weight' => 0, 'detail' => 'No IMO number on contract']],
                'triggered_by' => $triggeredBy,
                'triggered_by_user_id' => $userId,
            ]);
        }

        // Gate 3: Ongoing contract (no end date)
        if (!$contract->end_date) {
            return $this->createRecord($contract, [
                'status' => ContractAisVerification::STATUS_NOT_APPLICABLE,
                'reasons_json' => [['code' => 'ONGOING_CONTRACT', 'weight' => 0, 'detail' => 'Contract has no end date']],
                'triggered_by' => $triggeredBy,
                'triggered_by_user_id' => $userId,
            ]);
        }

        try {
            return $this->runVerification($contract, $triggeredBy, $userId);
        } catch (\Throwable $e) {
            Log::channel('daily')->error('[AIS] Verification error', [
                'contract_id' => $contract->id,
                'imo' => $contract->vessel_imo,
                'error' => $e->getMessage(),
            ]);

            return $this->createRecord($contract, [
                'status' => ContractAisVerification::STATUS_FAILED,
                'error_code' => class_basename($e),
                'error_message' => mb_substr($e->getMessage(), 0, 500),
                'triggered_by' => $triggeredBy,
                'triggered_by_user_id' => $userId,
            ]);
        }
    }

    private function runVerification(
        CandidateContract $contract,
        string $triggeredBy,
        ?string $userId,
    ): ContractAisVerification {
        $imo = $contract->vessel_imo;

        // 1. Resolve vessel static data
        $static = $this->provider->fetchVesselStatic($imo);
        $vessel = Vessel::findOrCreateByImo(
            $imo,
            $contract->vessel_name,
            $contract->vessel_type,
            $static?->mmsi,
        );

        // Update vessel with fresh static data from provider
        if ($static) {
            $vessel->update(array_filter([
                'name' => $static->name,
                'mmsi' => $static->mmsi,
                'type' => $static->type,
                'flag' => $static->flag,
                'dwt' => $static->dwt,
                'gt' => $static->gt,
                'length_m' => $static->lengthM,
                'beam_m' => $static->beamM,
                'year_built' => $static->yearBuilt,
                'last_seen_at' => $static->lastSeenAt,
                'vessel_type_raw' => $static->type,
                'vessel_type_normalized' => VesselTypeNormalizer::normalize($static->type),
                'static_source' => 'provider',
            ], fn($v) => $v !== null));
        }

        // Link vessel_id on contract if not set
        if (!$contract->vessel_id) {
            $contract->update(['vessel_id' => $vessel->id]);
        }

        // 2. Fetch track points
        $track = $this->provider->fetchTrackPoints($imo, $contract->start_date, $contract->end_date);

        // 3. Score
        $result = $this->scorer->score($track, $contract, $vessel);

        // 4. Build evidence summary
        $evidenceSummary = [
            'area_clusters' => $track->areaClusters,
            'total_points' => $track->totalPoints,
            'days_covered' => $track->daysCovered,
            'data_quality' => $track->dataQuality,
        ];

        // 5. Create ContractAisVerification record (append-only)
        $verification = $this->createRecord($contract, [
            'vessel_id' => $vessel->id,
            'status' => $result->status,
            'confidence_score' => round($result->score * 100, 2),
            'reasons_json' => $result->reasons,
            'anomalies_json' => $result->anomalies,
            'evidence_summary_json' => $evidenceSummary,
            'period_start' => $contract->start_date,
            'period_end' => $contract->end_date,
            'provider' => config('maritime.ais_mock') ? 'mock' : 'http',
            'triggered_by' => $triggeredBy,
            'triggered_by_user_id' => $userId,
            'verified_at' => $result->status === ContractAisVerification::STATUS_VERIFIED ? now() : null,
        ]);

        // 6. Upsert old ais_verifications record (keeps UI v1 working)
        try {
            AisVerification::updateOrCreate(
                ['candidate_contract_id' => $contract->id],
                [
                    'vessel_id' => $vessel->id,
                    'status' => $result->status,
                    'confidence_score' => round($result->score * 100, 2),
                    'failure_reason' => $result->status === ContractAisVerification::STATUS_FAILED
                        ? ($result->anomalies[0]['type'] ?? 'LOW_CONFIDENCE')
                        : null,
                    'verified_at' => $result->status === ContractAisVerification::STATUS_VERIFIED ? now() : null,
                ],
            );
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('[AIS] Failed to upsert legacy ais_verifications', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 7. Audit event
        try {
            TrustEvent::create([
                'pool_candidate_id' => $contract->pool_candidate_id,
                'event_type' => 'ais_verification_completed',
                'payload_json' => [
                    'contract_id' => $contract->id,
                    'verification_id' => $verification->id,
                    'status' => $result->status,
                    'confidence_score' => round($result->score * 100, 2),
                    'provider' => config('maritime.ais_mock') ? 'mock' : 'http',
                ],
            ]);
        } catch (\Throwable) {}

        return $verification;
    }

    private function createRecord(CandidateContract $contract, array $data): ContractAisVerification
    {
        return ContractAisVerification::create(array_merge([
            'candidate_contract_id' => $contract->id,
        ], $data));
    }
}
