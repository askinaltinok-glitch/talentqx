<?php

namespace App\Mail;

use App\Models\CandidateCredential;
use App\Models\PoolCandidate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CredentialExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $candidateLocale;
    public string $credentialType;
    public string $expiryDate;
    public int $daysLeft;

    private static array $subjects = [
        'tr' => 'Hatırlatma: %s belgenizin süresi %s tarihinde bitiyor — Octopus AI',
        'en' => 'Reminder: Your %s expires on %s — Octopus AI',
        'ru' => 'Напоминание: Срок действия %s истекает %s — Octopus AI',
        'az' => 'Xatırlatma: %s sənədinizin müddəti %s tarixində bitir — Octopus AI',
    ];

    public function __construct(
        public PoolCandidate $candidate,
        public CandidateCredential $credential,
        public string $reminderType,
    ) {
        $this->candidateLocale = $candidate->preferred_language ?? 'en';
        $this->credentialType = $credential->credential_type;
        $this->expiryDate = $credential->expires_at?->format('d.m.Y') ?? '-';
        $this->daysLeft = $credential->days_until_expiry ?? 0;
    }

    public function envelope(): Envelope
    {
        $template = self::$subjects[$this->candidateLocale] ?? self::$subjects['en'];
        $subject = sprintf($template, $this->credentialType, $this->expiryDate);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credential-expiry-reminder',
            with: [
                'candidate' => $this->candidate,
                'credential' => $this->credential,
                'locale' => $this->candidateLocale,
                'credentialType' => $this->credentialType,
                'expiryDate' => $this->expiryDate,
                'daysLeft' => $this->daysLeft,
                'reminderType' => $this->reminderType,
            ],
        );
    }

    public function getMailType(): string
    {
        return 'credential_expiry_reminder';
    }

    public function getSubjectText(): string
    {
        $template = self::$subjects[$this->candidateLocale] ?? self::$subjects['en'];
        return sprintf($template, $this->credentialType, $this->expiryDate);
    }
}
