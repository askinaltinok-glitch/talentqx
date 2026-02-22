<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Master seeder for Interview Template Language Calibration (Phase B).
 *
 * Ensures all 7 locales have structurally equivalent maritime interview templates.
 * Each sub-seeder uses updateOrCreate on (version, language, position_code) so this
 * is idempotent and safe to re-run at any time.
 *
 * Usage:
 *   php82 artisan db:seed --class=InterviewTemplateCalibrationSeeder --force
 *
 * What it does:
 *   - AZ: Seeds the missing __generic__ template
 *   - FIL: Seeds all 23 maritime templates (Filipino)
 *   - ID:  Seeds all 23 maritime templates (Indonesian)
 *   - UK:  Seeds all 23 maritime templates (Ukrainian)
 *
 * EN, TR, RU templates already exist and are not touched by this seeder.
 * Each sub-seeder is idempotent (updateOrCreate), so running this multiple
 * times is safe — it will update existing records, not create duplicates.
 */
class InterviewTemplateCalibrationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Interview Template Calibration — Phase B');
        $this->command->info('=========================================');

        $this->call(AzGenericTemplateSeeder::class);
        $this->call(FilInterviewTemplatesSeeder::class);
        $this->call(IdInterviewTemplatesSeeder::class);
        $this->call(UkInterviewTemplatesSeeder::class);

        $this->command->info('');
        $this->command->info('Calibration complete. Run tests:');
        $this->command->info('  php82 vendor/bin/phpunit tests/Feature/I18n/InterviewTemplateParityTest.php --no-configuration --bootstrap vendor/autoload.php');
    }
}
