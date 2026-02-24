<?php

namespace App\Mail;

use App\Models\MarketplaceAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the requesting company when their access request is approved or rejected.
 */
class MarketplaceAccessRespondedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MarketplaceAccessRequest $accessRequest,
        public string $outcome, // 'approved' or 'rejected'
    ) {}

    public function envelope(): Envelope
    {
        $status = $this->outcome === 'approved' ? 'Approved' : 'Declined';

        return new Envelope(
            subject: "Candidate Access Request {$status} — Octopus-AI",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.marketplace-access-responded',
            with: [
                'req' => $this->accessRequest,
                'outcome' => $this->outcome,
                'candidateName' => trim(
                    ($this->accessRequest->candidate?->first_name ?? '') . ' ' .
                    ($this->accessRequest->candidate?->last_name ?? '')
                ) ?: '—',
            ],
        );
    }
}
