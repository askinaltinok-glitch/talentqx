<?php

namespace App\Services\Fleet;

use App\Models\CandidateCommandProfile;
use App\Models\CandidateContract;
use App\Models\PoolCandidate;
use Illuminate\Support\Facades\DB;

class CommandProfileService
{
    /**
     * Generate a derived command profile from source_meta + contracts.
     * Idempotent: returns existing profile if already generated.
     */
    public function generateDerived(string $candidateId): CandidateCommandProfile
    {
        return DB::transaction(function () use ($candidateId) {
            // Return existing if already generated
            $existing = CandidateCommandProfile::where('candidate_id', $candidateId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                // Re-enrich if still derived and completeness is low
                if ($existing->source === 'derived' && $existing->completeness_pct < 40) {
                    return $this->enrichProfile($existing, $candidateId);
                }
                return $existing;
            }

            $candidate = PoolCandidate::findOrFail($candidateId);
            $meta = $candidate->source_meta ?? [];

            // Extract from source_meta
            $rank = $this->normalizeRank(data_get($meta, 'rank'));
            $years = (int) data_get($meta, 'experience_years', 0);
            $rawCerts = (array) data_get($meta, 'certificates', []);
            $certs = array_values(array_unique(array_map([$this, 'normalizeCert'], $rawCerts)));
            $vesselTypes = (array) data_get($meta, 'vessel_types', []);

            // Extract from contracts
            $contracts = CandidateContract::where('pool_candidate_id', $candidateId)
                ->orderBy('start_date')
                ->get();

            $vesselExperience = $this->buildVesselExperience($contracts);
            $tradingAreas = $this->buildTradingAreas($contracts);
            $dwtHistory = $this->buildDwtHistory($contracts);

            // Compute completeness
            $completeness = $this->computeCompleteness($rank, $years, $certs, $vesselExperience, $tradingAreas);

            // Build missing fields list
            $missing = [];
            if (empty($vesselExperience)) $missing[] = 'vessel_history';
            if (empty($tradingAreas)) $missing[] = 'trading_areas';
            $missing[] = 'cargo_operations'; // always missing in derived

            return CandidateCommandProfile::create([
                'candidate_id' => $candidateId,
                'vessel_experience' => $vesselExperience ?: null,
                'dwt_history' => $dwtHistory ?: null,
                'trading_areas' => $tradingAreas ?: null,
                'cargo_history' => null,
                'crew_scale_history' => null,
                'automation_exposure' => null,
                'incident_history' => null,
                'raw_identity_answers' => [
                    'rank' => $rank,
                    'experience_years' => $years,
                    'certificates' => $certs,
                    'vessel_types_declared' => $vesselTypes,
                    'missing_fields' => $missing,
                    'notes' => 'Derived from source_meta + contract history',
                ],
                'derived_command_class' => null, // detection engine hasn't run
                'confidence_score' => null,
                'multi_class_flags' => null,
                'source' => 'derived',
                'completeness_pct' => $completeness,
                'generated_at' => now(),
            ]);
        });
    }

    /**
     * Re-enrich an existing derived profile with latest contract data.
     */
    private function enrichProfile(CandidateCommandProfile $profile, string $candidateId): CandidateCommandProfile
    {
        $contracts = CandidateContract::where('pool_candidate_id', $candidateId)
            ->orderBy('start_date')
            ->get();

        $vesselExperience = $this->buildVesselExperience($contracts);
        $tradingAreas = $this->buildTradingAreas($contracts);
        $dwtHistory = $this->buildDwtHistory($contracts);

        $candidate = PoolCandidate::find($candidateId);
        $meta = $candidate?->source_meta ?? [];
        $rank = $this->normalizeRank(data_get($meta, 'rank'));
        $years = (int) data_get($meta, 'experience_years', 0);
        $certs = array_values(array_unique(array_map([$this, 'normalizeCert'], (array) data_get($meta, 'certificates', []))));

        $completeness = $this->computeCompleteness($rank, $years, $certs, $vesselExperience, $tradingAreas);

        if (!empty($vesselExperience)) {
            $profile->vessel_experience = $vesselExperience;
        }
        if (!empty($tradingAreas)) {
            $profile->trading_areas = $tradingAreas;
        }
        if (!empty($dwtHistory)) {
            $profile->dwt_history = $dwtHistory;
        }

        $profile->completeness_pct = $completeness;
        $profile->generated_at = now();
        $profile->save();

        return $profile;
    }

    private function buildVesselExperience($contracts): array
    {
        $experience = [];
        foreach ($contracts as $c) {
            if (!$c->vessel_name && !$c->vessel_type) {
                continue;
            }
            $experience[] = [
                'vessel_name' => $c->vessel_name,
                'vessel_type' => $c->vessel_type,
                'vessel_imo' => $c->vessel_imo,
                'rank' => $c->rank_code,
                'company' => $c->company_name,
                'start_date' => $c->start_date?->toDateString(),
                'end_date' => $c->end_date?->toDateString(),
                'duration_months' => $c->start_date ? $c->durationMonths() : null,
            ];
        }
        return $experience;
    }

    private function buildTradingAreas($contracts): array
    {
        $areas = [];
        foreach ($contracts as $c) {
            if ($c->trading_area) {
                $areas[] = $c->trading_area;
            }
        }
        return array_values(array_unique($areas));
    }

    private function buildDwtHistory($contracts): ?array
    {
        $dwtValues = [];
        foreach ($contracts as $c) {
            if ($c->dwt_range) {
                // Parse ranges like "10000-20000" or single values
                $parts = explode('-', str_replace(' ', '', $c->dwt_range));
                foreach ($parts as $p) {
                    $val = (int) preg_replace('/[^0-9]/', '', $p);
                    if ($val > 0) {
                        $dwtValues[] = $val;
                    }
                }
            }
        }

        if (empty($dwtValues)) {
            return null;
        }

        return [
            'min' => min($dwtValues),
            'max' => max($dwtValues),
        ];
    }

    private function computeCompleteness(?string $rank, int $years, array $certs, array $vesselExp, array $tradingAreas): int
    {
        $score = 0;

        // Core identity data (max 30)
        if ($rank) $score += 10;
        if ($years > 0) $score += 10;
        if (count($certs) >= 3) $score += 10;

        // Contract-derived data (max 30)
        if (!empty($vesselExp)) $score += min(15, count($vesselExp) * 5);
        if (!empty($tradingAreas)) $score += 10;

        // These require Phase-1 interview (max 40 unreachable in derived)
        // cargo_operations, crew_scale, automation, incidents = 0

        return min(60, max(15, $score)); // derived cap at 60%
    }

    private function normalizeRank(?string $rank): ?string
    {
        if (!$rank) return null;

        $map = [
            'captain' => 'MASTER',
            'master' => 'MASTER',
            'chief_officer' => 'C/O',
            'chief officer' => 'C/O',
            'chief_mate' => 'C/O',
            'second_officer' => '2/O',
            '2nd officer' => '2/O',
            'third_officer' => '3/O',
            '3rd officer' => '3/O',
            'chief_engineer' => 'C/E',
            'chief engineer' => 'C/E',
            'second_engineer' => '2/E',
            '2nd engineer' => '2/E',
            'third_engineer' => '3/E',
            '3rd engineer' => '3/E',
            'fourth_engineer' => '4/E',
            'bosun' => 'BSN',
            'able_seaman' => 'AB',
            'oiler' => 'OL',
            'eto' => 'ETO',
            'electrician' => 'ELECTRO',
            'cook' => 'COOK',
            'chief_cook' => 'CH.COOK',
            'steward' => 'STEWARD',
            'messman' => 'MESS',
        ];

        $lower = strtolower(trim($rank));
        return $map[$lower] ?? strtoupper($rank);
    }

    private function normalizeCert(string $cert): string
    {
        $map = [
            'stcw' => 'STCW',
            'coc' => 'COC',
            'goc' => 'GOC',
            'medical' => 'Medical Certificate',
            'seamans_book' => "Seaman's Book",
            'passport' => 'Passport',
            'flag_endorsement' => 'Flag Endorsement',
            'tanker_endorsement' => 'Tanker Endorsement',
            'arpa' => 'ARPA',
            'ecdis' => 'ECDIS',
            'brm' => 'BRM',
            'erm' => 'ERM',
            'hazmat' => 'HAZMAT',
        ];

        return $map[strtolower(trim($cert))] ?? strtoupper($cert);
    }
}
