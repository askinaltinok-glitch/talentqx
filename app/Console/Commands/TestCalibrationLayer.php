<?php

namespace App\Console\Commands;

use App\Models\FormInterview;
use Illuminate\Console\Command;

class TestCalibrationLayer extends Command
{
    protected $signature = 'calibration:test {id}';
    protected $description = 'Print calibration and policy fields for a completed form_interview';

    public function handle(): int
    {
        $id = $this->argument('id');

        $fi = FormInterview::query()->find($id);
        if (!$fi) {
            $this->error("Not found: {$id}");
            return self::FAILURE;
        }

        $this->info("=== Interview: {$fi->id} ===");
        $this->line("status: {$fi->status}");
        $this->line("position: {$fi->template_position_code}");
        $this->newLine();

        $this->info("--- Raw (DecisionEngine) ---");
        $this->line("raw_final_score: " . ($fi->raw_final_score ?? 'null'));
        $this->line("raw_decision: " . ($fi->raw_decision ?? 'null'));
        $this->newLine();

        $this->info("--- Calibration ---");
        $this->line("mean/std: " . ($fi->position_mean_score ?? 'null') . " / " . ($fi->position_std_dev_score ?? 'null'));
        $this->line("z_score: " . ($fi->z_score ?? 'null'));
        $this->line("calibrated_score: " . ($fi->calibrated_score ?? 'null'));
        $this->line("calibration_version: " . ($fi->calibration_version ?? 'null'));
        $this->newLine();

        $this->info("--- Policy (Final) ---");
        $this->line("final_score: " . ($fi->final_score ?? 'null'));
        $this->line("decision: " . ($fi->decision ?? 'null'));
        $this->line("policy_code: " . ($fi->policy_code ?? 'null'));
        $this->line("policy_version: " . ($fi->policy_version ?? 'null'));
        $this->line("reason: " . ($fi->decision_reason ?? ''));
        $this->newLine();

        // Show risk flags if any
        $riskFlags = $fi->risk_flags ?? [];
        if (!empty($riskFlags)) {
            $this->warn("--- Risk Flags ---");
            foreach ($riskFlags as $flag) {
                $code = is_array($flag) ? ($flag['code'] ?? 'unknown') : $flag;
                $severity = is_array($flag) ? ($flag['severity'] ?? '-') : '-';
                $this->line("  {$code} ({$severity})");
            }
        }

        return self::SUCCESS;
    }
}
