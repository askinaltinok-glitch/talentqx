<?php

namespace App\Services\Email;

use App\Models\Company;

class EmailTemplateService
{
    private const DEFAULT_PRIMARY_COLOR = '#667eea';

    /**
     * Render application received email.
     */
    public function renderApplicationReceived(array $data): array
    {
        $company = $data['company'];
        $branch = $data['branch'] ?? null;
        $job = $data['job'] ?? null;
        $candidate = $data['candidate'];
        $locale = $data['locale'] ?? 'tr';
        $roleCode = $data['role_code'] ?? null;

        $companyName = $this->getCompanyName($company);
        $branchName = is_object($branch) ? $branch->name : ($branch['name'] ?? '');
        $jobTitle = is_object($job) ? $job->title : ($job['title'] ?? 'Pozisyon');
        $candidateName = is_object($candidate) ? trim($candidate->first_name . ' ' . $candidate->last_name) : ($candidate['name'] ?? 'Aday');
        $applicationId = is_object($candidate) ? substr($candidate->id, 0, 8) : substr($candidate['id'] ?? '', 0, 8);

        $subject = $this->getSubject('application_received', $companyName, $locale, $jobTitle, $roleCode);
        $preheader = $locale === 'tr'
            ? "{$candidateName} - {$jobTitle} başvurusu alındı"
            : "{$candidateName} - Application received for {$jobTitle}";

        $body = $this->buildEmailHtml([
            'company' => $company,
            'headline' => $locale === 'tr' ? 'Başvurunuz Alındı!' : 'Application Received!',
            'headline_icon' => '✓',
            'greeting' => $locale === 'tr' ? "Merhaba {$candidateName}," : "Hello {$candidateName},",
            'message' => $locale === 'tr'
                ? "<strong>{$jobTitle}</strong> pozisyonu için başvurunuz başarıyla alındı. Başvurunuz incelendikten sonra sizinle iletişime geçilecektir."
                : "Your application for the <strong>{$jobTitle}</strong> position has been successfully received. We will contact you after reviewing your application.",
            'details' => [
                ['label' => $locale === 'tr' ? 'Şirket' : 'Company', 'value' => $companyName],
                ['label' => $locale === 'tr' ? 'Şube' : 'Branch', 'value' => $branchName],
                ['label' => $locale === 'tr' ? 'Pozisyon' : 'Position', 'value' => $jobTitle],
                ['label' => $locale === 'tr' ? 'Başvuru No' : 'Application ID', 'value' => strtoupper($applicationId)],
            ],
            'cta_text' => null,
            'cta_url' => null,
            'footer_note' => $locale === 'tr'
                ? 'Başvuru sürecinizi takip etmek için herhangi bir işlem yapmanıza gerek yok. Gerektiğinde sizinle iletişime geçeceğiz.'
                : 'You don\'t need to do anything to track your application. We will contact you when needed.',
            'locale' => $locale,
            'preheader' => $preheader,
        ]);

        return [
            'subject' => $subject,
            'body' => $body,
            'preheader' => $preheader,
        ];
    }

    /**
     * Render new application notification email (sent to company/admin).
     */
    public function renderNewApplication(array $data): array
    {
        $company = $data['company'];
        $job = $data['job'] ?? null;
        $candidate = $data['candidate'];
        $locale = $data['locale'] ?? 'tr';
        $roleCode = $data['role_code'] ?? null;
        $department = $data['department'] ?? null;

        $companyName = $this->getCompanyName($company);
        $jobTitle = is_object($job) ? $job->title : ($job['title'] ?? 'Pozisyon');
        $candidateName = is_object($candidate) ? trim($candidate->first_name . ' ' . $candidate->last_name) : ($candidate['name'] ?? 'Aday');

        $subject = $this->getSubject('new_application', $companyName, $locale, $jobTitle, $roleCode);
        $preheader = $locale === 'tr'
            ? "{$candidateName} – {$jobTitle} pozisyonuna yeni başvuru"
            : "{$candidateName} – New application for {$jobTitle}";

        $roleLabel = $roleCode ? str_replace('_', ' ', ucfirst($roleCode)) : null;

        $details = [
            ['label' => $locale === 'tr' ? 'Aday' : 'Candidate', 'value' => $candidateName],
            ['label' => $locale === 'tr' ? 'Pozisyon' : 'Position', 'value' => $jobTitle],
        ];
        if ($roleLabel) {
            $details[] = ['label' => $locale === 'tr' ? 'Rol' : 'Role', 'value' => $roleLabel];
        }
        if ($department) {
            $details[] = ['label' => $locale === 'tr' ? 'Departman' : 'Department', 'value' => ucfirst($department)];
        }

        $body = $this->buildEmailHtml([
            'company' => $company,
            'headline' => $locale === 'tr' ? 'Yeni Başvuru Alındı' : 'New Application Received',
            'headline_icon' => '📩',
            'greeting' => $locale === 'tr' ? 'Merhaba,' : 'Hello,',
            'message' => $locale === 'tr'
                ? "<strong>{$candidateName}</strong>, <strong>{$jobTitle}</strong> pozisyonuna başvurdu."
                : "<strong>{$candidateName}</strong> has applied for the <strong>{$jobTitle}</strong> position.",
            'details' => $details,
            'cta_text' => $locale === 'tr' ? 'Başvuruları Görüntüle' : 'View Applications',
            'cta_url' => config('app.url') . '/admin/jobs',
            'footer_note' => $locale === 'tr'
                ? 'Bu bildirim otomatik olarak gönderilmiştir.'
                : 'This notification was sent automatically.',
            'locale' => $locale,
            'preheader' => $preheader,
        ]);

        return [
            'subject' => $subject,
            'body' => $body,
            'preheader' => $preheader,
        ];
    }

    /**
     * Render interview invitation email.
     *
     * Final email spec:
     * - Subject: Octopus AI – {{company.name}} | Interview Invitation
     * - Greet candidate by first name
     * - Clearly state the company
     * - Show duration + expiry
     * - Include CTA button/link
     * - No "reply to this email" wording
     */
    public function renderInterviewInvitation(array $data): array
    {
        $company = $data['company'];
        $branch = $data['branch'] ?? null;
        $job = $data['job'] ?? null;
        $candidate = $data['candidate'];
        $interviewUrl = $data['interview_url'];
        $expiresAt = $data['expires_at'] ?? null;
        $durationMinutes = $data['duration_minutes'] ?? 20;
        $locale = $data['locale'] ?? 'tr';

        $companyName = $this->getCompanyName($company);
        $branchName = is_object($branch) ? $branch->name : ($branch['name'] ?? '');
        $jobTitle = is_object($job) ? $job->title : ($job['title'] ?? 'Pozisyon');

        // Use first_name for greeting (per spec)
        $firstName = is_object($candidate)
            ? $candidate->first_name
            : ($candidate['first_name'] ?? $candidate['name'] ?? 'Aday');

        // Subject: Octopus AI – Company Name | Role | Interview Invitation
        $roleCode = $data['role_code'] ?? null;
        $roleSuffix = $roleCode ? ' | ' . str_replace('_', ' ', ucfirst($roleCode)) : '';
        $subject = $locale === 'tr'
            ? "Octopus AI – {$companyName}{$roleSuffix} | Mülakat Daveti"
            : "Octopus AI – {$companyName}{$roleSuffix} | Interview Invitation";

        $preheader = $locale === 'tr'
            ? "{$firstName}, {$companyName} sizi online mülakata davet ediyor"
            : "{$firstName}, {$companyName} invites you to an online interview";

        // Format expiry date and time
        $expiryInfo = '';
        if ($expiresAt) {
            $expiryDate = is_string($expiresAt) ? $expiresAt : $expiresAt->format('d.m.Y H:i');
            $expiryInfo = $locale === 'tr'
                ? "Son katılım tarihi: <strong>{$expiryDate}</strong>"
                : "Deadline: <strong>{$expiryDate}</strong>";
        }

        // Duration info
        $durationInfo = $locale === 'tr'
            ? "Yaklaşık süre: <strong>{$durationMinutes} dakika</strong>"
            : "Duration: approx. <strong>{$durationMinutes} minutes</strong>";

        $body = $this->buildEmailHtml([
            'company' => $company,
            'headline' => $locale === 'tr' ? 'Mülakata Davetlisiniz!' : 'Interview Invitation!',
            'headline_icon' => '🎯',
            'greeting' => $locale === 'tr' ? "Merhaba {$firstName}," : "Hello {$firstName},",
            'message' => $locale === 'tr'
                ? "<strong>{$companyName}</strong> şirketi, <strong>{$jobTitle}</strong> pozisyonu için başvurunuzu inceledi ve sizi online mülakata davet ediyor."
                : "<strong>{$companyName}</strong> has reviewed your application for the <strong>{$jobTitle}</strong> position and would like to invite you to an online interview.",
            'details' => array_filter([
                ['label' => $locale === 'tr' ? 'Şirket' : 'Company', 'value' => $companyName],
                $branchName ? ['label' => $locale === 'tr' ? 'Şube' : 'Branch', 'value' => $branchName] : null,
                ['label' => $locale === 'tr' ? 'Pozisyon' : 'Position', 'value' => $jobTitle],
            ]),
            'cta_text' => $locale === 'tr' ? 'Mülakata Başla' : 'Start Interview',
            'cta_url' => $interviewUrl,
            'footer_note' => implode('<br>', array_filter([
                $durationInfo,
                $expiryInfo,
                '',
                $locale === 'tr'
                    ? 'Sessiz bir ortamda, kamera ve mikrofon erişimi olan bir cihazdan katılmanızı öneririz.'
                    : 'We recommend joining from a quiet environment with a device that has camera and microphone access.',
                '',
                $locale === 'tr'
                    ? '📌 Not: Teknik bir sorun yaşamanız durumunda mülakat bağlantısını yeniden açabilirsiniz.'
                    : '📌 Note: If you experience technical issues, you can reopen the interview link.',
            ])),
            'locale' => $locale,
            'preheader' => $preheader,
        ]);

        return [
            'subject' => $subject,
            'body' => $body,
            'preheader' => $preheader,
        ];
    }

    /**
     * Render interview reminder email (24 hours before).
     *
     * Short, clean copy:
     * - Greet by first name
     * - Remind interview is upcoming
     * - Show company name
     * - CTA button → interview link
     * - Neutral, supportive tone
     */
    public function renderInterviewReminder(array $data): array
    {
        $company = $data['company'];
        $candidate = $data['candidate'];
        $interviewUrl = $data['interview_url'];
        $expiresAt = $data['expires_at'] ?? null;
        $locale = $data['locale'] ?? 'tr';

        $companyName = $this->getCompanyName($company);
        $firstName = is_object($candidate)
            ? $candidate->first_name
            : ($candidate['first_name'] ?? $candidate['name'] ?? 'Aday');

        $subject = $locale === 'tr'
            ? "Octopus AI – Hatırlatma: {$companyName} Mülakatınız"
            : "Octopus AI – Reminder: Your Interview for {$companyName}";

        $preheader = $locale === 'tr'
            ? "{$firstName}, mülakatınız yaklaşıyor"
            : "{$firstName}, your interview is coming up";

        // Format expiry
        $expiryInfo = '';
        if ($expiresAt) {
            $expiryDate = is_string($expiresAt) ? $expiresAt : $expiresAt->format('d.m.Y H:i');
            $expiryInfo = $locale === 'tr'
                ? "Son katılım: <strong>{$expiryDate}</strong>"
                : "Deadline: <strong>{$expiryDate}</strong>";
        }

        $body = $this->buildEmailHtml([
            'company' => $company,
            'headline' => $locale === 'tr' ? 'Mülakat Hatırlatması' : 'Interview Reminder',
            'headline_icon' => '⏰',
            'greeting' => $locale === 'tr' ? "Merhaba {$firstName}," : "Hello {$firstName},",
            'message' => $locale === 'tr'
                ? "<strong>{$companyName}</strong> için online mülakatınız hâlâ sizi bekliyor. Mülakatı tamamlamak için aşağıdaki butona tıklayın."
                : "Your online interview for <strong>{$companyName}</strong> is still waiting for you. Click the button below to complete your interview.",
            'details' => [],
            'cta_text' => $locale === 'tr' ? 'Mülakata Başla' : 'Start Interview',
            'cta_url' => $interviewUrl,
            'footer_note' => implode('<br>', array_filter([
                $expiryInfo,
                '',
                $locale === 'tr'
                    ? 'Sessiz bir ortamda, kamera ve mikrofon erişimi olan bir cihazdan katılmanızı öneririz.'
                    : 'We recommend joining from a quiet environment with camera and microphone access.',
                '',
                $locale === 'tr'
                    ? '📌 Not: Teknik bir sorun yaşamanız durumunda mülakat bağlantısını yeniden açabilirsiniz.'
                    : '📌 Note: If you experience technical issues, you can reopen the interview link.',
            ])),
            'locale' => $locale,
            'preheader' => $preheader,
        ]);

        return [
            'subject' => $subject,
            'body' => $body,
            'preheader' => $preheader,
        ];
    }

    /**
     * Render last hour reminder email (T-1).
     *
     * Short, urgent but friendly:
     * - Greet by first name
     * - Clear "1 hour left" message
     * - CTA button → interview link
     */
    public function renderLastHourReminder(array $data): array
    {
        $company = $data['company'];
        $candidate = $data['candidate'];
        $job = $data['job'] ?? null;
        $interviewUrl = $data['interview_url'];
        $scheduledAt = $data['scheduled_at'] ?? null;
        $locale = $data['locale'] ?? 'tr';

        $companyName = $this->getCompanyName($company);
        $jobTitle = is_object($job) ? $job->title : ($job['title'] ?? '');
        $firstName = is_object($candidate)
            ? $candidate->first_name
            : ($candidate['first_name'] ?? $candidate['name'] ?? 'Aday');

        $subject = $locale === 'tr'
            ? "Octopus AI – ⏰ 1 saat sonra: Mülakatınız başlıyor"
            : "Octopus AI – ⏰ 1 hour left: Your interview is starting";

        $preheader = $locale === 'tr'
            ? "{$firstName}, mülakatınız 1 saat sonra başlayacak"
            : "{$firstName}, your interview starts in 1 hour";

        // Format time
        $timeStr = '';
        if ($scheduledAt) {
            $timeStr = is_string($scheduledAt) ? $scheduledAt : $scheduledAt->format('H:i');
        }

        $body = $this->buildEmailHtml([
            'company' => $company,
            'headline' => $locale === 'tr' ? 'Son Hatırlatma' : 'Final Reminder',
            'headline_icon' => '⏰',
            'greeting' => $locale === 'tr' ? "Merhaba {$firstName}," : "Hello {$firstName},",
            'message' => $locale === 'tr'
                ? ($jobTitle
                    ? "<strong>{$jobTitle}</strong> mülakatınız <strong>1 saat sonra</strong> başlayacak."
                    : "Mülakatınız <strong>1 saat sonra</strong> başlayacak.")
                : ($jobTitle
                    ? "Your <strong>{$jobTitle}</strong> interview starts in <strong>1 hour</strong>."
                    : "Your interview starts in <strong>1 hour</strong>."),
            'details' => $timeStr ? [
                ['label' => $locale === 'tr' ? 'Saat' : 'Time', 'value' => $timeStr],
            ] : [],
            'cta_text' => $locale === 'tr' ? 'Mülakata Katıl' : 'Join Interview',
            'cta_url' => $interviewUrl,
            'footer_note' => $locale === 'tr'
                ? 'Bağlantıyı şimdiden test edebilirsiniz. Görüşmek üzere!<br><br>📌 Not: Teknik bir sorun yaşamanız durumunda mülakat bağlantısını yeniden açabilirsiniz.'
                : 'You can test the link now. See you soon!<br><br>📌 Note: If you experience technical issues, you can reopen the interview link.',
            'locale' => $locale,
            'preheader' => $preheader,
        ]);

        return [
            'subject' => $subject,
            'body' => $body,
            'preheader' => $preheader,
        ];
    }

    /**
     * Render interview completion email.
     *
     * Purpose: Close the loop, reassure candidate
     * - Thank candidate
     * - Confirm interview completed successfully
     * - State evaluation is in progress
     * - No timelines promised
     * - Neutral, professional, calm tone
     */
    public function renderInterviewCompleted(array $data): array
    {
        $company = $data['company'];
        $candidate = $data['candidate'];
        $job = $data['job'] ?? null;
        $locale = $data['locale'] ?? 'tr';

        $companyName = $this->getCompanyName($company);
        $jobTitle = is_object($job) ? $job->title : ($job['title'] ?? '');
        $firstName = is_object($candidate)
            ? $candidate->first_name
            : ($candidate['first_name'] ?? $candidate['name'] ?? 'Aday');

        $subject = $locale === 'tr'
            ? "Octopus AI – Mülakatınız Tamamlandı – Teşekkürler"
            : "Octopus AI – Thank you for completing your interview";

        $preheader = $locale === 'tr'
            ? "{$firstName}, mülakatınız başarıyla alındı"
            : "{$firstName}, your interview has been received";

        $message = $locale === 'tr'
            ? "<strong>{$companyName}</strong> için mülakatınızı başarıyla tamamladınız. Yanıtlarınız değerlendirme sürecine alınmıştır."
            : "You have successfully completed your interview for <strong>{$companyName}</strong>. Your responses have been submitted for evaluation.";

        $body = $this->buildEmailHtml([
            'company' => $company,
            'headline' => $locale === 'tr' ? 'Mülakat Tamamlandı' : 'Interview Completed',
            'headline_icon' => '✓',
            'greeting' => $locale === 'tr' ? "Merhaba {$firstName}," : "Hello {$firstName},",
            'message' => $message,
            'details' => array_filter([
                ['label' => $locale === 'tr' ? 'Şirket' : 'Company', 'value' => $companyName],
                $jobTitle ? ['label' => $locale === 'tr' ? 'Pozisyon' : 'Position', 'value' => $jobTitle] : null,
            ]),
            'cta_text' => null,
            'cta_url' => null,
            'footer_note' => $locale === 'tr'
                ? 'Değerlendirme süreciyle ilgili herhangi bir işlem yapmanıza gerek yoktur. Gerektiğinde sizinle iletişime geçilecektir.'
                : 'You don\'t need to take any action regarding the evaluation process. You will be contacted when needed.',
            'locale' => $locale,
            'preheader' => $preheader,
        ]);

        return [
            'subject' => $subject,
            'body' => $body,
            'preheader' => $preheader,
        ];
    }

    /**
     * Render password reset email.
     */
    public function renderPasswordReset(array $data): array
    {
        $name = $data['name'] ?? 'Kullanıcı';
        $resetLink = $data['reset_link'];
        $minutes = $data['minutes'] ?? 60;
        $locale = $data['locale'] ?? 'tr';

        $subject = $locale === 'tr'
            ? 'Octopus AI — Şifre Sıfırlama Talebi'
            : 'Octopus AI — Password Reset Request';

        $preheader = $locale === 'tr'
            ? 'Şifrenizi sıfırlamak için bağlantıya tıklayın'
            : 'Click the link to reset your password';

        $body = $this->buildEmailHtml([
            'company' => null,
            'headline' => $locale === 'tr' ? 'Şifre Sıfırlama' : 'Password Reset',
            'headline_icon' => '🔐',
            'greeting' => $locale === 'tr' ? "Merhaba {$name}," : "Hello {$name},",
            'message' => $locale === 'tr'
                ? 'TalentQX hesabınız için şifre sıfırlama talebi aldık. Şifrenizi sıfırlamak için aşağıdaki butona tıklayın.'
                : 'We received a password reset request for your TalentQX account. Click the button below to reset your password.',
            'details' => [],
            'cta_text' => $locale === 'tr' ? 'Şifremi Sıfırla' : 'Reset Password',
            'cta_url' => $resetLink,
            'footer_note' => $locale === 'tr'
                ? "Bu bağlantı <strong>{$minutes} dakika</strong> boyunca geçerlidir. Eğer bu talebi siz yapmadıysanız, bu e-postayı dikkate almayın."
                : "This link is valid for <strong>{$minutes} minutes</strong>. If you didn't request this, please ignore this email.",
            'locale' => $locale,
            'preheader' => $preheader,
        ]);

        return [
            'subject' => $subject,
            'body' => $body,
            'preheader' => $preheader,
        ];
    }

    /**
     * Get email subject with company branding.
     */
    private function getSubject(string $type, string $companyName, string $locale, ?string $jobTitle = null, ?string $roleCode = null): string
    {
        // Build position suffix: prefer role_code label, fallback to jobTitle
        $positionSuffix = '';
        if ($roleCode) {
            $roleLabel = str_replace('_', ' ', ucfirst($roleCode));
            $positionSuffix = " — {$roleLabel}";
        } elseif ($jobTitle && $jobTitle !== 'Pozisyon') {
            $positionSuffix = " — {$jobTitle}";
        }

        $subjects = [
            'application_received' => [
                'tr' => "Octopus AI • {$companyName}{$positionSuffix} — Başvurunuz Alındı",
                'en' => "Octopus AI • {$companyName}{$positionSuffix} — Application Received",
            ],
            'interview_invitation' => [
                'tr' => "Octopus AI • {$companyName}{$positionSuffix} — Mülakat Daveti",
                'en' => "Octopus AI • {$companyName}{$positionSuffix} — Interview Invitation",
            ],
            'new_application' => [
                'tr' => "Octopus AI • {$companyName}{$positionSuffix} — Yeni Başvuru",
                'en' => "Octopus AI • {$companyName}{$positionSuffix} — New Application",
            ],
        ];

        return $subjects[$type][$locale] ?? $subjects[$type]['tr'] ?? 'Octopus AI';
    }

    /**
     * Get company name from object or array.
     */
    private function getCompanyName($company): string
    {
        if (is_object($company)) {
            return $company->name;
        }
        return $company['name'] ?? 'Şirket';
    }

    /**
     * Get company primary color.
     */
    private function getCompanyColor($company): string
    {
        if (is_object($company) && method_exists($company, 'getBrandColor')) {
            return $company->getBrandColor();
        }
        return $company['brand_primary_color'] ?? self::DEFAULT_PRIMARY_COLOR;
    }

    /**
     * Get company logo URL.
     */
    private function getCompanyLogo($company): ?string
    {
        if (is_object($company)) {
            return $company->logo_url;
        }
        return $company['logo_url'] ?? null;
    }

    /**
     * Get company initials (2 letters).
     */
    private function getCompanyInitials($company): string
    {
        if (is_object($company) && method_exists($company, 'getInitials')) {
            return $company->getInitials();
        }

        $name = is_object($company) ? $company->name : ($company['name'] ?? 'TX');
        $words = preg_split('/\s+/', trim($name));

        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($name, 0, 2));
    }

    /**
     * Build the complete HTML email.
     */
    private function buildEmailHtml(array $params): string
    {
        $company = $params['company'];
        $headline = $params['headline'];
        $headlineIcon = $params['headline_icon'] ?? '';
        $greeting = $params['greeting'];
        $message = $params['message'];
        $details = $params['details'];
        $ctaText = $params['cta_text'];
        $ctaUrl = $params['cta_url'];
        $footerNote = $params['footer_note'];
        $locale = $params['locale'];
        $preheader = $params['preheader'];

        // Company branding
        $companyName = $company ? $this->getCompanyName($company) : 'Octopus AI';
        $companyLogo = $company ? $this->getCompanyLogo($company) : null;
        $companyInitials = $company ? $this->getCompanyInitials($company) : 'OA';
        $primaryColor = $company ? $this->getCompanyColor($company) : self::DEFAULT_PRIMARY_COLOR;

        // Generate gradient end color
        $gradientEnd = $this->adjustColor($primaryColor, -30);

        // Header branding - Logo or Initials Badge
        if ($companyLogo) {
            $brandingHtml = <<<HTML
            <img src="{$companyLogo}" alt="{$companyName}" style="max-height: 48px; max-width: 180px; display: block; margin: 0 auto;">
            <p style="margin: 12px 0 0; font-size: 14px; color: rgba(255,255,255,0.9); font-weight: 500;">{$companyName}</p>
HTML;
        } else {
            $brandingHtml = <<<HTML
            <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 16px; display: inline-block; line-height: 64px; font-size: 26px; font-weight: 700; color: white; letter-spacing: 1px; margin: 0 auto;">{$companyInitials}</div>
            <p style="margin: 12px 0 0; font-size: 14px; color: rgba(255,255,255,0.9); font-weight: 500;">{$companyName}</p>
HTML;
        }

        // Details card
        $detailsHtml = '';
        if (!empty($details)) {
            $detailRows = '';
            foreach ($details as $detail) {
                if (!empty($detail['value'])) {
                    $detailRows .= <<<HTML
                    <tr>
                        <td style="padding: 12px 16px; color: #6b7280; font-size: 14px; border-bottom: 1px solid #f3f4f6; width: 40%;">{$detail['label']}</td>
                        <td style="padding: 12px 16px; font-weight: 600; color: #1f2937; border-bottom: 1px solid #f3f4f6;">{$detail['value']}</td>
                    </tr>
HTML;
                }
            }
            if ($detailRows) {
                $detailsHtml = <<<HTML
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background: #f9fafb; border-radius: 12px; overflow: hidden; margin: 24px 0;">
                    {$detailRows}
                </table>
HTML;
            }
        }

        // CTA button
        $ctaHtml = '';
        if ($ctaText && $ctaUrl) {
            $ctaHtml = <<<HTML
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td align="center" style="padding: 32px 0;">
                        <a href="{$ctaUrl}" style="display: inline-block; background: linear-gradient(135deg, {$primaryColor} 0%, {$gradientEnd} 100%); color: #ffffff; padding: 16px 40px; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 14px rgba(0,0,0,0.2);">{$ctaText}</a>
                    </td>
                </tr>
            </table>
HTML;
        }

        // Footer texts
        $privacyUrl = $locale === 'tr' ? 'https://talentqx.com/tr/privacy' : 'https://talentqx.com/en/privacy';
        $privacyText = $locale === 'tr' ? 'Gizlilik Politikası' : 'Privacy Policy';
        $securityText = $locale === 'tr' ? 'Verileriniz güvende' : 'Your data is secure';
        $sentOnBehalf = $locale === 'tr'
            ? "Bu e-posta <strong>{$companyName}</strong> adına Octopus AI altyapısı kullanılarak gönderilmiştir."
            : "This email was sent on behalf of <strong>{$companyName}</strong> using Octopus AI infrastructure.";

        return <<<HTML
<!DOCTYPE html>
<html lang="{$locale}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{$headline}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <!-- Preheader -->
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        {$preheader}
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <!-- Email wrapper -->
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 40px 16px;">

                <!-- Main container -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">

                    <!-- Header with branding -->
                    <tr>
                        <td style="background: linear-gradient(135deg, {$primaryColor} 0%, {$gradientEnd} 100%); padding: 40px 32px; text-align: center;">
                            {$brandingHtml}
                        </td>
                    </tr>

                    <!-- Headline badge -->
                    <tr>
                        <td style="padding: 32px 32px 0; text-align: center;">
                            <div style="display: inline-block; background: linear-gradient(135deg, {$primaryColor}15 0%, {$primaryColor}25 100%); border-radius: 50px; padding: 12px 24px;">
                                <span style="font-size: 18px; font-weight: 600; color: {$primaryColor};">{$headlineIcon} {$headline}</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 24px 32px 32px;">
                            <p style="margin: 0 0 16px; font-size: 16px; line-height: 1.7; color: #374151;">{$greeting}</p>
                            <p style="margin: 0 0 8px; font-size: 16px; line-height: 1.7; color: #374151;">{$message}</p>

                            {$detailsHtml}

                            {$ctaHtml}

                            <p style="margin: 24px 0 0; font-size: 14px; line-height: 1.6; color: #6b7280;">{$footerNote}</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 24px 32px; border-top: 1px solid #e5e7eb;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="text-align: center;">
                                        <p style="margin: 0 0 8px; font-size: 13px; color: #6b7280;">
                                            🔒 {$securityText} &nbsp;•&nbsp; <a href="{$privacyUrl}" style="color: {$primaryColor}; text-decoration: none;">{$privacyText}</a>
                                        </p>
                                        <p style="margin: 0; font-size: 12px; color: #9ca3af;">
                                            {$sentOnBehalf}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <!-- Powered by -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px;">
                    <tr>
                        <td style="padding: 24px 0; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #9ca3af;">
                                Powered by <a href="https://octopus-ai.net" style="color: {$primaryColor}; text-decoration: none; font-weight: 500;">Octopus AI</a> — AI-Powered Interview Platform
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Adjust color brightness.
     */
    private function adjustColor(string $hex, int $steps): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return self::DEFAULT_PRIMARY_COLOR;
        }

        $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $steps));
        $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $steps));
        $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $steps));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
