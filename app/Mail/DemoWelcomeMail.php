<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $password,
        public Company $company
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Octopus AI\'a Hoş Geldiniz! Demo Hesabınız Hazır',
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
                'platformUrl' => config('app.frontend_url', 'https://talentqx.com') . '/platform',
                'credits' => $this->company->monthly_credits,
            ],
        );
    }
}
