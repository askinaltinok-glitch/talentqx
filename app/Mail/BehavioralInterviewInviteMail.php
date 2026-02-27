<?php

namespace App\Mail;

use App\Models\PoolCandidate;
use App\Services\Brand\BrandResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BehavioralInterviewInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $candidateLocale;
    public array $brand;

    public function __construct(
        public PoolCandidate $candidate,
        ?array $brand = null,
    ) {
        $this->candidateLocale = $candidate->preferred_language ?? 'en';
        $this->brand = $brand ?? BrandResolver::fromCandidate($candidate);
    }

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: BrandResolver::subject($this->brand, 'behavioral_interview_invite', $this->candidateLocale),
        );
    }

    public function content(): \Illuminate\Mail\Mailables\Content
    {
        $appDomain = $this->brand['frontend_domain'] ?? 'octopus-ai.net';
        $token = $this->candidate->public_token ?? '';
        $interviewUrl = "https://{$appDomain}/{$this->candidateLocale}/maritime/behavioral-interview?candidate_id={$this->candidate->id}&t={$token}";

        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.behavioral-interview-invite',
            with: [
                'candidate' => $this->candidate,
                'locale' => $this->candidateLocale,
                'brand' => $this->brand,
                'interviewUrl' => $interviewUrl,
            ],
        );
    }

    public function getMailType(): string
    {
        return 'behavioral_interview_invite';
    }

    public function getSubjectText(): string
    {
        return BrandResolver::subject($this->brand, 'behavioral_interview_invite', $this->candidateLocale);
    }
}
