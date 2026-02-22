<?php

namespace App\Console\Commands\Maritime;

use App\Models\PoolCandidate;
use App\Services\Maritime\CalibrationConfig;
use App\Services\Maritime\FleetTypeResolver;
use Illuminate\Console\Command;

class ShowCalibrationProfile extends Command
{
    protected $signature = 'maritime:calibration-profile
                            {candidate_id : Pool candidate UUID}';

    protected $description = 'Display the resolved calibration profile for a candidate';

    public function handle(FleetTypeResolver $resolver): int
    {
        $candidate = PoolCandidate::findOrFail($this->argument('candidate_id'));
        $fleetType = $resolver->resolve($candidate);
        $calibration = new CalibrationConfig($fleetType);

        $this->info("Candidate: {$candidate->first_name} {$candidate->last_name}");
        $this->info("Fleet Type: " . ($fleetType ?? 'none (defaults)'));
        $this->line('');

        $this->table(['Setting', 'Value'], [
            ['Competency Review Threshold', $calibration->competencyReviewThreshold()],
            ['Reject on Critical Flag', $calibration->rejectOnCriticalFlag() ? 'Yes' : 'No'],
            ['Technical Review Below', $calibration->technicalReviewBelow()],
            ['Correlation Enabled', $calibration->isCorrelationEnabled() ? 'Yes' : 'No'],
        ]);

        $this->line('');
        $this->info('Dimension Weights:');
        $weights = $calibration->competencyDimensionWeights();
        if ($weights) {
            foreach ($weights as $dim => $w) {
                $this->line("  {$dim}: {$w}");
            }
        } else {
            $this->line('  (using defaults from base config)');
        }

        $corrThresholds = $calibration->correlationThresholds();
        if (!empty($corrThresholds)) {
            $this->line('');
            $this->info('Correlation Thresholds:');
            foreach ($corrThresholds as $key => $val) {
                $this->line("  {$key}: {$val}");
            }
        }

        return 0;
    }
}
