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

        $companyName = $this->getCompanyName($company);
        $branchName = is_object($branch) ? $branch->name : ($branch['name'] ?? '');
        $jobTitle = is_object($job) ? $job->title : ($job['title'] ?? 'Pozisyon');
        $candidateName = is_object($candidate) ? trim($candidate->first_name . ' ' . $candidate->last_name) : ($candidate['name'] ?? 'Aday');
        $applicationId = is_object($candidate) ? substr($candidate->id, 0, 8) : substr($candidate['id'] ?? '', 0, 8);

        $subject = $this->getSubject('application_received', $companyName, $locale);
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
     * Render interview invitation email.
     */
    public function renderInterviewInvitation(array $data): array
    {
        $company = $data['company'];
        $branch = $data['branch'] ?? null;
        $job = $data['job'] ?? null;
        $candidate = $data['candidate'];
        $interviewUrl = $data['interview_url'];
        $expiresAt = $data['expires_at'] ?? null;
        $locale = $data['locale'] ?? 'tr';

        $companyName = $this->getCompanyName($company);
        $branchName = is_object($branch) ? $branch->name : ($branch['name'] ?? '');
        $jobTitle = is_object($job) ? $job->title : ($job['title'] ?? 'Pozisyon');
        $roleCode = is_object($job) ? $job->role_code : ($job['role_code'] ?? '');
        $candidateName = is_object($candidate) ? trim($candidate->first_name . ' ' . $candidate->last_name) : ($candidate['name'] ?? 'Aday');

        $subject = $this->getSubject('interview_invitation', $companyName, $locale);
        $preheader = $locale === 'tr'
            ? "{$candidateName} - {$jobTitle} mülakatına davetlisiniz"
            : "{$candidateName} - You're invited to interview for {$jobTitle}";

        $expiryText = '';
        if ($expiresAt) {
            $expiryDate = is_string($expiresAt) ? $expiresAt : $expiresAt->format('d.m.Y H:i');
            $expiryText = $locale === 'tr'
                ? "Bu bağlantı <strong>{$expiryDate}</strong> tarihine kadar geçerlidir."
                : "This link is valid until <strong>{$expiryDate}</strong>.";
        }

        $body = $this->buildEmailHtml([
            'company' => $company,
            'headline' => $locale === 'tr' ? 'Mülakata Davetlisiniz!' : 'Interview Invitation!',
            'headline_icon' => '🎯',
            'greeting' => $locale === 'tr' ? "Merhaba {$candidateName}," : "Hello {$candidateName},",
            'message' => $locale === 'tr'
                ? "<strong>{$jobTitle}</strong> pozisyonu için başvurunuz değerlendirildi ve sizi online mülakata davet ediyoruz!"
                : "Your application for the <strong>{$jobTitle}</strong> position has been evaluated and we'd like to invite you to an online interview!",
            'details' => [
                ['label' => $locale === 'tr' ? 'Şirket' : 'Company', 'value' => $companyName],
                ['label' => $locale === 'tr' ? 'Şube' : 'Branch', 'value' => $branchName],
                ['label' => $locale === 'tr' ? 'Pozisyon' : 'Position', 'value' => $jobTitle],
                ['label' => $locale === 'tr' ? 'Rol Kodu' : 'Role Code', 'value' => $roleCode],
            ],
            'cta_text' => $locale === 'tr' ? 'Mülakata Başla' : 'Start Interview',
            'cta_url' => $interviewUrl,
            'footer_note' => $expiryText . ($expiryText ? '<br><br>' : '') . ($locale === 'tr'
                ? 'Mülakat yaklaşık 15-20 dakika sürecektir. Sessiz bir ortamda, kamera ve mikrofon erişimi olan bir cihazdan katılmanızı öneririz.'
                : 'The interview will take approximately 15-20 minutes. We recommend joining from a quiet environment with a device that has camera and microphone access.'),
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
            ? 'TalentQX — Şifre Sıfırlama Talebi'
            : 'TalentQX — Password Reset Request';

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
    private function getSubject(string $type, string $companyName, string $locale): string
    {
        $subjects = [
            'application_received' => [
                'tr' => "TalentQX • {$companyName} — Başvurunuz Alındı",
                'en' => "TalentQX • {$companyName} — Application Received",
            ],
            'interview_invitation' => [
                'tr' => "TalentQX • {$companyName} — Mülakat Daveti",
                'en' => "TalentQX • {$companyName} — Interview Invitation",
            ],
        ];

        return $subjects[$type][$locale] ?? $subjects[$type]['tr'] ?? 'TalentQX';
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
        $companyName = $company ? $this->getCompanyName($company) : 'TalentQX';
        $companyLogo = $company ? $this->getCompanyLogo($company) : null;
        $companyInitials = $company ? $this->getCompanyInitials($company) : 'TX';
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
            ? "Bu e-posta <strong>{$companyName}</strong> adına TalentQX altyapısı kullanılarak gönderilmiştir."
            : "This email was sent on behalf of <strong>{$companyName}</strong> using TalentQX infrastructure.";

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
                                Powered by <a href="https://talentqx.com" style="color: {$primaryColor}; text-decoration: none; font-weight: 500;">TalentQX</a> — AI Destekli Mülakat Platformu
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
