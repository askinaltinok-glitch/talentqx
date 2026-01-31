<?php

namespace App\Services\Interview;

use App\Models\Job;
use App\Models\JobQuestion;
use App\Services\AI\LLMProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuestionGenerator
{
    public function __construct(
        private LLMProviderInterface $llmProvider
    ) {}

    public function generateForJob(Job $job, bool $regenerate = false): array
    {
        if (!$regenerate && $job->questions()->exists()) {
            return $job->questions->toArray();
        }

        $competencies = $job->getEffectiveCompetencies();
        $questionRules = $job->getEffectiveQuestionRules();

        $context = [
            'position_name' => $job->template?->name ?? $job->title,
            'sample_questions' => $job->template?->question_rules['sample_questions'] ?? [],
        ];

        try {
            $generatedQuestions = $this->llmProvider->generateQuestions(
                $competencies,
                $questionRules,
                $context
            );

            return $this->saveQuestions($job, $generatedQuestions['questions'] ?? [], $regenerate);
        } catch (\Exception $e) {
            Log::error('Question generation failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function saveQuestions(Job $job, array $questions, bool $regenerate): array
    {
        return DB::transaction(function () use ($job, $questions, $regenerate) {
            if ($regenerate) {
                $job->questions()->delete();
            }

            $savedQuestions = [];
            $order = 1;

            foreach ($questions as $questionData) {
                $question = JobQuestion::create([
                    'job_id' => $job->id,
                    'question_order' => $order++,
                    'question_type' => $questionData['question_type'],
                    'question_text' => $questionData['question_text'],
                    'competency_code' => $questionData['competency_code'] ?? null,
                    'ideal_answer_points' => $questionData['ideal_answer_points'] ?? [],
                    'time_limit_seconds' => $questionData['time_limit_seconds'] ?? 180,
                ]);

                $savedQuestions[] = $question;
            }

            return $savedQuestions;
        });
    }

    public function generateSampleQuestions(array $competencies, array $questionRules): array
    {
        return $this->llmProvider->generateQuestions($competencies, $questionRules);
    }
}
