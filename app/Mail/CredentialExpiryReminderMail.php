<?php

namespace App\Mail;

use App\Models\CandidateCredential;
use App\Models\PoolCandidate;
use App\Services\Brand\BrandResolver;
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
    public array $brand;

    private static array $subjectTemplates = [
        'tr' => 'Hatırlatma: %s belgenizin süresi %s tarihinde bitiyor — %s',
        'en' => 'Reminder: Your %s expires on %s — %s',
        'ru' => 'Напоминание: Срок действия %s истекает %s — %s',
        'az' => 'Xatırlatma: %s sənədinizin müddəti %s tarixində bitir — %s',
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
        $this->brand = BrandResolver::fromCandidate($candidate);
    }

    public function envelope(): Envelope
    {
        $template = self::$subjectTemplates[$this->candidateLocale] ?? self::$subjectTemplates['en'];
        $brandName = $this->brand['name'] ?? 'Octopus AI';
        $subject = sprintf($template, $this->credentialType, $this->expiryDate, $brandName);

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
                'brand' => $this->brand,
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
