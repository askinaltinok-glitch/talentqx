<?php

namespace App\Services\Fleet;

use App\Models\CompanyVesselRequirementOverride;
use App\Models\FleetVessel;
use App\Models\VesselRequirementTemplate;
use Illuminate\Support\Facades\Log;

class VesselRequirementProfileService
{
    /**
     * Map display vessel_type string → template key.
     */
    private const TYPE_MAP = [
        'Tanker'              => 'tanker',
        'Chemical Tanker'     => 'tanker',
        'LNG/LPG Carrier'    => 'lng',
        'Bulk Carrier'        => 'bulk',
        'Container Ship'      => 'container',
        'Passenger / Cruise'  => 'passenger',
        'Offshore / Platform' => 'offshore',
        'General Cargo'       => 'bulk',
        'Ro-Ro'               => 'container',
        'Tug / Barge'         => 'offshore',
        'Fishing Vessel'      => 'bulk',
        'River Vessel'        => 'bulk',
    ];

    /**
     * Resolve the merged requirement profile for a vessel.
     * Returns null if feature flag is off or no matching template.
     */
    public function resolve(FleetVessel $vessel): ?array
    {
        if (!config('maritime.vessel_requirement_engine_v1')) {
            return null; // feature flag off — silent, no log needed
        }

        $typeKey = $this->resolveTypeKey($vessel->vessel_type ?? '');
        if (!$typeKey) {
            Log::info('VesselRequirementProfileService: no type mapping, falling back to legacy scoring', [
                'vessel_id' => $vessel->id,
                'vessel_type' => $vessel->vessel_type,
            ]);
            return null;
        }

        $template = VesselRequirementTemplate::where('vessel_type_key', $typeKey)
            ->where('is_active', true)
            ->where('status', 'published')
            ->first();

        if (!$template) {
            Log::info('VesselRequirementProfileService: no active template found, falling back to legacy scoring', [
                'vessel_id' => $vessel->id,
                'vessel_type' => $vessel->vessel_type,
                'type_key' => $typeKey,
            ]);
            return null;
        }

        $profile = $template->profile_json;

        // Check for company override
        if ($vessel->company_id) {
            $override = CompanyVesselRequirementOverride::forCompanyAndType(
                $vessel->company_id,
                $typeKey
            );

            if ($override && is_array($override->overrides_json)) {
                $profile = $this->mergeProfile($profile, $override->overrides_json);
            }
        }

        // Normalize weights
        if (isset($profile['weights'])) {
            $profile['weights'] = $this->normalizeWeights($profile['weights']);
        }

        return $profile;
    }

    /**
     * Map a vessel_type display string to a template key.
     */
    public function resolveTypeKey(?string $vesselType): ?string
    {
        if (!$vesselType) {
            return null;
        }

        // Exact match first
        if (isset(self::TYPE_MAP[$vesselType])) {
            return self::TYPE_MAP[$vesselType];
        }

        // Case-insensitive match
        $lower = strtolower(trim($vesselType));
        foreach (self::TYPE_MAP as $display => $key) {
            if (strtolower($display) === $lower) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Deep-merge template profile with sparse override.
     */
    public function mergeProfile(array $template, array $override): array
    {
        $result = $template;

        foreach ($override as $key => $value) {
            if ($key === 'required_certificates' && is_array($value)) {
                $result['required_certificates'] = $this->mergeCertificates(
                    $template['required_certificates'] ?? [],
                    $value
                );
            } elseif (($key === 'behavior_thresholds' || $key === 'weights') && is_array($value)) {
                $result[$key] = array_merge($template[$key] ?? [], $value);
            } elseif ($key === 'experience' && is_array($value)) {
                $result['experience'] = array_merge($template['experience'] ?? [], $value);
            } else {
                // Scalars: override wins
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Extract and normalize weights to sum = 1.0.
     */
    public function resolveWeights(array $profile): array
    {
        $weights = $profile['weights'] ?? [
            'cert_fit' => 0.25,
            'experience_fit' => 0.25,
            'behavior_fit' => 0.25,
            'availability_fit' => 0.25,
        ];

        return $this->normalizeWeights($weights);
    }

    /**
     * Merge certificates by certificate_type — override can add new or replace existing.
     */
    private function mergeCertificates(array $templateCerts, array $overrideCerts): array
    {
        $merged = [];
        foreach ($templateCerts as $cert) {
            $merged[$cert['certificate_type']] = $cert;
        }

        foreach ($overrideCerts as $cert) {
            if (!isset($cert['certificate_type'])) {
                continue;
            }
            if (isset($merged[$cert['certificate_type']])) {
                $merged[$cert['certificate_type']] = array_merge(
                    $merged[$cert['certificate_type']],
                    $cert
                );
            } else {
                $merged[$cert['certificate_type']] = $cert;
            }
        }

        return array_values($merged);
    }

    /**
     * Normalize weight values to sum to 1.0.
     */
    private function normalizeWeights(array $weights): array
    {
        $sum = array_sum($weights);
        if ($sum <= 0) {
            return $weights;
        }

        $normalized = [];
        foreach ($weights as $key => $val) {
            $normalized[$key] = round($val / $sum, 4);
        }

        return $normalized;
    }
}
