<?php

namespace App\Jobs;

use App\Models\ResearchRun;
use App\Services\Research\DomainEnrichmentAgent;
use App\Services\Research\HiringSignalAgent;
use App\Services\Research\LeadGeneratorAgent;
use App\Services\Research\MaritimeDiscoveryAgent;
use App\Services\Research\ResearchAgentInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunResearchAgentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [60, 300];
    public int $timeout = 600;

    public function __construct(
        public string $agentName,
        public ?array $meta = null,
    ) {}

    public function handle(): void
    {
        $run = ResearchRun::create([
            'agent_name' => $this->agentName,
            'status' => ResearchRun::STATUS_PENDING,
            'meta' => $this->meta,
        ]);

        $agent = $this->resolveAgent($this->agentName);

        if (!$agent) {
            $run->fail("Unknown agent: {$this->agentName}");
            return;
        }

        try {
            $run->start();
            $agent->run($run);
            $run->refresh();
            $run->complete(
                (int) $run->companies_found,
                (int) $run->signals_detected,
                (int) $run->leads_created,
            );

            Log::info("Research agent completed", [
                'agent' => $this->agentName,
                'run_id' => $run->id,
            ]);
        } catch (\Exception $e) {
            $run->fail($e->getMessage());
            Log::error("Research agent failed", [
                'agent' => $this->agentName,
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function resolveAgent(string $name): ?ResearchAgentInterface
    {
        return match ($name) {
            ResearchRun::AGENT_HIRING_SIGNAL => app(HiringSignalAgent::class),
            ResearchRun::AGENT_MARITIME_DISCOVERY => app(MaritimeDiscoveryAgent::class),
            ResearchRun::AGENT_DOMAIN_ENRICHMENT => app(DomainEnrichmentAgent::class),
            ResearchRun::AGENT_LEAD_GENERATOR => app(LeadGeneratorAgent::class),
            default => null,
        };
    }
}
