<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InterviewQuestionsV2Seeder extends Seeder
{
    public function run(): void
    {
        $competencies = DB::table('competencies')->pluck('id', 'code')->toArray();

        // Delete all existing position_questions
        DB::table('position_questions')->delete();
        $this->command->info('Cleared existing position_questions.');

        // Load all question data files
        $dataFiles = glob(__DIR__ . '/data/questions_*.php');
        $totalPositions = 0;
        $totalQuestions = 0;

        foreach ($dataFiles as $file) {
            $allQuestions = require $file;
            $fileName = basename($file);

            foreach ($allQuestions as $posCode => $questions) {
                $position = DB::table('job_positions')->where('code', $posCode)->first();
                if (!$position) {
                    $this->command->warn("Position not found: {$posCode}");
                    continue;
                }

                foreach ($questions as $i => $q) {
                    // Format: [question_tr, competency_code, question_type, [expected], [red_flags], difficulty]
                    $compId = $competencies[$q[1]] ?? null;
                    if (!$compId) {
                        $this->command->warn("Competency not found: {$q[1]} for {$posCode}");
                        continue;
                    }

                    DB::table('position_questions')->insert([
                        'id' => Str::uuid()->toString(),
                        'position_id' => $position->id,
                        'competency_id' => $compId,
                        'question_type' => $q[2],
                        'question_tr' => $q[0],
                        'question_en' => '',
                        'expected_indicators' => json_encode($q[3], JSON_UNESCAPED_UNICODE),
                        'red_flag_indicators' => json_encode($q[4], JSON_UNESCAPED_UNICODE),
                        'difficulty_level' => $q[5] ?? 2,
                        'time_limit_seconds' => 120,
                        'sort_order' => $i + 1,
                        'is_mandatory' => $i < 4 ? 1 : 0,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $totalQuestions++;
                }
                $totalPositions++;
            }
            $this->command->info("Loaded: {$fileName}");
        }

        $this->command->info("Done! {$totalPositions} positions, {$totalQuestions} questions.");
    }
}
