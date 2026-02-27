<?php

namespace App\Mail;

use App\Models\Candidate;
use App\Support\BrandConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidateRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Candidate $candidate,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        $companyName = $this->candidate->company->name
            ?? $this->candidate->job?->company?->name
            ?? $this->brandName();

        return new Envelope(
            subject: "Başvuru Sonucunuz — {$companyName}",
        );
    }

    public function content(): Content
    {
        $company = $this->candidate->company
            ?? $this->candidate->job?->company;

        return new Content(
            view: 'emails.candidate-rejected',
            with: [
                'candidate' => $this->candidate,
                'companyName' => $company->name ?? $this->brandName(),
                'positionTitle' => $this->candidate->job?->title ?? 'Belirtilmemiş',
                'brandName' => $this->brandName(),
            ],
        );
    }

    private function brandName(): string
    {
        $platform = $this->candidate->company->platform
            ?? $this->candidate->job?->company?->platform
            ?? 'octopus';
        return BrandConfig::brandName($platform);
    }
}
