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

class DemoWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private array $brand;

    public function __construct(
        public User $user,
        public string $password,
        public Company $company
    ) {
        // Brand is locked to company's platform at construction time.
        $this->brand = BrandConfig::forCompany($company);
    }

    public function envelope(): Envelope
    {
        $brandName = $this->brand['brand_name'];

        return new Envelope(
            subject: "{$brandName} — Hos Geldiniz! Demo Hesabiniz Hazir",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo-welcome',
            with: [
                'user' => $this->user,
                'password' => $this->password,
                'company' => $this->company,
                'platformUrl' => $this->brand['login_url'],
                'credits' => $this->company->monthly_credits,
                'brand' => $this->brand,
            ],
        );
    }
}
