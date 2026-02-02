<?php

namespace App\Console\Commands;

use App\Models\MessageOutbox;
use App\Models\Company;
use App\Jobs\ProcessOutboxMessagesJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class TestEmail extends Command
{
    protected $signature = 'talentqx:test-email
                            {--to= : Email address to send to}
                            {--direct : Send directly via Mail instead of outbox}';

    protected $description = 'Send a test email to verify SMTP configuration';

    public function handle(): int
    {
        $to = $this->option('to') ?: 'test@example.com';
        $isDirect = $this->option('direct');

        $this->info("Testing email delivery to: {$to}");
        $this->info("SMTP Host: " . config('mail.mailers.smtp.host'));
        $this->info("SMTP Port: " . config('mail.mailers.smtp.port'));
        $this->info("From: " . config('mail.from.address'));

        if ($isDirect) {
            return $this->sendDirect($to);
        }

        return $this->sendViaOutbox($to);
    }

    protected function sendDirect(string $to): int
    {
        $this->info("\nSending directly via Mail facade...");

        try {
            $timestamp = now()->format('Y-m-d H:i:s');
            $subject = "TalentQX Direct Test - {$timestamp}";
            $body = $this->getEmailBody($timestamp, 'DIRECT');

            Mail::html($body, function (Message $mail) use ($to, $subject) {
                $mail->to($to)->subject($subject);
            });

            $this->info("✓ Email sent successfully!");
            $this->info("  Subject: {$subject}");
            $this->info("  Check your inbox (and spam folder).");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Failed to send email: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function sendViaOutbox(string $to): int
    {
        $this->info("\nCreating outbox entry and processing...");

        try {
            // Get any company ID
            $companyId = Company::first()?->id;
            if (!$companyId) {
                $this->error("No company found in database. Cannot create outbox entry.");
                return Command::FAILURE;
            }

            $timestamp = now()->format('Y-m-d H:i:s');
            $subject = "TalentQX Outbox Test - {$timestamp}";
            $body = $this->getEmailBody($timestamp, 'OUTBOX');

            // Create outbox entry
            $outbox = MessageOutbox::create([
                'company_id' => $companyId,
                'channel' => MessageOutbox::CHANNEL_EMAIL,
                'recipient' => $to,
                'recipient_name' => 'Test Recipient',
                'subject' => $subject,
                'body' => $body,
                'status' => MessageOutbox::STATUS_PENDING,
                'priority' => 10, // High priority
            ]);

            $this->info("✓ Outbox entry created: {$outbox->id}");

            // Process immediately
            $this->info("Processing outbox message...");

            $job = new ProcessOutboxMessagesJob(1);
            $job->handle();

            // Refresh and check status
            $outbox->refresh();

            if ($outbox->status === MessageOutbox::STATUS_SENT) {
                $this->info("✓ Email sent successfully!");
                $this->info("  Status: {$outbox->status}");
                $this->info("  External ID: {$outbox->external_id}");
                $this->info("  Sent at: {$outbox->sent_at}");
                $this->info("  Check your inbox (and spam folder).");
                return Command::SUCCESS;
            } else {
                $this->error("✗ Email not sent.");
                $this->error("  Status: {$outbox->status}");
                $this->error("  Error: {$outbox->error_message}");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("✗ Failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function getEmailBody(string $timestamp, string $method): string
    {
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $from = config('mail.from.address');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>TalentQX Email Test</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; text-align: center;">TalentQX Email Test</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee;">
        <p style="font-size: 16px; color: #333;">
            <strong>Bu bir test emailidir.</strong>
        </p>
        <p style="color: #666;">
            Email sistemi başarıyla çalışıyorsa bu mesajı görüyorsunuz demektir.
        </p>
        <table style="width: 100%; margin: 20px 0; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #888;">Method:</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>{$method}</strong></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #888;">Timestamp:</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">{$timestamp}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #888;">SMTP Host:</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">{$host}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #888;">SMTP Port:</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">{$port}</td>
            </tr>
            <tr>
                <td style="padding: 8px; color: #888;">From:</td>
                <td style="padding: 8px;">{$from}</td>
            </tr>
        </table>
        <p style="color: #888; font-size: 12px; text-align: center; margin-top: 20px;">
            © TalentQX - AI Destekli Mülakat Platformu
        </p>
    </div>
</body>
</html>
HTML;
    }
}
