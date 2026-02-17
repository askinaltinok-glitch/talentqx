<?php

namespace App\Console\Commands;

use App\Services\DecisionEngine\DecisionEngineAudit;
use Illuminate\Console\Command;

class AuditDecisionEngine extends Command
{
    protected $signature = 'decision-engine:audit';
    protected $description = 'Run full decision engine audit with frozen profiles';

    public function handle(): int
    {
        $audit = new DecisionEngineAudit();
        $audit->runFullAudit();
        return Command::SUCCESS;
    }
}
