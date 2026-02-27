<?php

namespace App\Jobs;

use App\Mail\QrApplyInterviewReminderMail;
use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendQrApplyInterviewReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 60, 120];

    public function __construct(
        public Interview $interview,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $this->interview->refresh();

        // Guard: only send if interview is still pending and reminder not already sent
        if ($this->interview->status !== Interview::STATUS_PENDING) {
            return;
        }

        if ($this->interview->last_hour_reminder_sent_at) {
            return;
        }

        $candidate = $this->interview->candidate;
        if (!$candidate || !$candidate->email) {
            return;
        }

        $job = $this->interview->job;
        if (!$job) {
            return;
        }

        Mail::to($candidate->email)
            ->send(new QrApplyInterviewReminderMail($candidate, $job, $this->interview));

        $this->interview->update(['last_hour_reminder_sent_at' => now()]);
    }
}
