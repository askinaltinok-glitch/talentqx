<?php

namespace App\Services\Report;

class NarrativeTemplateService
{
    private array $config;

    public function __construct()
    {
        $path = config_path('pdf_narrative_templates.json');
        $this->config = json_decode(file_get_contents($path), true);
    }

    /**
     * Select template key based on score
     *
     * Rules:
     *   score >= 75 -> strong_fit
     *   score >= 60 -> potential_fit
     *   score < 60  -> weak_fit
     */
    public function selectTemplate(float $score): string
    {
        foreach ($this->config['rules'] as $key => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $key;
            }
        }
        return 'weak_fit'; // fallback
    }

    /**
     * Get narrative texts for a given score and locale
     */
    public function getNarratives(float $score, string $locale = 'tr', array $placeholders = []): array
    {
        $templateKey = $this->selectTemplate($score);
        $template = $this->config['templates'][$templateKey][$locale]
            ?? $this->config['templates'][$templateKey]['tr']; // fallback to TR

        // Replace placeholders
        $result = [];
        foreach ($template as $key => $text) {
            $result[$key] = $this->replacePlaceholders($text, $placeholders);
        }

        return [
            'template_key' => $templateKey,
            'narratives' => $result,
        ];
    }

    /**
     * Replace placeholders in text
     */
    private function replacePlaceholders(string $text, array $placeholders): string
    {
        foreach ($placeholders as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }

    /**
     * Get all available template keys
     */
    public function getTemplateKeys(): array
    {
        return array_keys($this->config['templates']);
    }

    /**
     * Get rules configuration
     */
    public function getRules(): array
    {
        return $this->config['rules'];
    }
}
