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

class QrApplyVerificationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Candidate $candidate,
        public Job $job,
        public string $code,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        $companyName = $this->job->company->name ?? $this->brandName();

        return new Envelope(
            subject: "Doğrulama Kodunuz: {$this->code} — {$companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.qr-apply-verification-code',
            with: [
                'candidate' => $this->candidate,
                'job' => $this->job,
                'code' => $this->code,
                'companyName' => $this->job->company->name ?? $this->brandName(),
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
