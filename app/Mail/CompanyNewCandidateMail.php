<?php

namespace App\Mail;

use App\Models\Candidate;
use App\Models\InterviewAnalysis;
use App\Support\BrandConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyNewCandidateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Candidate $candidate,
        public ?InterviewAnalysis $analysis = null,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        $candidateName = $this->candidate->full_name ?? 'Yeni Aday';

        return new Envelope(
            subject: "Yeni Aday Değerlendirildi: {$candidateName}",
        );
    }

    public function content(): Content
    {
        $company = $this->candidate->company
            ?? $this->candidate->job?->company;

        $brandName = BrandConfig::brandName($company->platform ?? 'octopus');

        $recommendation = $this->analysis?->decision_snapshot['recommendation'] ?? null;
        $overallScore = $this->analysis?->overall_score;
        $confidence = $this->analysis?->getConfidencePercent();
        $hasRedFlags = $this->analysis?->hasRedFlags() ?? false;

        $adminUrl = config('app.frontend_url', 'https://octopus-ai.net')
            . '/octo-admin/candidates/' . $this->candidate->id;

        return new Content(
            view: 'emails.company-new-candidate',
            with: [
                'candidate' => $this->candidate,
                'companyName' => $company->name ?? $brandName,
                'positionTitle' => $this->candidate->job?->title ?? 'Belirtilmemiş',
                'completedAt' => $this->candidate->latestInterview?->completed_at,
                'overallScore' => $overallScore,
                'recommendation' => $recommendation,
                'confidence' => $confidence,
                'hasRedFlags' => $hasRedFlags,
                'adminUrl' => $adminUrl,
                'brandName' => $brandName,
            ],
        );
    }
}
