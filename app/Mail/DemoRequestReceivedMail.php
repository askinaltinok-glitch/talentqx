<?php

namespace App\Mail;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoRequestReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DemoRequest $demoRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Demo Request — Octopus-AI',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo-request-received',
            with: ['r' => $this->demoRequest],
        );
    }
}
