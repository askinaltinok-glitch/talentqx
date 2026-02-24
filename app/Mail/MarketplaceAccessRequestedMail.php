<?php

namespace App\Mail;

use App\Models\MarketplaceAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the platform admin (owning company) when a new access request is created.
 * Contains approve/reject links with the access token.
 */
class MarketplaceAccessRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MarketplaceAccessRequest $accessRequest) {}

    public function envelope(): Envelope
    {
        $companyName = $this->accessRequest->requestingCompany?->name ?? 'Unknown';

        return new Envelope(
            subject: "New Candidate Access Request from {$companyName} — Octopus-AI",
        );
    }

    public function content(): Content
    {
        $baseUrl = config('app.url');
        $token = $this->accessRequest->access_token;

        return new Content(
            view: 'emails.marketplace-access-requested',
            with: [
                'req' => $this->accessRequest,
                'approveUrl' => "{$baseUrl}/api/v1/marketplace-access/{$token}/approve",
                'rejectUrl' => "{$baseUrl}/api/v1/marketplace-access/{$token}/reject",
                'reviewUrl' => "{$baseUrl}/api/v1/marketplace-access/{$token}",
                'expiresAt' => $this->accessRequest->token_expires_at->format('d M Y H:i'),
            ],
        );
    }
}
