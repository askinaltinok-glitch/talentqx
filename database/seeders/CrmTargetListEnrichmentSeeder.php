<?php

namespace Database\Seeders;

use App\Models\CrmCompany;
use App\Models\ResearchCompany;
use Illuminate\Database\Seeder;

class CrmTargetListEnrichmentSeeder extends Seeder
{
    public function run(): void
    {
        $enrichments = [
            // EU region
            'vships.com' => ['region' => 'EU', 'vessel_type' => 'mixed'],
            'columbia-shipmanagement.com' => ['region' => 'EU', 'vessel_type' => 'mixed'],
            'bs-shipmanagement.com' => ['region' => 'EU', 'vessel_type' => 'mixed'],
            'schultegroup.com' => ['region' => 'EU', 'vessel_type' => 'mixed'],
            'osm.no' => ['region' => 'EU', 'vessel_type' => 'offshore'],
            'wilhelmsen.com' => ['region' => 'EU', 'vessel_type' => 'mixed'],

            // APAC region
            'angloeastern.com' => ['region' => 'APAC', 'vessel_type' => 'mixed'],
            'synergymarinegroup.com' => ['region' => 'APAC', 'vessel_type' => 'mixed'],
            'fleetship.com' => ['region' => 'APAC', 'vessel_type' => 'mixed'],
            'thome.com.sg' => ['region' => 'APAC', 'vessel_type' => 'mixed'],

            // Turkey
            'densa.com.tr' => ['region' => 'TR', 'vessel_type' => 'tanker'],
            'besiktasgroup.com' => ['region' => 'TR', 'vessel_type' => 'tanker'],
            'turkon.com' => ['region' => 'TR', 'vessel_type' => 'container'],
        ];

        $updated = 0;
        foreach ($enrichments as $domain => $tags) {
            // Update in research_companies (where target list lives)
            $research = ResearchCompany::where('domain', $domain)->first();
            if ($research) {
                $existing = $research->classification ?? [];
                $research->update([
                    'classification' => array_merge($existing, $tags),
                ]);
                $updated++;
            }

            // Also update in crm_companies if pushed there
            $crm = CrmCompany::where('domain', $domain)->first();
            if ($crm) {
                $existingTags = $crm->tags ?? [];
                $crm->update(['tags' => array_merge($existingTags, $tags)]);
            }
        }

        $this->command->info("Enriched {$updated} target companies with region + vessel_type tags.");
    }
}
