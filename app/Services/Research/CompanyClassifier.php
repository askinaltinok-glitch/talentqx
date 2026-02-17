<?php

namespace App\Services\Research;

use App\Models\ResearchCompany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CompanyClassifier
{
    /**
     * Classify a research company using GPT-4o-mini.
     * Returns structured classification data.
     */
    public function classify(ResearchCompany $company): array
    {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            Log::warning('CompanyClassifier: No OpenAI API key configured');
            return $this->fallbackClassification($company);
        }

        $prompt = $this->buildPrompt($company);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 500,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                Log::warning('CompanyClassifier: API call failed', [
                    'status' => $response->status(),
                    'company_id' => $company->id,
                ]);
                return $this->fallbackClassification($company);
            }

            $content = $response->json('choices.0.message.content');
            $parsed = json_decode($content, true);

            if (!$parsed || !isset($parsed['is_maritime'])) {
                Log::warning('CompanyClassifier: Invalid response format', ['company_id' => $company->id]);
                return $this->fallbackClassification($company);
            }

            return [
                'is_maritime' => (bool) ($parsed['is_maritime'] ?? false),
                'company_type' => $parsed['company_type'] ?? null,
                'confidence' => min(100, max(0, (int) ($parsed['confidence'] ?? 50))),
                'tags' => $parsed['tags'] ?? [],
                'reasoning' => $parsed['reasoning'] ?? '',
                'sub_industry' => $parsed['sub_industry'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('CompanyClassifier: Exception', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            return $this->fallbackClassification($company);
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a company classification engine. Given company information, output STRICT JSON with these fields:
{
  "is_maritime": boolean,
  "company_type": string (one of: ship_manager, ship_owner, agency, charterer, manning_agent, training_center, offshore_operator, tanker_operator, logistics, retail, factory, tech, consulting, other),
  "confidence": integer 0-100,
  "tags": string[] (relevant keywords like "bulk carrier", "crew management", "offshore wind"),
  "reasoning": string (1-2 sentences why),
  "sub_industry": string or null (e.g. "container shipping", "offshore oil", "cruise lines")
}

Maritime indicators: ships, vessels, fleet, crew, seafarer, IMO, STCW, manning, charterer, bulk carrier, tanker, offshore, maritime, shipping, port, dock, marine, naval.
PROMPT;
    }

    private function buildPrompt(ResearchCompany $company): string
    {
        $parts = ["Company: {$company->name}"];

        if ($company->domain) {
            $parts[] = "Domain: {$company->domain}";
        }
        if ($company->country) {
            $parts[] = "Country: {$company->country}";
        }
        if ($company->description) {
            $parts[] = "Description: " . mb_substr($company->description, 0, 500);
        }
        if ($company->website) {
            $parts[] = "Website: {$company->website}";
        }
        if ($company->employee_count_est) {
            $parts[] = "Est. employees: {$company->employee_count_est}";
        }
        if ($company->source_meta) {
            $meta = json_encode($company->source_meta);
            if (strlen($meta) < 500) {
                $parts[] = "Source data: {$meta}";
            }
        }

        return implode("\n", $parts);
    }

    private function fallbackClassification(ResearchCompany $company): array
    {
        $name = strtolower($company->name);
        $domain = strtolower($company->domain ?? '');
        $desc = strtolower($company->description ?? '');
        $combined = "{$name} {$domain} {$desc}";

        $maritimeKeywords = ['ship', 'maritime', 'marine', 'vessel', 'crew', 'manning', 'offshore', 'tanker', 'bulk', 'cargo', 'port', 'dock', 'seafar', 'naval', 'fleet'];

        $isMaritime = false;
        foreach ($maritimeKeywords as $kw) {
            if (str_contains($combined, $kw)) {
                $isMaritime = true;
                break;
            }
        }

        return [
            'is_maritime' => $isMaritime,
            'company_type' => 'other',
            'confidence' => 30,
            'tags' => [],
            'reasoning' => 'Keyword-based fallback classification (no AI available)',
            'sub_industry' => null,
        ];
    }
}
