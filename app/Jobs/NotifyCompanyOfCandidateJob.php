<?php

namespace App\Jobs;

use App\Mail\CompanyNewCandidateMail;
use App\Models\Candidate;
use App\Models\Interview;
use App\Services\AdminNotificationService;
use App\Jobs\Traits\BrandAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyCompanyOfCandidateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public $tries = 2;
    public $backoff = [30, 60];

    public function __construct(
        public Interview $interview,
    ) {
        $this->onQueue('emails');
        $this->captureBrand();
    }

    public function handle(): void
    {
        $this->setBrandDatabase();

        $interview = $this->interview;

        // Idempotent guard — already notified
        if ($interview->company_notified_at) {
            Log::info('Company already notified, skipping', [
                'interview_id' => $interview->id,
            ]);
            return;
        }

        $candidate = $interview->candidate;
        if (!$candidate) {
            Log::warning('NotifyCompanyOfCandidateJob: candidate not found', [
                'interview_id' => $interview->id,
            ]);
            return;
        }

        $company = $candidate->company ?? $candidate->job?->company;
        if (!$company) {
            Log::warning('NotifyCompanyOfCandidateJob: company not found', [
                'interview_id' => $interview->id,
                'candidate_id' => $candidate->id,
            ]);
            return;
        }

        $analysis = $interview->analysis;

        // Collect recipients: active company users, fallback to billing_email
        $recipients = $company->users()
            ->whereNotNull('email')
            ->where('is_active', true)
            ->pluck('email')
            ->unique()
            ->toArray();

        if (empty($recipients) && $company->billing_email) {
            $recipients = [$company->billing_email];
        }

        if (empty($recipients)) {
            Log::warning('NotifyCompanyOfCandidateJob: no recipients found', [
                'company_id' => $company->id,
                'company_name' => $company->name,
            ]);
            return;
        }

        // Send to all recipients
        foreach ($recipients as $email) {
            Mail::to($email)->send(new CompanyNewCandidateMail($candidate, $analysis));
        }

        // Push notification for admin
        app(AdminNotificationService::class)->notifyEmailSent(
            'company_new_candidate',
            implode(', ', $recipients),
            "Company notified: {$candidate->full_name}",
            ['candidate_id' => $candidate->id, 'company_id' => $company->id]
        );

        // Stamp as notified
        $interview->update(['company_notified_at' => now()]);

        Log::info('Company notified of new candidate', [
            'interview_id' => $interview->id,
            'candidate_id' => $candidate->id,
            'company_id' => $company->id,
            'recipient_count' => count($recipients),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('NotifyCompanyOfCandidateJob failed', [
            'interview_id' => $this->interview->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
