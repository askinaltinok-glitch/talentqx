<?php

namespace Database\Seeders;

use App\Models\CountryCertificateRule;
use Illuminate\Database\Seeder;

class CountryCertificateRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['country_code' => 'TR', 'certificate_type' => 'MEDICAL_FITNESS', 'validity_months' => 24, 'notes' => 'Turkey standard medical validity'],
            ['country_code' => 'TR', 'certificate_type' => 'FLAG_ENDORSEMENT', 'validity_months' => 12, 'notes' => 'Turkey flag endorsement annual renewal'],
            ['country_code' => 'PH', 'certificate_type' => 'MEDICAL_FITNESS', 'validity_months' => 12, 'notes' => 'Philippines medical annual requirement'],
            ['country_code' => 'PH', 'certificate_type' => 'FLAG_ENDORSEMENT', 'validity_months' => 12, 'notes' => 'Philippines flag endorsement annual'],
            ['country_code' => 'PA', 'certificate_type' => 'FLAG_ENDORSEMENT', 'validity_months' => 24, 'notes' => 'Panama flag endorsement 2-year'],
            ['country_code' => 'LR', 'certificate_type' => 'FLAG_ENDORSEMENT', 'validity_months' => 60, 'notes' => 'Liberia flag endorsement 5-year'],
            ['country_code' => 'MH', 'certificate_type' => 'FLAG_ENDORSEMENT', 'validity_months' => 60, 'notes' => 'Marshall Islands flag endorsement 5-year'],
            ['country_code' => 'AZ', 'certificate_type' => 'MEDICAL_FITNESS', 'validity_months' => 24, 'notes' => 'Azerbaijan standard medical validity'],
        ];

        foreach ($rules as $rule) {
            CountryCertificateRule::updateOrCreate(
                [
                    'country_code' => $rule['country_code'],
                    'certificate_type' => $rule['certificate_type'],
                ],
                $rule
            );
        }
    }
}
