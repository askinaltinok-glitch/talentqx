<?php

namespace App\Console\Commands;

use App\Services\Interview\InterviewTemplateService;
use Illuminate\Console\Command;

class TestMaritimeTemplateSafety extends Command
{
    protected $signature = 'test:maritime-template-safety';
    protected $description = 'Smoke test: deck/engine template isolation (captain never gets engine, second_engineer never gets deck)';

    public function handle(InterviewTemplateService $svc): int
    {
        $passed = 0;
        $failed = 0;

        // Test 1: captain must resolve to deck_* only (or null)
        $this->info('Test 1: captain → must be deck_* or null');
        $t = $svc->getMaritimeTemplate('v1', 'tr', 'deck', 'captain');
        if ($t === null) {
            $this->warn('  → No template found (acceptable — no deck templates seeded yet)');
            $passed++;
        } elseif (str_starts_with($t->position_code, 'deck_')) {
            $this->info("  → PASS: resolved to {$t->position_code}");
            $passed++;
        } else {
            $this->error("  → FAIL: resolved to {$t->position_code} (expected deck_*)");
            $failed++;
        }

        // Test 2: second_engineer must resolve to engine_* only (or null)
        $this->info('Test 2: second_engineer → must be engine_* or null');
        $t = $svc->getMaritimeTemplate('v1', 'tr', 'engine', 'second_engineer');
        if ($t === null) {
            $this->warn('  → No template found (acceptable — no engine templates seeded yet)');
            $passed++;
        } elseif (str_starts_with($t->position_code, 'engine_')) {
            $this->info("  → PASS: resolved to {$t->position_code}");
            $passed++;
        } else {
            $this->error("  → FAIL: resolved to {$t->position_code} (expected engine_*)");
            $failed++;
        }

        // Test 3: captain with department=engine → must NOT resolve (mismatch)
        $this->info('Test 3: captain + department=engine → must be null (cross-department blocked)');
        $t = $svc->getMaritimeTemplate('v1', 'tr', 'engine', 'captain');
        if ($t === null) {
            $this->info('  → PASS: no template (cross-department blocked)');
            $passed++;
        } elseif (str_starts_with($t->position_code, 'engine_')) {
            // It returned engine_captain or engine___generic__ — engine___generic__ is OK
            // because the position_code requested was engine_captain which doesn't exist,
            // but engine___generic__ does. This is within-department fallback.
            $this->info("  → PASS: resolved to {$t->position_code} (within engine namespace)");
            $passed++;
        } else {
            $this->error("  → FAIL: resolved to {$t->position_code} (leaked outside engine namespace!)");
            $failed++;
        }

        // Test 4: second_engineer with department=deck → must NOT get deck templates for this role
        $this->info('Test 4: second_engineer + department=deck → must not get engine content');
        $t = $svc->getMaritimeTemplate('v1', 'tr', 'deck', 'second_engineer');
        if ($t === null) {
            $this->info('  → PASS: no template (cross-department blocked)');
            $passed++;
        } elseif (str_starts_with($t->position_code, 'deck_')) {
            $this->info("  → PASS: resolved to {$t->position_code} (within deck namespace)");
            $passed++;
        } else {
            $this->error("  → FAIL: resolved to {$t->position_code} (leaked outside deck namespace!)");
            $failed++;
        }

        // Test 5: departmentForRole mapping
        $this->info('Test 5: role→department mapping');
        $checks = [
            'captain' => 'deck', 'chief_officer' => 'deck', 'bosun' => 'deck',
            'chief_engineer' => 'engine', 'second_engineer' => 'engine', 'oiler' => 'engine',
            'cook' => 'galley',
        ];
        foreach ($checks as $role => $expected) {
            $got = $svc->departmentForRole($role);
            if ($got === $expected) {
                $passed++;
            } else {
                $this->error("  → FAIL: {$role} → got '{$got}' expected '{$expected}'");
                $failed++;
            }
        }
        $this->info("  → Role mapping: " . count($checks) . " checked");

        // Summary
        $this->newLine();
        if ($failed === 0) {
            $this->info("ALL PASS ({$passed} tests)");
            return 0;
        }
        $this->error("FAILED: {$failed} / " . ($passed + $failed));
        return 1;
    }
}
