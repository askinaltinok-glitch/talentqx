<?php

namespace App\Mail;

use App\Models\OrgCultureInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CultureInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    private const SUBJECTS = [
        'tr' => 'Kültür Değerlendirmesi Daveti',
        'en' => 'Culture Assessment Invitation',
    ];

    public string $lang;

    public function __construct(
        public OrgCultureInvite $invite,
        public string $employeeName,
        string $lang = 'tr',
    ) {
        $this->lang = $lang;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: self::SUBJECTS[$this->lang] ?? self::SUBJECTS['en'],
        );
    }

    public function content(): Content
    {
        $magicUrl = 'https://org.talentqx.com/c/' . $this->invite->token;

        return new Content(
            view: 'emails.culture-invite',
            with: [
                'employeeName' => $this->employeeName,
                'magicUrl' => $magicUrl,
                'locale' => $this->lang,
            ],
        );
    }
}
