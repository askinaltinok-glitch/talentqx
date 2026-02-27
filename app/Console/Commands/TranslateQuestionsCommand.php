<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslateQuestionsCommand extends Command
{
    protected $signature = 'questions:translate
        {--lang=de : Target language code (en, de, fr, ar)}
        {--database=mysql_talentqx : Database connection}
        {--batch-size=10 : Number of questions per API call}
        {--dry-run : Preview without writing to DB}';

    protected $description = 'Translate position questions to target language using OpenAI';

    private const SUPPORTED_LANGS = ['en', 'de', 'fr', 'ar'];

    private const LANG_NAMES = [
        'en' => 'English',
        'de' => 'German',
        'fr' => 'French',
        'ar' => 'Arabic',
    ];

    public function handle(): int
    {
        $lang = $this->option('lang');
        $database = $this->option('database');
        $batchSize = (int) $this->option('batch-size');
        $dryRun = $this->option('dry-run');

        if (!in_array($lang, self::SUPPORTED_LANGS)) {
            $this->error("Unsupported language: {$lang}. Supported: " . implode(', ', self::SUPPORTED_LANGS));
            return Command::FAILURE;
        }

        $apiKey = config('services.openai.api_key');
        if (empty($apiKey)) {
            $this->error('OpenAI API key is not configured in config/services.php');
            return Command::FAILURE;
        }

        $langName = self::LANG_NAMES[$lang];
        $questionCol = "question_{$lang}";
        $followUpCol = "follow_up_{$lang}";

        $this->info("Translating questions to {$langName} ({$lang})...");
        $this->info("Database: {$database}");

        // Fetch untranslated questions
        $questions = DB::connection($database)
            ->table('position_questions')
            ->whereNotNull('question_tr')
            ->whereNull($questionCol)
            ->where('is_active', true)
            ->select('id', 'question_tr', 'question_en', 'follow_up_tr', 'follow_up_en')
            ->get();

        if ($questions->isEmpty()) {
            $this->info("No untranslated questions found for {$langName}.");
            return Command::SUCCESS;
        }

        $this->info("Found {$questions->count()} questions to translate.");

        $batches = $questions->chunk($batchSize);
        $translated = 0;
        $failed = 0;

        foreach ($batches as $batchIndex => $batch) {
            $this->info("Processing batch " . ($batchIndex + 1) . "/" . $batches->count() . "...");

            try {
                $results = $this->translateBatch($batch->values()->all(), $lang, $langName, $apiKey);

                foreach ($results as $result) {
                    if ($dryRun) {
                        $this->line("  [{$result['id']}] {$result['question']}");
                        $translated++;
                        continue;
                    }

                    $updateData = [$questionCol => $result['question']];
                    if (!empty($result['follow_up'])) {
                        $updateData[$followUpCol] = $result['follow_up'];
                    }

                    DB::connection($database)
                        ->table('position_questions')
                        ->where('id', $result['id'])
                        ->update($updateData);

                    $translated++;
                }

                $this->info("  Batch " . ($batchIndex + 1) . " OK ({$batch->count()} questions)");
            } catch (\Exception $e) {
                $this->error("Batch " . ($batchIndex + 1) . " failed: " . $e->getMessage());
                Log::error('Question translation batch failed', [
                    'lang' => $lang,
                    'batch' => $batchIndex + 1,
                    'error' => $e->getMessage(),
                ]);
                $failed += $batch->count();
            }

            // Rate limit: pause between batches
            if ($batchIndex < $batches->count() - 1) {
                usleep(500000); // 500ms
            }
        }

        $this->info("Translation complete: {$translated} translated, {$failed} failed.");

        if ($dryRun) {
            $this->warn("Dry run — no changes written to database.");
        }

        return Command::SUCCESS;
    }

    private function translateBatch(array $questions, string $lang, string $langName, string $apiKey): array
    {
        $questionsForPrompt = [];
        foreach ($questions as $q) {
            $questionsForPrompt[] = [
                'id' => $q->id,
                'question' => $q->question_tr ?? $q->question_en,
                'follow_up' => $q->follow_up_tr ?? $q->follow_up_en,
            ];
        }

        $prompt = "Translate the following HR interview questions from Turkish to {$langName}.\n"
            . "Keep the professional tone and interview context.\n"
            . "Return ONLY valid JSON with the same structure.\n\n"
            . "Input:\n" . json_encode($questionsForPrompt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a professional HR translator. Translate interview questions accurately to {$langName}. "
                        . "Maintain professional HR terminology. Return only a valid JSON object with a \"translations\" key containing an array with fields: id, question, follow_up.",
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->status() . ' ' . $response->body());
        }

        $content = $response->json('choices.0.message.content', '');
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response: ' . json_last_error_msg());
        }

        // Handle { "translations": [...] } format
        if (isset($decoded['translations'])) {
            return $decoded['translations'];
        }

        // Handle direct array
        if (isset($decoded[0])) {
            return $decoded;
        }

        // Try to find the array in any key
        foreach ($decoded as $value) {
            if (is_array($value) && isset($value[0])) {
                return $value;
            }
        }

        throw new \RuntimeException('Unexpected response format from OpenAI');
    }
}
