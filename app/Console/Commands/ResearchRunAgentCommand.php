<?php

namespace App\Console\Commands;

use App\Jobs\RunResearchAgentJob;
use App\Models\ResearchRun;
use Illuminate\Console\Command;

class ResearchRunAgentCommand extends Command
{
    protected $signature = 'research:run-agent {agent : Agent name (hiring_signal|maritime_discovery|domain_enrichment|lead_generator)} {--sync : Run synchronously instead of dispatching to queue}';
    protected $description = 'Run a research agent (dispatch to queue or run synchronously)';

    public function handle(): int
    {
        $agent = $this->argument('agent');

        if (!in_array($agent, ResearchRun::AGENTS)) {
            $this->error("Unknown agent: {$agent}. Valid agents: " . implode(', ', ResearchRun::AGENTS));
            return 1;
        }

        if ($this->option('sync')) {
            $this->info("Running {$agent} synchronously...");
            $job = new RunResearchAgentJob($agent);
            $job->handle();
            $this->info("Agent {$agent} completed.");
        } else {
            RunResearchAgentJob::dispatch($agent);
            $this->info("Agent {$agent} dispatched to queue.");
        }

        return 0;
    }
}
