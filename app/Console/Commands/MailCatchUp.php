<?php

namespace App\Console\Commands;

use App\Models\MessageOutbox;
use App\Models\Company;
use App\Models\Candidate;
use App\Services\Email\EmailTemplateService;
use App\Jobs\ProcessOutboxMessagesJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MailCatchUp extends Command
{
    protected $signature = 'talentqx:mail:catch-up
                            {--company= : Company slug to process (required)}
                            {--hours=24 : Look back hours for missed emails}
                            {--dry-run : Show what would be sent without actually sending}
                            {--process : Process the outbox immediately after creating entries}
                            {--type=interview_invitation : Email type (interview_invitation only, application_received disabled)}';

    protected $description = 'Send missed interview_invitation emails to candidates with pending interviews (application_received is DISABLED)';

    public function handle(): int
    {
        $companySlug = $this->option('company');
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');
        $process = $this->option('process');
        $type = $this->option('type');

        if (!$companySlug) {
            $this->error("--company option is required");
            return Command::FAILURE;
        }

        // IMPORTANT: application_received is DISABLED per spec
        if ($type === 'application_received') {
            $this->error("❌ application_received emails are DISABLED.");
            $this->error("   Per spec: Do NOT send retroactive 'Application Received' emails.");
            $this->error("   Only interview_invitation emails are allowed.");
            return Command::FAILURE;
        }

        if ($type !== 'interview_invitation') {
            $this->error("Invalid type. Only 'interview_invitation' is supported.");
            return Command::FAILURE;
        }

        $this->info("=== TalentQX Mail Catch-Up (Interview Invitations Only) ===");
        $this->info("Company: {$companySlug}");
        $this->info("Look back: {$hours} hours");
        $this->info("Type: {$type}");
        $this->info("Dry run: " . ($dryRun ? 'YES' : 'NO'));
        $this->newLine();

        // Safety mode check
        $safetyMode = config('mail.safety_mode', false) === true;
        $this->info("Safety Mode: " . ($safetyMode ? 'ON' : 'OFF'));

        if ($safetyMode) {
            $whitelist = implode(', ', config('mail.test_whitelist', []));
            $this->warn("Only whitelisted emails will be sent: {$whitelist}");
        }
        $this->newLine();

        // Load company
        $company = Company::where('slug', $companySlug)->first();
        if (!$company) {
            $this->error("Company not found: {$companySlug}");
            return Command::FAILURE;
        }

        $this->info("Company: {$company->name} ({$company->id})");
        $this->newLine();

        // Find interviews without interview_invitation email
        $cutoffTime = now()->subHours($hours);

        $interviewsWithoutEmail = \App\Models\Interview::where('created_at', '>=', $cutoffTime)
            ->whereHas('candidate', function ($q) use ($company) {
                $q->where('company_id', $company->id)
                  ->whereNotNull('email')
                  ->where('email', '!=', '');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('message_outbox')
                    ->whereColumn('message_outbox.related_id', 'interviews.id')
                    ->where('message_outbox.related_type', 'interview')
                    ->where('message_outbox.template_id', 'interview_invitation');
            })
            ->with(['candidate', 'job', 'job.company', 'job.branch'])
            ->get();

        if ($interviewsWithoutEmail->isEmpty()) {
            $this->info("No interviews found without interview_invitation email.");
            return Command::SUCCESS;
        }

        $this->info("Found {$interviewsWithoutEmail->count()} interviews without interview_invitation email:");
        $this->newLine();

        $emailService = new EmailTemplateService();
        $createdCount = 0;

        foreach ($interviewsWithoutEmail as $interview) {
            $candidate = $interview->candidate;
            $job = $interview->job;
            $branch = $job?->branch;

            if (!$job || !$candidate) {
                $this->warn("  Skipping interview {$interview->id} - no job or candidate");
                continue;
            }

            $candidateData = [
                'id' => $candidate->id,
                'name' => trim($candidate->first_name . ' ' . $candidate->last_name),
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
            ];

            $this->line("  {$candidate->email}");
            $this->line("    Name: {$candidateData['name']}");
            $this->line("    Job: {$job->title}");
            $this->line("    Interview created: {$interview->created_at->format('Y-m-d H:i')}");

            if ($dryRun) {
                $this->info("    [DRY RUN] Would send interview_invitation email");
                continue;
            }

            // Render and create outbox entry
            $rendered = $emailService->renderInterviewInvitation([
                'company' => $company,
                'branch' => $branch,
                'job' => $job,
                'candidate' => $candidateData,
                'interview_url' => $interview->getInterviewUrl(),
                'expires_at' => $interview->token_expires_at,
                'duration_minutes' => $job->interview_settings['max_duration_minutes'] ?? 20,
                'locale' => 'tr',
            ]);

            $outbox = MessageOutbox::create([
                'company_id' => $company->id,
                'channel' => MessageOutbox::CHANNEL_EMAIL,
                'recipient' => $candidate->email,
                'recipient_name' => $candidateData['name'],
                'subject' => $rendered['subject'],
                'body' => $rendered['body'],
                'template_id' => 'interview_invitation',
                'related_type' => 'interview',
                'related_id' => $interview->id,
                'status' => MessageOutbox::STATUS_PENDING,
                'priority' => 10,
                'metadata' => [
                    'catch_up' => true,
                    'interview_created_at' => $interview->created_at->toIso8601String(),
                ],
            ]);

            $this->info("    Created outbox: {$outbox->id}");
            $createdCount++;
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("Dry run complete. {$interviewsWithoutEmail->count()} emails would be sent.");
            return Command::SUCCESS;
        }

        $this->info("Created {$createdCount} outbox entries.");

        if ($process && $createdCount > 0) {
            $this->newLine();
            $this->info("Processing outbox...");

            $job = new ProcessOutboxMessagesJob($createdCount + 10);
            $job->handle();

            // Show results
            $this->newLine();
            $this->info("=== Results ===");

            $results = MessageOutbox::where('company_id', $company->id)
                ->where('template_id', 'interview_invitation')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->whereNotNull('metadata->catch_up')
                ->get();

            $sent = $results->where('status', 'sent')->count();
            $blocked = $results->where('status', 'blocked')->count();
            $pending = $results->where('status', 'pending')->count();

            $this->info("  Sent: {$sent}");
            $this->warn("  Blocked (safety mode): {$blocked}");
            $this->line("  Pending: {$pending}");
        } else {
            $this->info("Entries created. Run 'php artisan outbox:process' or wait for scheduler to send.");
        }

        return Command::SUCCESS;
    }
}
