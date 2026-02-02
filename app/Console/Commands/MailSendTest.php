<?php

namespace App\Console\Commands;

use App\Models\MessageOutbox;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Job;
use App\Models\Candidate;
use App\Services\Email\EmailTemplateService;
use App\Jobs\ProcessOutboxMessagesJob;
use Illuminate\Console\Command;

class MailSendTest extends Command
{
    protected $signature = 'talentqx:mail:send-test
                            {--to=ecodeppo@gmail.com : Email address to send test to}
                            {--company=ekler-istanbul : Company slug to use}
                            {--type=all : Type of email to send (application_received, interview_invitation, all)}';

    protected $description = 'Send test emails for application_received and interview_invitation flows';

    public function handle(): int
    {
        $to = $this->option('to');
        $companySlug = $this->option('company');
        $type = $this->option('type');

        $this->info("=== TalentQX Mail Test ===");
        $this->info("To: {$to}");
        $this->info("Company: {$companySlug}");
        $this->info("Type: {$type}");
        $this->newLine();

        // Safety mode check
        $safetyMode = config('mail.safety_mode', false) === true;
        $this->info("Safety Mode: " . ($safetyMode ? 'ON' : 'OFF'));

        if ($safetyMode) {
            $whitelist = implode(', ', config('mail.test_whitelist', []));
            $this->info("Whitelist: {$whitelist}");
        }
        $this->newLine();

        // Load company
        $company = Company::where('slug', $companySlug)->first();
        if (!$company) {
            $this->error("Company not found: {$companySlug}");
            return Command::FAILURE;
        }

        $this->info("Company: {$company->name}");
        $this->info("Brand Color: {$company->getBrandColor()}");
        $this->info("Reply-To: {$company->getEmailReplyTo()}");
        $this->newLine();

        // Load or create test data
        $branch = Branch::where('company_id', $company->id)->first();
        $job = Job::where('company_id', $company->id)->first();

        if (!$branch || !$job) {
            $this->error("Company must have at least one branch and one job.");
            return Command::FAILURE;
        }

        // Create mock candidate data
        $candidateData = [
            'id' => 'test-' . uniqid(),
            'name' => 'Test Aday',
            'first_name' => 'Test',
            'last_name' => 'Aday',
            'email' => $to,
        ];

        $emailService = new EmailTemplateService();
        $sentCount = 0;

        // Send application_received
        if ($type === 'all' || $type === 'application_received') {
            $this->info("Sending application_received email...");

            $rendered = $emailService->renderApplicationReceived([
                'company' => $company,
                'branch' => $branch,
                'job' => $job,
                'candidate' => $candidateData,
                'locale' => 'tr',
            ]);

            $outbox = $this->createOutboxEntry($company, $to, $rendered, 'application_received');
            $this->info("  Outbox created: {$outbox->id}");
            $this->info("  Subject: {$outbox->subject}");
            $sentCount++;
        }

        // Send interview_invitation
        if ($type === 'all' || $type === 'interview_invitation') {
            $this->info("Sending interview_invitation email...");

            $interviewUrl = config('app.url') . '/interview/test-token-' . uniqid();
            $expiresAt = now()->addHours(48);

            $rendered = $emailService->renderInterviewInvitation([
                'company' => $company,
                'branch' => $branch,
                'job' => $job,
                'candidate' => $candidateData,
                'interview_url' => $interviewUrl,
                'expires_at' => $expiresAt,
                'locale' => 'tr',
            ]);

            $outbox = $this->createOutboxEntry($company, $to, $rendered, 'interview_invitation');
            $this->info("  Outbox created: {$outbox->id}");
            $this->info("  Subject: {$outbox->subject}");
            $sentCount++;
        }

        $this->newLine();
        $this->info("Processing outbox...");

        // Process immediately
        $job = new ProcessOutboxMessagesJob(10);
        $job->handle();

        $this->newLine();
        $this->info("=== Results ===");

        // Show results
        $results = MessageOutbox::where('recipient', $to)
            ->where('created_at', '>=', now()->subMinutes(1))
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($results as $msg) {
            $statusIcon = match($msg->status) {
                'sent' => '✓',
                'blocked' => '⊘',
                'failed' => '✗',
                default => '?',
            };

            $this->line("  {$statusIcon} [{$msg->status}] {$msg->subject}");

            if ($msg->status === 'blocked') {
                $this->warn("    Reason: {$msg->error_message}");
            }

            if ($msg->status === 'sent') {
                $this->info("    External ID: {$msg->external_id}");
            }
        }

        $this->newLine();
        $this->info("Check inbox: {$to}");

        return Command::SUCCESS;
    }

    private function createOutboxEntry(Company $company, string $to, array $rendered, string $templateId): MessageOutbox
    {
        return MessageOutbox::create([
            'company_id' => $company->id,
            'channel' => MessageOutbox::CHANNEL_EMAIL,
            'recipient' => $to,
            'recipient_name' => 'Test Aday',
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
            'template_id' => $templateId,
            'status' => MessageOutbox::STATUS_PENDING,
            'priority' => 10,
            'metadata' => [
                'test' => true,
                'sent_via' => 'talentqx:mail:send-test',
            ],
        ]);
    }
}
