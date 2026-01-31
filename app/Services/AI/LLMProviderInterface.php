<?php

namespace App\Services\AI;

interface LLMProviderInterface
{
    public function generateQuestions(array $competencies, array $questionRules, array $context = []): array;

    public function analyzeInterview(array $responses, array $competencies, array $redFlags): array;

    public function transcribeAudio(string $audioPath, string $language = 'tr'): array;
}
