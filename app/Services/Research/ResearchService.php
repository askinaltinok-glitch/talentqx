<?php

namespace App\Services\Research;

use App\Jobs\ClassifyCompanyJob;
use App\Models\ResearchCompany;
use App\Models\ResearchSignal;
use App\Models\ResearchRun;
use App\Models\ResearchJob;
use App\Models\ResearchCompanyCandidate;
use App\Models\CrmCompany;
use Illuminate\Support\Facades\Log;

class ResearchService
{
    /**
     * Import companies from a JSON array.
     * Each item: {name, domain?, country?, industry?, description?, website?, linkedin_url?, source_meta?}
     */
    public function importFromJson(array $items, string $source = 'import'): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($items as $i => $item) {
            try {
                if (empty($item['name'])) {
                    $errors[] = ['index' => $i, 'error' => 'Missing name'];
                    continue;
                }

                $domain = $this->normalizeDomain($item['domain'] ?? $item['website'] ?? null);

                // Skip if domain exists
                if ($domain && ResearchCompany::findByDomain($domain)) {
                    $skipped++;
                    continue;
                }

                ResearchCompany::create([
                    'name' => $item['name'],
                    'domain' => $domain,
                    'country' => strtoupper($item['country'] ?? 'XX'),
                    'industry' => $item['industry'] ?? 'general',
                    'description' => $item['description'] ?? null,
                    'website' => $item['website'] ?? ($domain ? "https://{$domain}" : null),
                    'linkedin_url' => $item['linkedin_url'] ?? null,
                    'employee_count_est' => $item['employee_count_est'] ?? null,
                    'fleet_size_est' => $item['fleet_size_est'] ?? null,
                    'source' => $source,
                    'source_meta' => $item['source_meta'] ?? $item,
                    'status' => ResearchCompany::STATUS_DISCOVERED,
                    'discovered_at' => now(),
                ]);

                $created++;
            } catch (\Exception $e) {
                $errors[] = ['index' => $i, 'error' => $e->getMessage()];
            }
        }

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Import from CSV file path.
     */
    public function importFromCsv(string $path, string $source = 'import'): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException("Cannot open CSV: {$path}");
        }

        $headers = fgetcsv($handle);
        $items = [];

        while (($row = fgetcsv($handle)) !== false) {
            $item = array_combine($headers, $row);
            if ($item) {
                $items[] = $item;
            }
        }

        fclose($handle);

        return $this->importFromJson($items, $source);
    }

    /**
     * Enrich from a domain (e.g. from unmatched inbound email).
     */
    public function enrichFromDomain(string $domain, array $context = []): ResearchCompany
    {
        $normalized = $this->normalizeDomain($domain);

        $company = ResearchCompany::findOrCreateByDomain($normalized, [
            'source' => ResearchCompany::SOURCE_EMAIL_DOMAIN,
            'source_meta' => $context,
            'country' => $context['country'] ?? null,
        ]);

        return $company;
    }

    /**
     * Dispatch classification job for a company.
     */
    public function classifyCompany(ResearchCompany $company): void
    {
        ClassifyCompanyJob::dispatch($company);
    }

    /**
     * Add a hiring signal to a company and recalculate score.
     */
    public function addSignal(ResearchCompany $company, string $type, array $data): ResearchSignal
    {
        $signal = ResearchSignal::create([
            'research_company_id' => $company->id,
            'signal_type' => $type,
            'confidence_score' => $data['confidence_score'] ?? 50,
            'source_url' => $data['source_url'] ?? null,
            'raw_data' => $data,
            'detected_at' => now(),
        ]);

        $company->recalculateHiringScore();

        return $signal;
    }

    /**
     * Push a research company to CRM.
     */
    public function pushToCrm(ResearchCompany $company, ?string $userId = null): ?CrmCompany
    {
        return $company->pushToCrm($userId);
    }

    /**
     * Get combined stats (Sprint-6 + Sprint-7).
     */
    public function getStats(): array
    {
        return [
            // Sprint-6 stats
            'total_jobs' => ResearchJob::count(),
            'running_jobs' => ResearchJob::status('running')->count(),
            'completed_jobs' => ResearchJob::status('completed')->count(),
            'total_candidates' => ResearchCompanyCandidate::count(),
            'pending_candidates' => ResearchCompanyCandidate::pending()->count(),
            'accepted_candidates' => ResearchCompanyCandidate::where('status', 'accepted')->count(),

            // Sprint-7 stats
            'research_companies' => ResearchCompany::count(),
            'discovered' => ResearchCompany::status('discovered')->count(),
            'enriched' => ResearchCompany::status('enriched')->count(),
            'qualified' => ResearchCompany::status('qualified')->count(),
            'pushed' => ResearchCompany::status('pushed')->count(),
            'ignored' => ResearchCompany::status('ignored')->count(),
            'maritime_companies' => ResearchCompany::maritime()->count(),
            'signals_total' => ResearchSignal::count(),
            'signals_7d' => ResearchSignal::recent(7)->count(),
            'avg_hiring_score' => (int) round(ResearchCompany::whereNotIn('status', ['ignored', 'pushed'])->avg('hiring_signal_score') ?? 0),
            'agent_runs' => ResearchRun::count(),
            'agent_runs_today' => ResearchRun::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Normalize domain from URL or email.
     */
    private function normalizeDomain(?string $input): ?string
    {
        if (!$input) return null;

        // If it looks like an email, extract domain
        if (str_contains($input, '@')) {
            $input = substr($input, strpos($input, '@') + 1);
        }

        // If it looks like a URL, extract host
        if (str_starts_with($input, 'http')) {
            $host = parse_url($input, PHP_URL_HOST);
            if ($host) $input = $host;
        }

        $input = preg_replace('/^www\./', '', strtolower(trim($input)));

        // Skip free email providers
        $freeProviders = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'mail.ru', 'yandex.ru', 'yandex.com', 'icloud.com', 'aol.com', 'protonmail.com'];
        if (in_array($input, $freeProviders)) {
            return null;
        }

        return $input ?: null;
    }
}
