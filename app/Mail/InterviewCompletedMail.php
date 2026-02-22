<?php

namespace App\Mail;

use App\Models\PoolCandidate;
use App\Services\Brand\BrandResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $candidateLocale;
    public array $brand;

    public function __construct(
        public PoolCandidate $candidate,
        public ?string $positionName = null,
        array $brand = [],
    ) {
        $this->candidateLocale = $candidate->preferred_language ?? 'tr';
        $this->brand = $brand ?: BrandResolver::fromCandidate($candidate);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: BrandResolver::subject($this->brand, 'interview_completed', $this->candidateLocale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview-completed',
            with: [
                'candidate' => $this->candidate,
                'locale' => $this->candidateLocale,
                'positionName' => $this->positionName,
                'brand' => $this->brand,
            ],
        );
    }

    public function getMailType(): string
    {
        return 'interview_completed';
    }

    public function getSubjectText(): string
    {
        return BrandResolver::subject($this->brand, 'interview_completed', $this->candidateLocale);
    }
}
