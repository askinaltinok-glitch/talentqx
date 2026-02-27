<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\CreditPackage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public CreditPackage $package,
        public string $checkoutUrl,
        public string $currency = 'TRY'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "TalentQX — Ödeme Bağlantınız",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-link',
            with: [
                'company' => $this->company,
                'package' => $this->package,
                'checkoutUrl' => $this->checkoutUrl,
                'currency' => $this->currency,
                'amount' => $this->package->getPrice($this->currency),
            ],
        );
    }
}
