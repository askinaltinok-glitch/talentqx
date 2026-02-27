<?php

namespace App\Mail;

use App\Models\PoolCandidate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaritimeVerificationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PoolCandidate $candidate,
        public string $code,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        $locale = $this->candidate->preferred_language ?? 'en';

        $subjects = [
            'en' => "Your Verification Code: {$this->code} — Octopus AI",
            'tr' => "Doğrulama Kodunuz: {$this->code} — Octopus AI",
            'ru' => "Ваш код подтверждения: {$this->code} — Octopus AI",
            'az' => "Doğrulama Kodunuz: {$this->code} — Octopus AI",
            'fil' => "Iyong Verification Code: {$this->code} — Octopus AI",
            'id' => "Kode Verifikasi Anda: {$this->code} — Octopus AI",
            'uk' => "Ваш код підтвердження: {$this->code} — Octopus AI",
        ];

        return new Envelope(
            subject: $subjects[$locale] ?? $subjects['en'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.maritime-verification-code',
            with: [
                'candidate' => $this->candidate,
                'code' => $this->code,
                'locale' => $this->candidate->preferred_language ?? 'en',
            ],
        );
    }
}
