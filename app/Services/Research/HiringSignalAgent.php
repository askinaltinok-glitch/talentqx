<?php

namespace App\Services\Research;

use App\Models\ResearchCompany;
use App\Models\ResearchRun;
use App\Models\ResearchSignal;
use Illuminate\Support\Facades\Log;

class HiringSignalAgent implements ResearchAgentInterface
{
    public function getName(): string
    {
        return 'hiring_signal';
    }

    /**
     * Scan research_companies with raw source_meta for job posting signals.
     * Creates ResearchSignal records and recalculates hiring scores.
     */
    public function run(ResearchRun $run): void
    {
        $companies = ResearchCompany::whereIn('status', [
                ResearchCompany::STATUS_DISCOVERED,
                ResearchCompany::STATUS_ENRICHED,
                ResearchCompany::STATUS_QUALIFIED,
            ])
            ->whereNotNull('source_meta')
            ->orderBy('created_at')
            ->limit(200)
            ->get();

        $signalsDetected = 0;

        foreach ($companies as $company) {
            try {
                $signals = $this->detectSignals($company);

                foreach ($signals as $signal) {
                    // Avoid duplicate signals of same type within 24h
                    $exists = ResearchSignal::where('research_company_id', $company->id)
                        ->where('signal_type', $signal['type'])
                        ->where('detected_at', '>=', now()->subDay())
                        ->exists();

                    if ($exists) continue;

                    ResearchSignal::create([
                        'research_company_id' => $company->id,
                        'signal_type' => $signal['type'],
                        'confidence_score' => $signal['confidence'],
                        'source_url' => $signal['source_url'] ?? null,
                        'raw_data' => $signal,
                        'detected_at' => now(),
                    ]);

                    $signalsDetected++;
                }

                if (!empty($signals)) {
                    $company->recalculateHiringScore();
                }
            } catch (\Exception $e) {
                Log::warning('HiringSignalAgent: Error processing company', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $run->update(['signals_detected' => $signalsDetected]);

        Log::info('HiringSignalAgent completed', [
            'companies_scanned' => $companies->count(),
            'signals_detected' => $signalsDetected,
        ]);
    }

    /**
     * Detect hiring signals from company source_meta and description.
     */
    private function detectSignals(ResearchCompany $company): array
    {
        $signals = [];
        $meta = $company->source_meta ?? [];
        $desc = strtolower($company->description ?? '');
        $name = strtolower($company->name);

        // Check for job posting keywords in source_meta
        $metaJson = strtolower(json_encode($meta));

        $jobKeywords = ['hiring', 'job opening', 'vacancy', 'career', 'recruit', 'position available', 'we are looking', 'join our team'];
        foreach ($jobKeywords as $keyword) {
            if (str_contains($metaJson, $keyword) || str_contains($desc, $keyword)) {
                $signals[] = [
                    'type' => ResearchSignal::TYPE_JOB_POST,
                    'confidence' => 60,
                    'keyword' => $keyword,
                ];
                break;
            }
        }

        // Maritime crew signals
        $crewKeywords = ['crew needed', 'seafarer', 'officer wanted', 'rating needed', 'crew change', 'manning', 'crew vacancy'];
        foreach ($crewKeywords as $keyword) {
            if (str_contains($metaJson, $keyword) || str_contains($desc, $keyword)) {
                $signals[] = [
                    'type' => ResearchSignal::TYPE_MARITIME_CREW,
                    'confidence' => 75,
                    'keyword' => $keyword,
                ];
                break;
            }
        }

        // Expansion signals
        $expansionKeywords = ['expanding', 'new office', 'new vessel', 'fleet growth', 'new branch', 'acquisition'];
        foreach ($expansionKeywords as $keyword) {
            if (str_contains($metaJson, $keyword) || str_contains($desc, $keyword)) {
                $signals[] = [
                    'type' => ResearchSignal::TYPE_EXPANSION,
                    'confidence' => 55,
                    'keyword' => $keyword,
                ];
                break;
            }
        }

        // Career page signal
        if (isset($meta['career_page']) || isset($meta['careers_url'])) {
            $signals[] = [
                'type' => ResearchSignal::TYPE_CAREER_PAGE,
                'confidence' => 50,
                'source_url' => $meta['career_page'] ?? $meta['careers_url'] ?? null,
            ];
        }

        return $signals;
    }
}
