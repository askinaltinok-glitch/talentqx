<?php

namespace Database\Seeders;

use App\Models\ResearchCompany;
use Illuminate\Database\Seeder;

class CrmTargetListSeeder extends Seeder
{
    public function run(): void
    {
        $targets = [
            // Top Maritime Ship Managers
            ['name' => 'V.Ships', 'domain' => 'vships.com', 'country' => 'UK', 'industry' => 'maritime', 'fleet_type' => 'mixed', 'fleet_size_est' => 1000, 'crew_size_est' => 40000, 'maritime_flag' => true],
            ['name' => 'Anglo-Eastern', 'domain' => 'angloeastern.com', 'country' => 'HK', 'industry' => 'maritime', 'fleet_type' => 'mixed', 'fleet_size_est' => 600, 'crew_size_est' => 27000, 'maritime_flag' => true],
            ['name' => 'Synergy Marine Group', 'domain' => 'synergymarinegroup.com', 'country' => 'SG', 'industry' => 'maritime', 'fleet_type' => 'mixed', 'fleet_size_est' => 500, 'crew_size_est' => 18000, 'maritime_flag' => true],
            ['name' => 'Columbia Shipmanagement', 'domain' => 'columbia-shipmanagement.com', 'country' => 'CY', 'industry' => 'maritime', 'fleet_type' => 'mixed', 'fleet_size_est' => 400, 'crew_size_est' => 16000, 'maritime_flag' => true],
            ['name' => 'BSM (Bernhard Schulte)', 'domain' => 'bs-shipmanagement.com', 'country' => 'DE', 'industry' => 'maritime', 'fleet_type' => 'mixed', 'fleet_size_est' => 600, 'crew_size_est' => 20000, 'maritime_flag' => true],
            ['name' => 'Fleet Management', 'domain' => 'fleetship.com', 'country' => 'HK', 'industry' => 'maritime', 'fleet_type' => 'mixed', 'fleet_size_est' => 500, 'crew_size_est' => 18000, 'maritime_flag' => true],
            ['name' => 'Thome Group', 'domain' => 'thome.com.sg', 'country' => 'SG', 'industry' => 'maritime', 'fleet_type' => 'mixed', 'fleet_size_est' => 350, 'crew_size_est' => 12000, 'maritime_flag' => true],
            ['name' => 'Schulte Group', 'domain' => 'schultegroup.com', 'country' => 'DE', 'industry' => 'maritime', 'fleet_type' => 'mixed', 'fleet_size_est' => 600, 'crew_size_est' => 20000, 'maritime_flag' => true],
            ['name' => 'OSM Maritime', 'domain' => 'osm.no', 'country' => 'NO', 'industry' => 'maritime', 'fleet_type' => 'offshore', 'fleet_size_est' => 200, 'crew_size_est' => 10000, 'maritime_flag' => true],
            ['name' => 'Wilhelmsen Ship Management', 'domain' => 'wilhelmsen.com', 'country' => 'NO', 'industry' => 'maritime', 'fleet_type' => 'mixed', 'fleet_size_est' => 400, 'crew_size_est' => 9000, 'maritime_flag' => true],

            // Turkish Maritime Manning Agents
            ['name' => 'Densa Denizcilik', 'domain' => 'densa.com.tr', 'country' => 'TR', 'industry' => 'maritime', 'fleet_type' => 'tanker', 'fleet_size_est' => 30, 'crew_size_est' => 1200, 'maritime_flag' => true],
            ['name' => 'Besiktas Shipping', 'domain' => 'besiktasgroup.com', 'country' => 'TR', 'industry' => 'maritime', 'fleet_type' => 'tanker', 'fleet_size_est' => 50, 'crew_size_est' => 2000, 'maritime_flag' => true],
            ['name' => 'Turkon Line', 'domain' => 'turkon.com', 'country' => 'TR', 'industry' => 'maritime', 'fleet_type' => 'container', 'fleet_size_est' => 20, 'crew_size_est' => 800, 'maritime_flag' => true],

            // HR / General Targets
            ['name' => 'ManpowerGroup', 'domain' => 'manpowergroup.com', 'country' => 'US', 'industry' => 'general', 'fleet_type' => null, 'fleet_size_est' => null, 'crew_size_est' => null, 'maritime_flag' => false],
            ['name' => 'Randstad', 'domain' => 'randstad.com', 'country' => 'NL', 'industry' => 'general', 'fleet_type' => null, 'fleet_size_est' => null, 'crew_size_est' => null, 'maritime_flag' => false],
            ['name' => 'Hays', 'domain' => 'hays.com', 'country' => 'UK', 'industry' => 'general', 'fleet_type' => null, 'fleet_size_est' => null, 'crew_size_est' => null, 'maritime_flag' => false],
        ];

        $created = 0;
        foreach ($targets as $t) {
            $company = ResearchCompany::firstOrCreate(
                ['domain' => $t['domain']],
                [
                    'name' => $t['name'],
                    'country' => $t['country'],
                    'industry' => $t['industry'],
                    'maritime_flag' => $t['maritime_flag'],
                    'fleet_type' => $t['fleet_type'],
                    'fleet_size_est' => $t['fleet_size_est'],
                    'crew_size_est' => $t['crew_size_est'],
                    'target_list' => true,
                    'hiring_signal_score' => 60,
                    'source' => ResearchCompany::SOURCE_MANUAL,
                    'status' => ResearchCompany::STATUS_DISCOVERED,
                    'website' => "https://{$t['domain']}",
                    'discovered_at' => now(),
                ]
            );

            if ($company->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info("Seeded {$created} target companies (" . count($targets) . " total checked).");
    }
}
