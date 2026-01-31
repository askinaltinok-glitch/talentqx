<?php

namespace App\Services\Interview;

use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use Illuminate\Support\Collection;

class ContextQuestionService
{
    /**
     * Get questions for an interview session
     * Returns shared questions + context-specific questions
     */
    public function getQuestionsForSession(InterviewSession $session): Collection
    {
        $roleKey = $session->role_key;
        $contextKey = $session->context_key;
        $locale = $session->locale;

        // Get shared questions (no context)
        $sharedQuestions = InterviewQuestion::getSharedQuestions($roleKey, $locale);

        // Get context-specific questions if context is set
        $contextQuestions = collect();
        if ($contextKey) {
            $contextQuestions = InterviewQuestion::getContextQuestions($roleKey, $contextKey, $locale);
        }

        // Merge and sort by order
        return $sharedQuestions->merge($contextQuestions)->sortBy('order')->values();
    }

    /**
     * Get question count for a role/context
     */
    public function getQuestionCount(string $roleKey, ?string $contextKey, string $locale = 'tr'): int
    {
        $shared = InterviewQuestion::where('role_key', $roleKey)
            ->where('locale', $locale)
            ->whereNull('context_key')
            ->where('is_active', true)
            ->count();

        $contextSpecific = 0;
        if ($contextKey) {
            $contextSpecific = InterviewQuestion::where('role_key', $roleKey)
                ->where('context_key', $contextKey)
                ->where('locale', $locale)
                ->where('is_active', true)
                ->count();
        }

        return $shared + $contextSpecific;
    }

    /**
     * Validate that all required questions have been answered
     */
    public function validateAnswers(InterviewSession $session): array
    {
        $questions = $this->getQuestionsForSession($session);
        $answeredIds = $session->answers->pluck('question_id')->toArray();

        $missing = $questions->filter(fn($q) => !in_array($q->id, $answeredIds));

        return [
            'complete' => $missing->isEmpty(),
            'total' => $questions->count(),
            'answered' => count($answeredIds),
            'missing' => $missing->pluck('question_key')->toArray(),
        ];
    }
}
