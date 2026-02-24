<?php

namespace App\Console\Commands\Maritime;

use App\Models\MaritimeRoleDna;
use App\Models\MaritimeRoleRecord;
use App\Services\Maritime\RoleFitEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedRoleRegistryCommand extends Command
{
    protected $signature = 'maritime:seed-role-registry {--ver=v1 : Registry version to seed}';
    protected $description = 'Load role registry and DNA matrix from JSON into database (idempotent)';

    public function handle(): int
    {
        $version = $this->option('ver');

        $registryPath = storage_path("app/role_registry/ROLE_CORE_REGISTRY_{$version}.json");
        $dnaPath = storage_path("app/role_registry/ROLE_DNA_MATRIX_{$version}.json");

        if (!file_exists($registryPath)) {
            $this->error("Registry file not found: {$registryPath}");
            return 1;
        }
        if (!file_exists($dnaPath)) {
            $this->error("DNA matrix file not found: {$dnaPath}");
            return 1;
        }

        $registry = json_decode(file_get_contents($registryPath), true);
        $dna = json_decode(file_get_contents($dnaPath), true);

        if (!$registry || !isset($registry['roles'])) {
            $this->error('Invalid registry JSON structure');
            return 1;
        }
        if (!$dna || !isset($dna['dna'])) {
            $this->error('Invalid DNA JSON structure');
            return 1;
        }

        DB::beginTransaction();

        try {
            // Upsert roles
            $roleCount = 0;
            foreach ($registry['roles'] as $role) {
                MaritimeRoleRecord::updateOrCreate(
                    ['role_key' => $role['canonical_code']],
                    [
                        'label' => $role['label'],
                        'department' => $role['department'],
                        'domain' => 'maritime',
                        'is_active' => true,
                        'is_selectable' => $role['is_selectable'] ?? true,
                        'sort_order' => $role['sort_order'],
                        'meta' => [
                            'registry_code' => $role['role_code'],
                            'category' => $role['category'],
                            'authority_level' => $role['authority_level'],
                            'safety_criticality' => $role['safety_criticality'],
                            'decision_scope' => $role['decision_scope'],
                        ],
                    ]
                );
                $roleCount++;
            }

            // Wiper: canonical alias of oiler, not selectable in apply form.
            // Legacy code and CSV imports may reference "wiper" directly,
            // so it must exist in DB to prevent lookup exceptions.
            MaritimeRoleRecord::updateOrCreate(
                ['role_key' => 'wiper'],
                [
                    'label' => 'Wiper',
                    'department' => 'engine',
                    'domain' => 'maritime',
                    'is_active' => true,
                    'is_selectable' => false,
                    'sort_order' => 62, // after oiler (61)
                    'meta' => [
                        'registry_code' => 'WIPER',
                        'category' => 'execution',
                        'authority_level' => 1,
                        'safety_criticality' => 'low',
                        'decision_scope' => null,
                        'canonical_alias' => 'oiler',
                    ],
                ]
            );
            $roleCount++;

            $this->info("Upserted {$roleCount} maritime roles (incl. wiper alias).");

            // Upsert DNA entries
            $dnaCount = 0;
            foreach ($dna['dna'] as $entry) {
                $roleKey = $entry['canonical_code'];

                // Verify role exists
                if (!MaritimeRoleRecord::find($roleKey)) {
                    $this->warn("Skipping DNA for unknown role_key: {$roleKey}");
                    continue;
                }

                // Delete existing then create (updateOrCreate on composite unique)
                MaritimeRoleDna::where('role_key', $roleKey)
                    ->where('version', $version)
                    ->delete();

                MaritimeRoleDna::create([
                    'role_key' => $roleKey,
                    'dna_dimensions' => $entry['dimensions'],
                    'behavioral_profile' => $entry['behavioral_profile'],
                    'mismatch_signals' => $entry['mismatch_signals'],
                    'integration_rules' => [
                        'scoring' => [
                            'behavioral_weight' => $entry['dimensions']['behavioral_weight'],
                            'technical_weight' => $entry['dimensions']['technical_weight'],
                            'remaining_capacity' => round(1.0 - $entry['dimensions']['behavioral_weight'] - $entry['dimensions']['technical_weight'], 2),
                        ],
                        'interview' => [
                            'include_leadership_scenarios' => $entry['dimensions']['leadership_expectation'] >= 0.40,
                            'include_decision_pressure' => $entry['dimensions']['decision_exposure'] >= 0.40,
                            'include_safety_critical' => $entry['dimensions']['safety_ownership'] >= 0.50,
                            'critical_dimensions' => array_keys(array_filter(
                                $entry['behavioral_profile'],
                                fn($v) => $v === 'critical'
                            )),
                        ],
                        'mismatch' => [
                            'signal_count' => count($entry['mismatch_signals']),
                            'strong_threshold' => 3,
                            'weak_threshold' => 1,
                        ],
                    ],
                    'version' => $version,
                ]);
                $dnaCount++;
            }

            $this->info("Upserted {$dnaCount} DNA entries (version: {$version}).");

            DB::commit();

            // Invalidate caches after successful seeding
            RoleFitEngine::clearCache();
            $this->info('Cache invalidated.');

            $this->info('Role registry seeding complete.');
            return 0;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Seeding failed: {$e->getMessage()}");
            return 1;
        }
    }
}
