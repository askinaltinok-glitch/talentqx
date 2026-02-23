<?php

namespace Database\Seeders;

use App\Models\VesselRequirementTemplate;
use Illuminate\Database\Seeder;

class VesselRequirementTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'vessel_type_key' => 'bulk',
                'label' => 'Bulk Carrier',
                'profile_json' => [
                    'required_certificates' => [
                        ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                        ['certificate_type' => 'MEDICAL_FITNESS', 'min_remaining_months' => 6, 'mandatory' => true],
                        ['certificate_type' => 'BST', 'min_remaining_months' => 0, 'mandatory' => true],
                    ],
                    'experience' => [
                        'vessel_type_min_months' => 12,
                    ],
                    'behavior_thresholds' => [
                        'discipline' => 0.6,
                        'stress_tolerance' => 0.5,
                        'leadership' => 0.6,
                    ],
                    'weights' => [
                        'cert_fit' => 0.35,
                        'experience_fit' => 0.30,
                        'behavior_fit' => 0.20,
                        'availability_fit' => 0.15,
                    ],
                    'risk_level' => 'low',
                ],
            ],
            [
                'vessel_type_key' => 'container',
                'label' => 'Container Ship',
                'profile_json' => [
                    'required_certificates' => [
                        ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                        ['certificate_type' => 'MEDICAL_FITNESS', 'min_remaining_months' => 6, 'mandatory' => true],
                        ['certificate_type' => 'BRM', 'min_remaining_months' => 0, 'mandatory' => true],
                    ],
                    'experience' => [
                        'vessel_type_min_months' => 18,
                    ],
                    'behavior_thresholds' => [
                        'discipline' => 0.7,
                        'stress_tolerance' => 0.6,
                        'leadership' => 0.6,
                    ],
                    'weights' => [
                        'cert_fit' => 0.30,
                        'experience_fit' => 0.30,
                        'behavior_fit' => 0.25,
                        'availability_fit' => 0.15,
                    ],
                    'risk_level' => 'medium',
                ],
            ],
            [
                'vessel_type_key' => 'tanker',
                'label' => 'Tanker',
                'profile_json' => [
                    'required_certificates' => [
                        ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                        ['certificate_type' => 'MEDICAL_FITNESS', 'min_remaining_months' => 6, 'mandatory' => true],
                        ['certificate_type' => 'ADV_FIRE', 'min_remaining_months' => 0, 'mandatory' => true],
                        ['certificate_type' => 'TANKER_ENDORSEMENT', 'min_remaining_months' => 0, 'mandatory' => true, 'hard_block' => true, 'block_reason_key' => 'missing_tanker_endorsement'],
                    ],
                    'experience' => [
                        'vessel_type_min_months' => 24,
                    ],
                    'behavior_thresholds' => [
                        'discipline' => 0.75,
                        'stress_tolerance' => 0.7,
                        'leadership' => 0.7,
                    ],
                    'weights' => [
                        'cert_fit' => 0.40,
                        'experience_fit' => 0.30,
                        'behavior_fit' => 0.20,
                        'availability_fit' => 0.10,
                    ],
                    'risk_level' => 'high',
                    'advisory_message' => 'High risk vessel. Consider increasing Medical minimum to 12 months.',
                ],
            ],
            [
                'vessel_type_key' => 'lng',
                'label' => 'LNG/LPG Carrier',
                'profile_json' => [
                    'required_certificates' => [
                        ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                        ['certificate_type' => 'MEDICAL_FITNESS', 'min_remaining_months' => 6, 'mandatory' => true],
                        ['certificate_type' => 'ADV_FIRE', 'min_remaining_months' => 0, 'mandatory' => true],
                        ['certificate_type' => 'LNG_ENDORSEMENT', 'min_remaining_months' => 0, 'mandatory' => true, 'hard_block' => true, 'block_reason_key' => 'missing_lng_endorsement'],
                    ],
                    'experience' => [
                        'vessel_type_min_months' => 30,
                    ],
                    'behavior_thresholds' => [
                        'discipline' => 0.8,
                        'stress_tolerance' => 0.75,
                        'leadership' => 0.75,
                    ],
                    'weights' => [
                        'cert_fit' => 0.45,
                        'experience_fit' => 0.30,
                        'behavior_fit' => 0.15,
                        'availability_fit' => 0.10,
                    ],
                    'risk_level' => 'very_high',
                    'advisory_message' => 'LNG operations typically require stricter medical validity (12 months recommended).',
                ],
            ],
            [
                'vessel_type_key' => 'offshore',
                'label' => 'Offshore / Platform',
                'profile_json' => [
                    'required_certificates' => [
                        ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                        ['certificate_type' => 'MEDICAL_FITNESS', 'min_remaining_months' => 6, 'mandatory' => true],
                        ['certificate_type' => 'DP_CERT', 'min_remaining_months' => 0, 'mandatory' => true, 'hard_block' => true, 'block_reason_key' => 'missing_dp_certification'],
                    ],
                    'experience' => [
                        'vessel_type_min_months' => 24,
                    ],
                    'behavior_thresholds' => [
                        'discipline' => 0.7,
                        'stress_tolerance' => 0.75,
                        'leadership' => 0.7,
                    ],
                    'weights' => [
                        'cert_fit' => 0.35,
                        'experience_fit' => 0.25,
                        'behavior_fit' => 0.25,
                        'availability_fit' => 0.15,
                    ],
                    'risk_level' => 'high',
                    'advisory_message' => 'Offshore operations recommended medical minimum 12 months.',
                ],
            ],
            [
                'vessel_type_key' => 'passenger',
                'label' => 'Passenger / Cruise',
                'profile_json' => [
                    'required_certificates' => [
                        ['certificate_type' => 'COC', 'min_remaining_months' => 6, 'mandatory' => true],
                        ['certificate_type' => 'MEDICAL_FITNESS', 'min_remaining_months' => 6, 'mandatory' => true],
                        ['certificate_type' => 'CROWD_MANAGEMENT', 'min_remaining_months' => 0, 'mandatory' => true],
                    ],
                    'experience' => [
                        'vessel_type_min_months' => 18,
                    ],
                    'behavior_thresholds' => [
                        'discipline' => 0.7,
                        'stress_tolerance' => 0.6,
                        'leadership' => 0.7,
                    ],
                    'weights' => [
                        'cert_fit' => 0.30,
                        'experience_fit' => 0.25,
                        'behavior_fit' => 0.30,
                        'availability_fit' => 0.15,
                    ],
                    'risk_level' => 'medium',
                ],
            ],
        ];

        foreach ($templates as $data) {
            VesselRequirementTemplate::updateOrCreate(
                ['vessel_type_key' => $data['vessel_type_key']],
                $data
            );
        }
    }
}
