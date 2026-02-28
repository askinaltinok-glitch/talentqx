<?php

namespace App\Mail;

use App\Models\Candidate;
use App\Models\Interview;
use App\Models\Job;
use App\Support\BrandConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QrApplyInterviewReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Candidate $candidate,
        public Job $job,
        public Interview $interview,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        $companyName = $this->job->company->name ?? $this->brandName();

        return new Envelope(
            subject: "Mülakatınız 1 Saat Sonra — {$companyName}",
        );
    }

    public function content(): Content
    {
        $logoUrl = $this->job->company->getLogoUrl();

        return new Content(
            view: 'emails.qr-apply-interview-reminder',
            with: [
                'candidate' => $this->candidate,
                'job' => $this->job,
                'interview' => $this->interview,
                'companyName' => $this->job->company->name ?? $this->brandName(),
                'brandName' => $this->brandName(),
                'scheduledAt' => $this->interview->scheduled_at,
                'interviewUrl' => $this->interview->getInterviewUrl(),
                'companyLogoUrl' => $logoUrl ? rtrim(config('app.url'), '/') . $logoUrl : null,
            ],
        );
    }

    private function brandName(): string
    {
        $platform = $this->job->company->platform ?? 'octopus';
        return BrandConfig::brandName($platform);
    }
}
