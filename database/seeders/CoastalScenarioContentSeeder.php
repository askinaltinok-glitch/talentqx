<?php

namespace Database\Seeders;

use App\Models\MaritimeScenario;
use Illuminate\Database\Seeder;

/**
 * Populate COASTAL scenarios with production-quality content.
 *
 * Idempotent: updates existing rows by scenario_code.
 * Run: php82 artisan db:seed --class=CoastalScenarioContentSeeder --force
 */
class CoastalScenarioContentSeeder extends Seeder
{
    public function run(): void
    {
        $scenarios = array_merge(
            $this->getScenariosSlot1to4(),
            $this->getScenariosSlot5to8(),
        );

        foreach ($scenarios as $code => $data) {
            MaritimeScenario::where('scenario_code', $code)->update($data);
            $this->command->info("Updated: {$code}");
        }

        $activated = MaritimeScenario::where('command_class', 'COASTAL')
            ->where('version', 'v2')
            ->update(['is_active' => true]);

        $this->command->info("COASTAL scenario content seeded and activated ({$activated} scenarios).");
    }

    private function getScenariosSlot1to4(): array
    {
        return require __DIR__ . '/CoastalScenarioContentSeeder_slots1to4.php';
    }

    private function getScenariosSlot5to8(): array
    {
        return require __DIR__ . '/CoastalScenarioContentSeeder_slots5to8.php';
    }
}
