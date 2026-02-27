<?php

namespace App\Mail;

use App\Models\Candidate;
use App\Models\Job;
use App\Support\BrandConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QrApplyCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Candidate $candidate,
        public Job $job,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        $companyName = $this->job->company->name ?? $this->brandName();

        return new Envelope(
            subject: "Mülakatınız Tamamlandı — {$companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.qr-apply-completed',
            with: [
                'candidate' => $this->candidate,
                'job' => $this->job,
                'companyName' => $this->job->company->name ?? $this->brandName(),
                'positionTitle' => $this->job->title ?? 'Belirtilmemiş',
                'brandName' => $this->brandName(),
            ],
        );
    }

    private function brandName(): string
    {
        $platform = $this->job->company->platform ?? 'octopus';
        return BrandConfig::brandName($platform);
    }
}
