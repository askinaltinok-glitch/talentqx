<?php

namespace App\Services\Behavioral;

use App\Models\CandidateCommandProfile;
use App\Models\CandidateContract;
use App\Models\PoolCandidate;
use App\Models\SeafarerCertificate;
use Illuminate\Support\Facades\Log;

/**
 * VesselFitEvidenceService
 *
 * Replaces thin provenance tagging with a full evidence-based vessel fit pipeline.
 * Every fit score answers "which data produced this?" with a source hierarchy,
 * evidence items, confidence levels, guardrails, and explainability signals.
 *
 * Source hierarchy (priority → confidence):
 *   contract_history (100, high)
 *   certificates     (80,  medium)
 *   experience_form  (60,  medium)
 *   interview_keywords (40, low)
 *   demo_seed        (10,  low)
 *   none             (0,   none)
 */
class VesselFitEvidenceService
{
    /** Map certificate types → vessel type codes they provide evidence for. */
    private const CERT_TO_VESSEL_MAP = [
        'TANKER_FAM'  => ['TANKER'],
        'TANKER_OIL'  => ['TANKER'],
        'TANKER_CHEM' => ['TANKER'],
        'TANKER_GAS'  => ['LNG', 'TANKER'],
        'DP_BASIC'    => ['OFFSHORE'],
        'DP_ADVANCED' => ['OFFSHORE'],
    ];

    /** Map vessel fit codes → contract vessel_type values (lowercase). */
    private const CODE_TO_CONTRACT_TYPE = [
        'TANKER'         => ['tanker', 'oil_tanker', 'chemical_tanker', 'product_tanker'],
        'LNG'            => ['lng', 'lpg', 'lng_lpg', 'gas_carrier'],
        'CONTAINER_ULCS' => ['container', 'container_ship', 'ulcs'],
        'PASSENGER'      => ['passenger', 'cruise', 'ferry', 'ro_pax'],
        'OFFSHORE'       => ['offshore', 'platform_supply', 'ahts', 'fpso', 'construction'],
        'RIVER'          => ['river', 'inland', 'barge'],
        'COASTAL'        => ['coastal', 'coaster'],
        'SHORT_SEA'      => ['short_sea', 'shortsea'],
        'DEEP_SEA'       => ['deep_sea', 'deepsea', 'ocean'],
    ];

    /** Map vessel fit codes → form/source_meta values (lowercase). */
    private const CODE_TO_FORM_TYPE = [
        'TANKER'         => ['tanker', 'oil_tanker', 'chemical_tanker', 'product_tanker'],
        'LNG'            => ['lng', 'lpg', 'lng_lpg', 'gas_carrier', 'lng/lpg', 'lpg/lng'],
        'CONTAINER_ULCS' => ['container', 'container_ship', 'ulcs'],
        'PASSENGER'      => ['passenger', 'cruise', 'ferry', 'ro_pax'],
        'OFFSHORE'       => ['offshore', 'platform_supply', 'ahts', 'fpso', 'construction'],
        'RIVER'          => ['river', 'inland', 'barge'],
        'COASTAL'        => ['coastal', 'coaster'],
        'SHORT_SEA'      => ['short_sea', 'shortsea'],
        'DEEP_SEA'       => ['deep_sea', 'deepsea', 'ocean'],
    ];

    /** LNG keyword whitelist — "gas" alone does NOT trigger LNG. */
    private const LNG_KEYWORDS = ['lng', 'liquefied natural gas', 'igf', 'lng carrier', 'lpg/lng', 'lng/lpg'];

    /** All vessel type codes in display order. */
    private const ALL_VESSEL_TYPES = [
        'TANKER', 'LNG', 'CONTAINER_ULCS', 'PASSENGER', 'OFFSHORE',
        'RIVER', 'COASTAL', 'SHORT_SEA', 'DEEP_SEA',
    ];

    /** Human-readable labels. */
    private const VESSEL_LABELS = [
        'TANKER'         => 'Tanker',
        'LNG'            => 'LNG/LPG',
        'CONTAINER_ULCS' => 'Container/ULCS',
        'PASSENGER'      => 'Passenger',
        'OFFSHORE'       => 'Offshore',
        'RIVER'          => 'River',
        'COASTAL'        => 'Coastal',
        'SHORT_SEA'      => 'Short Sea',
        'DEEP_SEA'       => 'Deep Sea',
    ];

    /**
     * Compute evidence-based vessel fit entries.
     *
     * @param string $candidateId       Pool candidate UUID
     * @param array  $behavioralFitJson The raw behavioral fit_json keyed by vessel code
     * @param float  $behavioralConfidence Behavioral profile confidence (0-1)
     * @return array Array of evidence entries, one per vessel type
     */
    public function compute(string $candidateId, array $behavioralFitJson, float $behavioralConfidence): array
    {
        try {
            $isDemo = $this->checkIsDemo($candidateId);
            $contractSignals = $this->gatherContractSignals($candidateId);
            $certificateSignals = $this->gatherCertificateSignals($candidateId);
            $formSignals = $this->gatherFormSignals($candidateId);

            $results = [];

            foreach (self::ALL_VESSEL_TYPES as $vesselType) {
                $behavioralEntry = $behavioralFitJson[$vesselType] ?? null;
                $behavioralFit = $behavioralEntry['normalized_fit'] ?? 50;

                $results[] = $this->computeForVesselType(
                    $vesselType,
                    $contractSignals,
                    $certificateSignals,
                    $formSignals,
                    $behavioralFit,
                    $behavioralConfidence,
                    $isDemo
                );
            }

            return $results;
        } catch (\Throwable $e) {
            Log::channel('single')->warning('VesselFitEvidenceService::compute failed', [
                'candidate_id' => $candidateId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Compute evidence entry for a single vessel type.
     */
    private function computeForVesselType(
        string $vesselType,
        array $contractSignals,
        array $certificateSignals,
        array $formSignals,
        int $behavioralFit,
        float $behavioralConfidence,
        bool $isDemo
    ): array {
        $evidence = [];
        $signals = [];
        $guardsApplied = [];
        $primarySource = 'none';
        $fitPct = 0;
        $confidence = 'none';

        // Demo handling
        if ($isDemo) {
            return $this->buildDemoEntry($vesselType, $behavioralFit);
        }

        // 1. Contract history
        $contractData = $contractSignals[$vesselType] ?? null;
        if ($contractData) {
            $monthsOnType = $contractData['months'];
            $totalMonths = $contractData['total_months'];
            $contractFit = $this->clamp(
                (int) round(($monthsOnType / max($totalMonths, 1)) * 100),
                30,
                95
            );
            if ($monthsOnType >= 12) {
                $contractFit = max($contractFit, 65);
            }

            $primarySource = 'contract_history';
            $fitPct = $contractFit;
            $confidence = 'high';

            $evidence[] = [
                'source' => 'contract_history',
                'key' => 'contracts',
                'label' => 'Contract experience',
                'detail' => "{$monthsOnType}mo on " . (self::VESSEL_LABELS[$vesselType] ?? $vesselType),
            ];
            $signals[] = [
                'source' => 'contract_history',
                'key' => 'months_on_type',
                'points' => $contractFit,
                'reason' => "{$monthsOnType}mo contract history ({$totalMonths}mo total)",
            ];
        }

        // 2. Certificates
        $certData = $certificateSignals[$vesselType] ?? [];
        if (!empty($certData)) {
            $isSpecialized = false;
            foreach ($certData as $cert) {
                $evidence[] = [
                    'source' => 'certificates',
                    'key' => $cert['type'],
                    'label' => $cert['label'],
                    'detail' => $cert['detail'],
                ];
                if (in_array($cert['type'], ['TANKER_GAS', 'DP_ADVANCED'])) {
                    $isSpecialized = true;
                }
            }

            $certFit = $isSpecialized ? 75 : 65;
            $signals[] = [
                'source' => 'certificates',
                'key' => $certData[0]['type'],
                'points' => $certFit,
                'reason' => ($isSpecialized ? 'Specialized' : 'Basic') . ' certificate held',
            ];

            if ($primarySource === 'none') {
                $primarySource = 'certificates';
                $fitPct = $certFit;
                $confidence = 'medium';
            }
        }

        // 3. Experience form / source_meta
        $formData = $formSignals[$vesselType] ?? null;
        if ($formData) {
            $evidence[] = [
                'source' => 'experience_form',
                'key' => 'declared',
                'label' => 'Form declaration',
                'detail' => $formData['detail'],
            ];
            $signals[] = [
                'source' => 'experience_form',
                'key' => 'form_declared',
                'points' => 50,
                'reason' => 'Candidate declared ' . (self::VESSEL_LABELS[$vesselType] ?? $vesselType) . ' experience',
            ];

            if ($primarySource === 'none') {
                $primarySource = 'experience_form';
                $fitPct = 50;
                $confidence = 'medium';
            }
        }

        // 4. Interview keywords (behavioral)
        $keywordOnlyCapped = false;
        $lngKeywordBlocked = false;

        if ($primarySource === 'none' && $behavioralFit > 0) {
            // LNG guardrail: keyword-only LNG requires whitelist match
            if ($vesselType === 'LNG') {
                // No contract, no cert, no form → behavioral only for LNG
                // Blocked: require cert/contract evidence
                $lngKeywordBlocked = true;
                $guardsApplied[] = 'lng_keyword_blocked';
                $primarySource = 'none';
                $confidence = 'none';
            } else {
                $cappedFit = min($behavioralFit, 60);
                $keywordOnlyCapped = ($behavioralFit > 60);
                $primarySource = 'interview_keywords';
                $fitPct = $cappedFit;
                $confidence = 'low';

                $signals[] = [
                    'source' => 'behavioral',
                    'key' => 'dimension_check',
                    'points' => $behavioralFit,
                    'reason' => "Behavioral fit {$behavioralFit}% (capped at 60%)",
                ];

                if ($keywordOnlyCapped) {
                    $guardsApplied[] = 'keyword_only_capped';
                }
            }
        }

        // Apply behavioral modifier for non-keyword primary sources
        if (!in_array($primarySource, ['none', 'interview_keywords', 'demo_seed'])) {
            $modifier = ($behavioralFit - 50) * 0.15;
            $fitPct = $this->clamp((int) round($fitPct + $modifier), 0, 100);

            $signals[] = [
                'source' => 'behavioral',
                'key' => 'dimension_check',
                'points' => $behavioralFit,
                'reason' => "Behavioral modifier " . ($modifier >= 0 ? '+' : '') . round($modifier, 1) . " applied",
            ];
        }

        $rendered = ($primarySource !== 'none');

        return [
            'vessel_type' => $vesselType,
            'fit_pct' => $fitPct,
            'confidence' => $confidence,
            'primary_source' => $primarySource,
            'evidence' => $evidence,
            'guards' => [
                'keyword_only_capped' => $keywordOnlyCapped,
                'lng_keyword_blocked' => $lngKeywordBlocked,
                'rendered' => $rendered,
            ],
            'behavioral_fit' => $behavioralFit,
            'explain' => [
                'signals' => $signals,
                'guards_applied' => $guardsApplied,
            ],
        ];
    }

    /**
     * Build a demo entry — all sources become demo_seed.
     */
    private function buildDemoEntry(string $vesselType, int $behavioralFit): array
    {
        return [
            'vessel_type' => $vesselType,
            'fit_pct' => $behavioralFit,
            'confidence' => 'low',
            'primary_source' => 'demo_seed',
            'evidence' => [[
                'source' => 'demo_seed',
                'key' => 'demo',
                'label' => 'Demo candidate',
                'detail' => 'Synthetic data — behavioral fit used as-is',
            ]],
            'guards' => [
                'keyword_only_capped' => false,
                'lng_keyword_blocked' => false,
                'rendered' => true,
            ],
            'behavioral_fit' => $behavioralFit,
            'explain' => [
                'signals' => [[
                    'source' => 'demo_seed',
                    'key' => 'demo_behavioral',
                    'points' => $behavioralFit,
                    'reason' => 'Demo candidate — behavioral fit passthrough',
                ]],
                'guards_applied' => [],
            ],
        ];
    }

    // ─── Data Gatherers ─────────────────────────────────────────────────────

    /**
     * Gather contract signals grouped by vessel type.
     * Returns [VESSEL_CODE => ['months' => float, 'total_months' => float, 'contracts' => int]]
     */
    private function gatherContractSignals(string $candidateId): array
    {
        $contracts = CandidateContract::where('pool_candidate_id', $candidateId)
            ->whereNotNull('vessel_type')
            ->get();

        if ($contracts->isEmpty()) {
            return [];
        }

        $totalMonths = 0;
        $byCode = [];

        foreach ($contracts as $contract) {
            $months = $contract->durationMonths();
            $totalMonths += $months;
            $rawType = strtolower(trim($contract->vessel_type));

            foreach (self::CODE_TO_CONTRACT_TYPE as $code => $mapped) {
                foreach ($mapped as $m) {
                    if ($rawType === $m || str_contains($rawType, $m) || str_contains($m, $rawType)) {
                        if (!isset($byCode[$code])) {
                            $byCode[$code] = ['months' => 0, 'contracts' => 0];
                        }
                        $byCode[$code]['months'] += $months;
                        $byCode[$code]['contracts']++;
                        break 2;
                    }
                }
            }
        }

        foreach ($byCode as $code => &$data) {
            $data['total_months'] = max($totalMonths, 1);
            $data['months'] = (int) round($data['months']);
        }
        unset($data);

        return $byCode;
    }

    /**
     * Gather certificate signals mapped to vessel types.
     * Returns [VESSEL_CODE => [ ['type' => ..., 'label' => ..., 'detail' => ...], ... ]]
     */
    private function gatherCertificateSignals(string $candidateId): array
    {
        $certificates = SeafarerCertificate::where('pool_candidate_id', $candidateId)
            ->get();

        if ($certificates->isEmpty()) {
            return [];
        }

        $byCode = [];

        foreach ($certificates as $cert) {
            $certType = strtoupper(trim($cert->certificate_type));
            $vesselCodes = self::CERT_TO_VESSEL_MAP[$certType] ?? null;

            if (!$vesselCodes) {
                continue;
            }

            foreach ($vesselCodes as $vesselCode) {
                $byCode[$vesselCode][] = [
                    'type' => $certType,
                    'label' => $this->certLabel($certType),
                    'detail' => $cert->expires_at
                        ? 'Expires ' . $cert->expires_at
                        : 'No expiry recorded',
                ];
            }
        }

        return $byCode;
    }

    /**
     * Gather form/source_meta signals.
     * Returns [VESSEL_CODE => ['detail' => string]]
     */
    private function gatherFormSignals(string $candidateId): array
    {
        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            return [];
        }

        $types = [];

        // source_meta.vessel_types
        if ($candidate->source_meta && !empty($candidate->source_meta['vessel_types'])) {
            $types = array_merge($types, array_map(
                fn($v) => strtolower(trim($v)),
                $candidate->source_meta['vessel_types']
            ));
        }

        // CandidateCommandProfile vessel types
        $profile = CandidateCommandProfile::where('candidate_id', $candidateId)->first();
        if ($profile) {
            $types = array_merge($types, array_map(
                fn($v) => strtolower(trim($v)),
                $profile->getVesselTypes()
            ));
        }

        $types = array_unique($types);

        if (empty($types)) {
            return [];
        }

        $byCode = [];

        foreach (self::CODE_TO_FORM_TYPE as $code => $mapped) {
            foreach ($mapped as $m) {
                foreach ($types as $t) {
                    if ($t === $m || str_contains($t, $m) || str_contains($m, $t)) {
                        $byCode[$code] = [
                            'detail' => 'Declared in application form',
                        ];
                        break 2;
                    }
                }
            }
        }

        return $byCode;
    }

    /**
     * Check if candidate is a demo candidate.
     */
    private function checkIsDemo(string $candidateId): bool
    {
        return (bool) PoolCandidate::where('id', $candidateId)
            ->where('is_demo', true)
            ->exists();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    private function certLabel(string $certType): string
    {
        return match ($certType) {
            'TANKER_FAM'  => 'Basic Tanker Familiarization',
            'TANKER_OIL'  => 'Oil Tanker Training',
            'TANKER_CHEM' => 'Chemical Tanker Training',
            'TANKER_GAS'  => 'LNG/LPG Gas Training',
            'DP_BASIC'    => 'DP Basic Certificate',
            'DP_ADVANCED' => 'DP Advanced Certificate',
            default       => $certType,
        };
    }
}
