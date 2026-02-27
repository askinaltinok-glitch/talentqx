<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use App\Support\BrandConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private array $brand;

    public function __construct(
        public User $user,
        public string $password,
        public Company $company
    ) {
        // Brand is locked to company's platform at construction time.
        // This ensures queued emails always use the correct brand.
        $this->brand = BrandConfig::forCompany($company);
    }

    public function envelope(): Envelope
    {
        $brandName = $this->brand['brand_name'];

        return new Envelope(
            subject: "{$brandName} — Portaliniz Hazir",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-welcome',
            with: [
                'user' => $this->user,
                'password' => $this->password,
                'company' => $this->company,
                'portalUrl' => $this->brand['login_url'],
                'credits' => $this->company->monthly_credits + $this->company->bonus_credits,
                'brand' => $this->brand,
            ],
        );
    }
}
