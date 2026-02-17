<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InterviewQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuestionImportController extends Controller
{
    /**
     * Required fields in each question JSON
     */
    private const REQUIRED_FIELDS = ['question', 'type'];

    /**
     * Valid question types
     */
    private const VALID_TYPES = ['behavioral', 'scenario', 'text', 'situational'];

    /**
     * Validate and preview questions from JSON input.
     *
     * POST /api/v1/admin/questions/import/validate
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'json_input' => 'required|string',
            'role_key' => 'required|string|max:64',
            'context_key' => 'nullable|string|max:50',
            'locale' => 'nullable|string|max:10',
        ]);

        $jsonInput = $request->input('json_input');
        $roleKey = $request->input('role_key');
        $contextKey = $request->input('context_key');
        $locale = $request->input('locale', 'tr');

        // Try to parse JSON
        $parsed = $this->parseJsonInput($jsonInput);

        if ($parsed['error']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'JSON_PARSE_ERROR',
                    'message' => $parsed['error'],
                ],
            ], 422);
        }

        $questions = $parsed['questions'];

        // Validate each question
        $validationResults = [];
        $validCount = 0;
        $invalidCount = 0;

        foreach ($questions as $index => $q) {
            $result = $this->validateQuestion($q, $index);
            $validationResults[] = $result;

            if ($result['valid']) {
                $validCount++;
            } else {
                $invalidCount++;
            }
        }

        // Check for existing questions with same role_key
        $existingCount = InterviewQuestion::where('role_key', $roleKey)
            ->where('locale', $locale)
            ->when($contextKey, fn($query) => $query->where('context_key', $contextKey))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_parsed' => count($questions),
                'valid_count' => $validCount,
                'invalid_count' => $invalidCount,
                'existing_questions' => $existingCount,
                'role_key' => $roleKey,
                'context_key' => $contextKey,
                'locale' => $locale,
                'preview' => $validationResults,
            ],
        ]);
    }

    /**
     * Save validated questions to database.
     *
     * POST /api/v1/admin/questions/import/save
     */
    public function save(Request $request): JsonResponse
    {
        $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.type' => 'required|string|in:' . implode(',', self::VALID_TYPES),
            'questions.*.tags' => 'nullable|array',
            'questions.*.expected_competencies' => 'nullable|array',
            'questions.*.time_limit_seconds' => 'nullable|integer|min:60|max:600',
            'role_key' => 'required|string|max:64',
            'context_key' => 'nullable|string|max:50',
            'locale' => 'nullable|string|max:10',
            'replace_existing' => 'nullable|boolean',
        ]);

        $questions = $request->input('questions');
        $roleKey = $request->input('role_key');
        $contextKey = $request->input('context_key');
        $locale = $request->input('locale', 'tr');
        $replaceExisting = $request->input('replace_existing', false);

        DB::beginTransaction();

        try {
            // Optionally delete existing questions
            if ($replaceExisting) {
                InterviewQuestion::where('role_key', $roleKey)
                    ->where('locale', $locale)
                    ->when($contextKey, fn($q) => $q->where('context_key', $contextKey))
                    ->delete();
            }

            // Get starting order number
            $maxOrder = InterviewQuestion::where('role_key', $roleKey)
                ->where('locale', $locale)
                ->max('order_no') ?? 0;

            $created = [];

            foreach ($questions as $index => $q) {
                $meta = [
                    'tags' => $q['tags'] ?? [],
                    'expected_competencies' => $q['expected_competencies'] ?? [],
                    'time_limit_seconds' => $q['time_limit_seconds'] ?? 180,
                ];

                $question = InterviewQuestion::create([
                    'role_key' => $roleKey,
                    'context_key' => $contextKey,
                    'locale' => $locale,
                    'type' => $q['type'],
                    'prompt' => $q['question'],
                    'order_no' => $maxOrder + $index + 1,
                    'is_active' => true,
                    'meta' => $meta,
                ]);

                $created[] = [
                    'id' => $question->id,
                    'type' => $question->type,
                    'prompt' => mb_substr($question->prompt, 0, 80) . '...',
                    'order_no' => $question->order_no,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'created_count' => count($created),
                    'role_key' => $roleKey,
                    'questions' => $created,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SAVE_ERROR',
                    'message' => 'Sorular kaydedilemedi: ' . $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Get existing role keys for dropdown
     *
     * GET /api/v1/admin/questions/roles
     */
    public function roles(): JsonResponse
    {
        $roles = InterviewQuestion::select('role_key')
            ->distinct()
            ->orderBy('role_key')
            ->pluck('role_key');

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Parse JSON input - handles both array and individual objects
     */
    private function parseJsonInput(string $input): array
    {
        $input = trim($input);

        // Try direct JSON parse first
        $decoded = json_decode($input, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // If it's a single object, wrap in array
            if (isset($decoded['question'])) {
                return ['questions' => [$decoded], 'error' => null];
            }
            // If it's already an array
            if (is_array($decoded) && isset($decoded[0])) {
                return ['questions' => $decoded, 'error' => null];
            }
        }

        // Try to extract multiple JSON objects (Claude sometimes outputs them separately)
        $questions = [];
        preg_match_all('/\{[^{}]*"question"[^{}]*\}/s', $input, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $jsonStr) {
                $obj = json_decode($jsonStr, true);
                if ($obj && isset($obj['question'])) {
                    $questions[] = $obj;
                }
            }
        }

        if (!empty($questions)) {
            return ['questions' => $questions, 'error' => null];
        }

        return [
            'questions' => [],
            'error' => 'JSON parse hatası. Lütfen geçerli JSON formatı kullanın.',
        ];
    }

    /**
     * Validate a single question object
     */
    private function validateQuestion(array $q, int $index): array
    {
        $errors = [];
        $warnings = [];

        // Check required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($q[$field])) {
                $errors[] = "'{$field}' alanı zorunlu";
            }
        }

        // Validate type
        if (!empty($q['type']) && !in_array($q['type'], self::VALID_TYPES)) {
            $errors[] = "Geçersiz type: '{$q['type']}'. İzin verilenler: " . implode(', ', self::VALID_TYPES);
        }

        // Validate question length
        if (!empty($q['question'])) {
            $len = mb_strlen($q['question']);
            if ($len < 20) {
                $warnings[] = 'Soru çok kısa (min 20 karakter önerilir)';
            }
            if ($len > 1000) {
                $warnings[] = 'Soru çok uzun (max 1000 karakter önerilir)';
            }
        }

        // Validate time_limit
        if (isset($q['time_limit_seconds'])) {
            $time = (int) $q['time_limit_seconds'];
            if ($time < 60 || $time > 600) {
                $warnings[] = "time_limit_seconds 60-600 arasında olmalı (şu an: {$time})";
            }
        }

        // Check tags/competencies are arrays
        if (isset($q['tags']) && !is_array($q['tags'])) {
            $errors[] = "'tags' array olmalı";
        }
        if (isset($q['expected_competencies']) && !is_array($q['expected_competencies'])) {
            $errors[] = "'expected_competencies' array olmalı";
        }

        return [
            'index' => $index + 1,
            'valid' => empty($errors),
            'question_preview' => mb_substr($q['question'] ?? '', 0, 100) . (mb_strlen($q['question'] ?? '') > 100 ? '...' : ''),
            'type' => $q['type'] ?? null,
            'tags' => $q['tags'] ?? [],
            'competencies' => $q['expected_competencies'] ?? [],
            'time_limit' => $q['time_limit_seconds'] ?? 180,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
