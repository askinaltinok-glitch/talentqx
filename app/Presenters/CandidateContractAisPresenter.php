<?php

namespace App\Presenters;

use App\Models\AisVerification;
use App\Models\CandidateContract;
use App\Models\ContractAisVerification;

class CandidateContractAisPresenter
{
    /**
     * Format a single contract with vessel + AIS verification blocks.
     */
    public static function present(CandidateContract $contract): array
    {
        $base = [
            'id' => $contract->id,
            'vessel_name' => $contract->vessel_name,
            'vessel_imo' => $contract->vessel_imo,
            'vessel_type' => $contract->vessel_type,
            'company_name' => $contract->company_name,
            'rank_code' => $contract->rank_code,
            'start_date' => $contract->start_date->toDateString(),
            'end_date' => $contract->end_date?->toDateString(),
            'duration_months' => $contract->durationMonths(),
            'trading_area' => $contract->trading_area,
            'dwt_range' => $contract->dwt_range,
            'source' => $contract->source,
            'verified' => $contract->verified,
            'verified_at' => $contract->verified_at?->toIso8601String(),
            'notes' => $contract->notes,
            'created_at' => $contract->created_at?->toIso8601String(),
        ];

        // Vessel block (from vessels table if IMO matched)
        $vessel = $contract->relationLoaded('vessel') ? $contract->vessel : null;
        $base['vessel'] = $vessel ? [
            'imo' => $vessel->imo,
            'name' => $vessel->name,
            'type' => $vessel->type,
            'flag' => $vessel->flag,
            'dwt' => $vessel->dwt,
            'gt' => $vessel->gt,
            'length_m' => $vessel->length_m,
            'beam_m' => $vessel->beam_m,
            'year_built' => $vessel->year_built,
            'last_seen_at' => $vessel->last_seen_at?->toIso8601String(),
        ] : null;

        // Prefer new contract_ais_verifications over legacy ais_verifications
        $latestAis = $contract->relationLoaded('latestAisVerification')
            ? $contract->latestAisVerification
            : null;
        $legacyAis = $contract->relationLoaded('aisVerification')
            ? $contract->aisVerification
            : null;

        $base['ais_verification'] = self::aisBlock($contract, $legacyAis, $latestAis);

        return $base;
    }

    /**
     * Compute AIS verification status block.
     * Prefers new ContractAisVerification when available.
     */
    private static function aisBlock(
        CandidateContract $contract,
        ?AisVerification $ais,
        ?ContractAisVerification $latestAis = null,
    ): array {
        // No IMO → not applicable
        if (!$contract->vessel_imo) {
            return [
                'status' => AisVerification::STATUS_NOT_APPLICABLE,
                'reason' => 'MISSING_IMO',
                'confidence_score' => null,
                'verified_at' => null,
                'reasons' => null,
                'anomalies' => null,
                'evidence_summary' => null,
                'provider' => null,
            ];
        }

        // Use new engine data if available
        if ($latestAis) {
            return [
                'status' => $latestAis->status,
                'reason' => $latestAis->error_code ?? ($latestAis->reasons_json[0]['code'] ?? null),
                'confidence_score' => $latestAis->confidence_score,
                'verified_at' => $latestAis->verified_at?->toIso8601String(),
                'reasons' => $latestAis->reasons_json,
                'anomalies' => $latestAis->anomalies_json,
                'evidence_summary' => $latestAis->evidence_summary_json,
                'provider' => $latestAis->provider,
            ];
        }

        // Fall back to legacy record
        if (!$ais) {
            return [
                'status' => AisVerification::STATUS_PENDING,
                'reason' => null,
                'confidence_score' => null,
                'verified_at' => null,
                'reasons' => null,
                'anomalies' => null,
                'evidence_summary' => null,
                'provider' => null,
            ];
        }

        return [
            'status' => $ais->status,
            'reason' => $ais->failure_reason,
            'confidence_score' => $ais->confidence_score,
            'verified_at' => $ais->verified_at?->toIso8601String(),
            'reasons' => null,
            'anomalies' => null,
            'evidence_summary' => null,
            'provider' => null,
        ];
    }

    /**
     * Compute aggregate AIS KPIs for a candidate's contracts.
     */
    public static function aggregateKpis(iterable $contracts): array
    {
        $total = 0;
        $withImo = 0;
        $verified = 0;
        $pending = 0;
        $failed = 0;

        foreach ($contracts as $c) {
            $total++;
            if ($c->vessel_imo) {
                $withImo++;
                $ais = $c->relationLoaded('aisVerification') ? $c->aisVerification : null;
                if ($ais) {
                    match ($ais->status) {
                        AisVerification::STATUS_VERIFIED => $verified++,
                        AisVerification::STATUS_FAILED => $failed++,
                        default => $pending++,
                    };
                } else {
                    $pending++;
                }
            }
        }

        return [
            'total_contracts' => $total,
            'with_imo' => $withImo,
            'verified' => $verified,
            'pending' => $pending,
            'failed' => $failed,
        ];
    }
}
