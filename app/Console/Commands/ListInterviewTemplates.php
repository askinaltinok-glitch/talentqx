<?php

namespace App\Console\Commands;

use App\Services\Interview\InterviewTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ListInterviewTemplates extends Command
{
    protected $signature = 'interview-templates:list {--test-fallback : Test fallback retrieval}';
    protected $description = 'List all interview templates in the database';

    public function handle(InterviewTemplateService $templateService): int
    {
        $templates = DB::table('interview_templates')
            ->select('id', 'version', 'language', 'position_code', 'title', 'is_active', 'created_at', 'updated_at')
            ->orderBy('is_active', 'desc')
            ->orderByRaw("position_code = '__generic__' DESC")
            ->orderBy('position_code')
            ->get();

        echo "\n╔═══════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                      INTERVIEW TEMPLATES DATABASE                             ║\n";
        echo "║  Standard: generic = __generic__ (NOT NULL, unique per version+language)     ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n\n";

        echo sprintf("%-38s | %-7s | %-5s | %-20s | %-40s | %-6s\n",
            'ID', 'VERSION', 'LANG', 'POSITION_CODE', 'TITLE', 'ACTIVE');
        echo str_repeat('─', 130) . "\n";

        foreach ($templates as $row) {
            $posCode = $row->position_code;
            $isGeneric = $posCode === '__generic__';
            echo sprintf("%-38s | %-7s | %-5s | %-20s | %-40s | %-6s\n",
                $row->id,
                $row->version,
                $row->language,
                $isGeneric ? $posCode . ' (SYSTEM)' : $posCode,
                substr($row->title ?? '', 0, 40),
                $row->is_active ? 'YES' : 'NO'
            );
        }

        echo str_repeat('─', 130) . "\n";
        echo "\nSUMMARY:\n";
        echo "  Total rows:    " . count($templates) . "\n";
        echo "  Active rows:   " . $templates->where('is_active', true)->count() . "\n";
        echo "  Inactive rows: " . $templates->where('is_active', false)->count() . "\n";

        // Show JSON size for active templates
        echo "\nJSON STORAGE VERIFICATION (active templates):\n";
        foreach ($templates->where('is_active', true) as $row) {
            $fullRow = DB::table('interview_templates')->where('id', $row->id)->first();
            $jsonLength = strlen($fullRow->template_json);
            $decoded = json_decode($fullRow->template_json, true);
            $isValid = $decoded !== null ? 'VALID' : 'INVALID';

            echo sprintf("  %-20s: %d bytes, JSON: %s\n",
                $row->position_code,
                $jsonLength,
                $isValid
            );

            // Show top-level keys
            if ($decoded) {
                echo "    Top-level keys: " . implode(', ', array_keys($decoded)) . "\n";
            }
        }

        // Test fallback retrieval if requested
        if ($this->option('test-fallback')) {
            echo "\n" . str_repeat('═', 80) . "\n";
            echo "FALLBACK RETRIEVAL TEST:\n";
            echo str_repeat('─', 80) . "\n";

            $testCases = [
                ['v1', 'tr', 'retail_cashier'],      // Should find retail_cashier
                ['v1', 'tr', 'nonexistent_pos'],     // Should fallback to __generic__
                ['v1', 'tr', '__generic__'],         // Should find __generic__ directly
            ];

            foreach ($testCases as [$version, $language, $positionCode]) {
                try {
                    $result = $templateService->getTemplate($version, $language, $positionCode);
                    $fallback = $result->position_code !== $positionCode ? ' (FALLBACK)' : '';
                    echo sprintf("  getTemplate('%s', '%s', '%s') -> %s%s\n",
                        $version, $language, $positionCode,
                        $result->position_code,
                        $fallback
                    );
                } catch (\Exception $e) {
                    echo sprintf("  getTemplate('%s', '%s', '%s') -> ERROR: %s\n",
                        $version, $language, $positionCode,
                        $e->getMessage()
                    );
                }
            }
        }

        return Command::SUCCESS;
    }
}
