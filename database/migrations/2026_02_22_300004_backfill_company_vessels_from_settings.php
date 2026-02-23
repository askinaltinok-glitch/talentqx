<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $companies = DB::table('companies')
            ->whereNotNull('settings')
            ->get(['id', 'settings']);

        foreach ($companies as $company) {
            $settings = json_decode($company->settings, true);
            $onboardVessels = $settings['onboard_vessels'] ?? [];

            foreach ($onboardVessels as $entry) {
                $vesselId = $entry['vessel_id'] ?? null;
                if (!$vesselId) {
                    continue;
                }

                // Skip if vessel doesn't exist
                $vesselExists = DB::table('vessels')->where('id', $vesselId)->exists();
                if (!$vesselExists) {
                    continue;
                }

                // Skip if already migrated
                $exists = DB::table('company_vessels')
                    ->where('company_id', $company->id)
                    ->where('vessel_id', $vesselId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('company_vessels')->insert([
                    'id' => Str::uuid(),
                    'company_id' => $company->id,
                    'vessel_id' => $vesselId,
                    'role' => 'operator',
                    'is_active' => true,
                    'assigned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Backfill cannot be reversed automatically — data remains in company_vessels
    }
};
