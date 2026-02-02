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
                            {--process : Process the outbox immediately after creating entries}';

    protected $description = 'Send missed application_received emails to candidates who applied recently';

    public function handle(): int
    {
        $companySlug = $this->option('company');
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');
        $process = $this->option('process');

        if (!$companySlug) {
            $this->error("--company option is required");
            return Command::FAILURE;
        }

        $this->info("=== TalentQX Mail Catch-Up ===");
        $this->info("Company: {$companySlug}");
        $this->info("Look back: {$hours} hours");
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

        // Find candidates without application_received email
        $cutoffTime = now()->subHours($hours);

        $candidatesWithoutEmail = Candidate::where('company_id', $company->id)
            ->where('created_at', '>=', $cutoffTime)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('message_outbox')
                    ->whereColumn('message_outbox.related_id', 'candidates.id')
                    ->where('message_outbox.related_type', 'candidate')
                    ->where('message_outbox.template_id', 'application_received');
            })
            ->with(['job', 'job.branch'])
            ->get();

        if ($candidatesWithoutEmail->isEmpty()) {
            $this->info("No candidates found without application_received email.");
            return Command::SUCCESS;
        }

        $this->info("Found {$candidatesWithoutEmail->count()} candidates without application_received email:");
        $this->newLine();

        $emailService = new EmailTemplateService();
        $createdCount = 0;

        foreach ($candidatesWithoutEmail as $candidate) {
            $job = $candidate->job;
            $branch = $job?->branch;

            if (!$job) {
                $this->warn("  Skipping {$candidate->email} - no job associated");
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
            $this->line("    Applied: {$candidate->created_at->format('Y-m-d H:i')}");

            if ($dryRun) {
                $this->info("    [DRY RUN] Would send application_received email");
                continue;
            }

            // Render and create outbox entry
            $rendered = $emailService->renderApplicationReceived([
                'company' => $company,
                'branch' => $branch,
                'job' => $job,
                'candidate' => $candidateData,
                'locale' => 'tr',
            ]);

            $outbox = MessageOutbox::create([
                'company_id' => $company->id,
                'channel' => MessageOutbox::CHANNEL_EMAIL,
                'recipient' => $candidate->email,
                'recipient_name' => $candidateData['name'],
                'subject' => $rendered['subject'],
                'body' => $rendered['body'],
                'template_id' => 'application_received',
                'related_type' => 'candidate',
                'related_id' => $candidate->id,
                'status' => MessageOutbox::STATUS_PENDING,
                'priority' => 5,
                'metadata' => [
                    'catch_up' => true,
                    'original_applied_at' => $candidate->created_at->toIso8601String(),
                ],
            ]);

            $this->info("    Created outbox: {$outbox->id}");
            $createdCount++;
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("Dry run complete. {$candidatesWithoutEmail->count()} emails would be sent.");
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
                ->where('template_id', 'application_received')
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
