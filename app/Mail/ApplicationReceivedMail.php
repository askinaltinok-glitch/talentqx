<?php

namespace App\Mail;

use App\Models\PoolCandidate;
use App\Services\Brand\BrandResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $candidateLocale;
    public array $brand;

    public function __construct(
        public PoolCandidate $candidate,
        array $brand = [],
    ) {
        $this->candidateLocale = $candidate->preferred_language ?? 'tr';
        $this->brand = $brand ?: BrandResolver::fromCandidate($candidate);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: BrandResolver::subject($this->brand, 'application_received', $this->candidateLocale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-received',
            with: [
                'candidate' => $this->candidate,
                'locale' => $this->candidateLocale,
                'brand' => $this->brand,
            ],
        );
    }

    public function getMailType(): string
    {
        return 'application_received';
    }

    public function getSubjectText(): string
    {
        return BrandResolver::subject($this->brand, 'application_received', $this->candidateLocale);
    }
}
