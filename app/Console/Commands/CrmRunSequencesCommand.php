<?php

namespace App\Console\Commands;

use App\Services\Mail\SequenceService;
use Illuminate\Console\Command;

class CrmRunSequencesCommand extends Command
{
    protected $signature = 'crm:run-sequences';
    protected $description = 'Process due CRM sequence steps and create outbound queue items';

    public function handle(SequenceService $service): int
    {
        $this->info('Processing due sequence steps...');

        $processed = $service->processDue();

        $this->info("Done. Steps processed: {$processed}");

        return 0;
    }
}
