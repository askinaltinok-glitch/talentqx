<?php

namespace App\Services\AI;

interface LLMProviderInterface
{
    public function generateQuestions(array $competencies, array $questionRules, array $context = []): array;

    public function analyzeInterview(array $responses, array $competencies, array $redFlags, array $culturalContext = []): array;

    public function analyzeFormInterview(array $responses, array $competencies, array $culturalContext = []): array;

    public function transcribeAudio(string $audioPath, string $language = 'tr'): array;

    public function improveJobDescription(string $title, string $description, string $positionType = ''): array;

    public function getModelInfo(): array;
}
