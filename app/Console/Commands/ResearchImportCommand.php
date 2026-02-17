<?php

namespace App\Console\Commands;

use App\Services\Research\ResearchService;
use Illuminate\Console\Command;

class ResearchImportCommand extends Command
{
    protected $signature = 'research:import {file : Path to JSON or CSV file} {--source=import : Source label for imported companies}';
    protected $description = 'Import companies from a JSON or CSV file into research_companies';

    public function handle(ResearchService $service): int
    {
        $file = $this->argument('file');
        $source = $this->option('source');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);

        $this->info("Importing from {$file} (source: {$source})...");

        try {
            if ($extension === 'csv') {
                $result = $service->importFromCsv($file, $source);
            } else {
                $json = json_decode(file_get_contents($file), true);
                if (!is_array($json)) {
                    $this->error("Invalid JSON file");
                    return 1;
                }
                // Support both flat array and {data: [...]} format
                $items = isset($json['data']) ? $json['data'] : $json;
                $result = $service->importFromJson($items, $source);
            }

            $this->info("Created: {$result['created']}");
            $this->info("Skipped (duplicate domain): {$result['skipped']}");

            if (!empty($result['errors'])) {
                $this->warn("Errors: " . count($result['errors']));
                foreach (array_slice($result['errors'], 0, 10) as $err) {
                    $this->warn("  [{$err['index']}] {$err['error']}");
                }
            }
        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
