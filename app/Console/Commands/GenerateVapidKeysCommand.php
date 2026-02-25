<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'vapid:generate';
    protected $description = 'Generate VAPID key pair for Web Push notifications';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->info('VAPID keys generated successfully.');
        $this->newLine();
        $this->line('Add these to your .env file:');
        $this->newLine();
        $this->line("VAPID_SUBJECT=mailto:noreply@octopus-ai.net");
        $this->line("VAPID_PUBLIC_KEY={$keys['publicKey']}");
        $this->line("VAPID_PRIVATE_KEY={$keys['privateKey']}");

        return Command::SUCCESS;
    }
}
