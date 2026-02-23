<?php

namespace App\Mail;

use App\Models\InterviewInvitation;
use App\Models\PoolCandidate;
use App\Services\Brand\BrandResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InterviewInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $candidateLocale;
    public array $brand;

    private const SUBJECTS = [
        'en' => 'Your Behavioral Assessment Invitation',
        'tr' => 'Davranışsal Değerlendirme Davetiniz',
        'ru' => 'Приглашение на поведенческую оценку',
        'az' => 'Davranış Qiymətləndirmə Dəvətiniz',
        'fil' => 'Imbitasyon sa Behavioral Assessment',
        'id' => 'Undangan Penilaian Perilaku Anda',
        'uk' => 'Запрошення на поведінкову оцінку',
    ];

    public function __construct(
        public PoolCandidate $candidate,
        public InterviewInvitation $invitation,
        ?array $brand = null,
    ) {
        $this->candidateLocale = $candidate->preferred_language ?? 'en';
        $this->brand = $brand ?? BrandResolver::fromCandidate($candidate);
    }

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        $subject = self::SUBJECTS[$this->candidateLocale] ?? self::SUBJECTS['en'];

        return new \Illuminate\Mail\Mailables\Envelope(
            subject: $subject,
        );
    }

    public function content(): \Illuminate\Mail\Mailables\Content
    {
        $appDomain = $this->brand['frontend_domain'] ?? 'app.octopus-ai.net';
        $token = $this->invitation->invitation_token;
        $interviewUrl = "https://{$appDomain}/{$this->candidateLocale}/maritime/interview?token={$token}";

        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.interview-invitation',
            with: [
                'candidate' => $this->candidate,
                'invitation' => $this->invitation,
                'locale' => $this->candidateLocale,
                'brand' => $this->brand,
                'interviewUrl' => $interviewUrl,
                'expiresAt' => $this->invitation->expires_at,
                'rank' => $this->invitation->meta['rank'] ?? $this->candidate->rank ?? null,
            ],
        );
    }

    public function getMailType(): string
    {
        return 'interview_invitation';
    }

    public function getSubjectText(): string
    {
        return self::SUBJECTS[$this->candidateLocale] ?? self::SUBJECTS['en'];
    }
}
