<?php

namespace App\Console\Commands;

use App\Models\InterviewInvitation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireInterviewInvitationsCommand extends Command
{
    protected $signature = 'maritime:expire-invitations';

    protected $description = 'Expire interview invitations that have passed their 48-hour window';

    public function handle(): int
    {
        $expired = InterviewInvitation::whereIn('status', [
                InterviewInvitation::STATUS_INVITED,
                InterviewInvitation::STATUS_STARTED,
            ])
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $invitation) {
            $invitation->markExpired();
            $count++;
        }

        if ($count > 0) {
            $this->info("Expired {$count} interview invitation(s).");
            Log::info('maritime:expire-invitations: expired invitations', ['count' => $count]);
        } else {
            $this->info('No invitations to expire.');
        }

        return self::SUCCESS;
    }
}
