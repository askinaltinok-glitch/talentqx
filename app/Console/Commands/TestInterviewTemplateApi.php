<?php

namespace App\Console\Commands;

use App\Models\InterviewTemplate;
use App\Services\Interview\InterviewTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestInterviewTemplateApi extends Command
{
    protected $signature = 'interview-templates:test';
    protected $description = 'Test InterviewTemplateService and API layer assertions';

    public function handle(InterviewTemplateService $service): int
    {
        $this->info("\n╔═══════════════════════════════════════════════════════════════════════════════╗");
        $this->info("║               INTERVIEW TEMPLATE API TEST SUITE                              ║");
        $this->info("╚═══════════════════════════════════════════════════════════════════════════════╝\n");

        $passed = 0;
        $failed = 0;

        // ===========================================
        // TEST 1: Requesting retail_cashier returns that
        // ===========================================
        $this->line("TEST 1: Requesting 'retail_cashier' returns retail_cashier");
        try {
            $template = $service->getTemplate('v1', 'tr', 'retail_cashier');
            if ($template->position_code === 'retail_cashier') {
                $this->info("  ✓ PASSED: position_code = '{$template->position_code}'");
                $passed++;
            } else {
                $this->error("  ✗ FAILED: Expected 'retail_cashier', got '{$template->position_code}'");
                $failed++;
            }
        } catch (\Exception $e) {
            $this->error("  ✗ FAILED: Exception - {$e->getMessage()}");
            $failed++;
        }

        // ===========================================
        // TEST 2: Requesting nonexistent returns __generic__
        // ===========================================
        $this->line("\nTEST 2: Requesting 'nonexistent_position' returns __generic__ (fallback)");
        try {
            $template = $service->getTemplate('v1', 'tr', 'nonexistent_position');
            if ($template->position_code === InterviewTemplateService::GENERIC_POSITION_CODE) {
                $this->info("  ✓ PASSED: Fallback to '{$template->position_code}'");
                $passed++;
            } else {
                $this->error("  ✗ FAILED: Expected '__generic__', got '{$template->position_code}'");
                $failed++;
            }
        } catch (\Exception $e) {
            $this->error("  ✗ FAILED: Exception - {$e->getMessage()}");
            $failed++;
        }

        // ===========================================
        // TEST 3: Requesting __generic__ directly works
        // ===========================================
        $this->line("\nTEST 3: Requesting '__generic__' directly returns __generic__");
        try {
            $template = $service->getGenericTemplate('v1', 'tr');
            if ($template->position_code === InterviewTemplateService::GENERIC_POSITION_CODE) {
                $this->info("  ✓ PASSED: position_code = '{$template->position_code}'");
                $passed++;
            } else {
                $this->error("  ✗ FAILED: Expected '__generic__', got '{$template->position_code}'");
                $failed++;
            }
        } catch (\Exception $e) {
            $this->error("  ✗ FAILED: Exception - {$e->getMessage()}");
            $failed++;
        }

        // ===========================================
        // TEST 4: template_json is a string (not decoded)
        // ===========================================
        $this->line("\nTEST 4: template_json is a string (exact storage)");
        try {
            $template = $service->getTemplate('v1', 'tr', 'retail_cashier');
            $rawJson = $template->template_json;

            if (is_string($rawJson)) {
                $this->info("  ✓ PASSED: template_json is string type");
                $passed++;
            } else {
                $this->error("  ✗ FAILED: template_json is NOT a string, got: " . gettype($rawJson));
                $failed++;
            }
        } catch (\Exception $e) {
            $this->error("  ✗ FAILED: Exception - {$e->getMessage()}");
            $failed++;
        }

        // ===========================================
        // TEST 5: template_json matches DB exactly (no whitespace/key reorder)
        // ===========================================
        $this->line("\nTEST 5: template_json matches DB exactly (no whitespace/key reorder)");
        try {
            $template = $service->getTemplate('v1', 'tr', 'retail_cashier');
            $serviceJson = $template->template_json;

            // Get directly from DB
            $dbRow = DB::table('interview_templates')
                ->where('id', $template->id)
                ->first();
            $dbJson = $dbRow->template_json;

            if ($serviceJson === $dbJson) {
                $this->info("  ✓ PASSED: template_json matches DB exactly");
                $this->info("    - Service length: " . strlen($serviceJson) . " bytes");
                $this->info("    - DB length: " . strlen($dbJson) . " bytes");
                $passed++;
            } else {
                $this->error("  ✗ FAILED: template_json does NOT match DB exactly");
                $this->error("    - Service length: " . strlen($serviceJson) . " bytes");
                $this->error("    - DB length: " . strlen($dbJson) . " bytes");
                $failed++;
            }
        } catch (\Exception $e) {
            $this->error("  ✗ FAILED: Exception - {$e->getMessage()}");
            $failed++;
        }

        // ===========================================
        // TEST 6: JSON is valid and has expected keys
        // ===========================================
        $this->line("\nTEST 6: JSON is valid and has expected top-level keys");
        try {
            $template = $service->getTemplate('v1', 'tr', '__generic__');
            $decoded = json_decode($template->template_json, true);

            if ($decoded === null) {
                $this->error("  ✗ FAILED: Invalid JSON - " . json_last_error_msg());
                $failed++;
            } else {
                $expectedKeys = ['version', 'language', 'generic_template', 'positions'];
                $actualKeys = array_keys($decoded);
                $missingKeys = array_diff($expectedKeys, $actualKeys);

                if (empty($missingKeys)) {
                    $this->info("  ✓ PASSED: JSON valid with keys: " . implode(', ', $actualKeys));
                    $passed++;
                } else {
                    $this->error("  ✗ FAILED: Missing keys: " . implode(', ', $missingKeys));
                    $failed++;
                }
            }
        } catch (\Exception $e) {
            $this->error("  ✗ FAILED: Exception - {$e->getMessage()}");
            $failed++;
        }

        // ===========================================
        // TEST 7: Model accessor 'template' vs 'template_json'
        // ===========================================
        $this->line("\nTEST 7: Model accessor 'template' (array) vs 'template_json' (string)");
        try {
            $template = $service->getTemplate('v1', 'tr', 'retail_cashier');

            $templateJsonType = gettype($template->template_json);
            $templateType = gettype($template->template); // Uses accessor

            if ($templateJsonType === 'string' && $templateType === 'array') {
                $this->info("  ✓ PASSED: template_json is string, template accessor is array");
                $passed++;
            } else {
                $this->error("  ✗ FAILED: template_json type={$templateJsonType}, template type={$templateType}");
                $failed++;
            }
        } catch (\Exception $e) {
            $this->error("  ✗ FAILED: Exception - {$e->getMessage()}");
            $failed++;
        }

        // ===========================================
        // SUMMARY
        // ===========================================
        $this->line("\n" . str_repeat('═', 80));
        $total = $passed + $failed;
        $this->info("TEST SUMMARY: {$passed}/{$total} passed");

        if ($failed > 0) {
            $this->error("{$failed} test(s) FAILED");
            return Command::FAILURE;
        }

        $this->info("All tests PASSED!");
        return Command::SUCCESS;
    }
}
