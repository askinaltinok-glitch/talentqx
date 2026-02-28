<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; background: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); padding: 30px 40px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 20px; margin: 12px 0 0; font-weight: 600; }
        .body { padding: 40px; }
        .body h2 { color: #0f4c81; font-size: 20px; margin: 0 0 16px; }
        .body p { color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
        .cta { display: block; background: #0d9488; color: #ffffff !important; text-decoration: none; text-align: center; padding: 14px 32px; border-radius: 8px; font-size: 16px; font-weight: 600; margin: 28px 0; }
        .disclaimer { background: #f8fafc; border-left: 4px solid #e2e8f0; padding: 14px 18px; margin: 24px 0; border-radius: 0 6px 6px 0; }
        .disclaimer p { color: #94a3b8; font-size: 12px; line-height: 1.6; margin: 0; font-style: italic; }
        .footer { background: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #94a3b8; font-size: 12px; margin: 4px 0; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        @include('emails.partials.logo')
        <h1>TalentQX OrgHealth</h1>
    </div>

    <div class="body">
        @if($locale === 'tr')
            <h2>Merhaba {{ $employeeName }},</h2>
            <p>Kurumunuz adına bir <strong>Kültür Profili Değerlendirmesi</strong> daveti aldınız.</p>
            <p>Bu kısa anket, mevcut ve tercih ettiğiniz organizasyon kültürünü anlamaya yardımcı olur. Tamamlanması yaklaşık 10 dakika sürer.</p>

            <a href="{{ $magicUrl }}" class="cta">Değerlendirmeye Başla</a>

            <div class="disclaimer">
                <p>Bu değerlendirme bireysel performans analizi değildir. Sonuçlar yalnızca toplu kültür analizi için kullanılır.</p>
            </div>
        @else
            <h2>Hello {{ $employeeName }},</h2>
            <p>You have been invited to complete a <strong>Culture Profile Assessment</strong> on behalf of your organization.</p>
            <p>This short survey helps understand your current and preferred organizational culture. It takes approximately 10 minutes to complete.</p>

            <a href="{{ $magicUrl }}" class="cta">Start Assessment</a>

            <div class="disclaimer">
                <p>This assessment is not an individual performance evaluation. Results are used only for aggregate culture analysis.</p>
            </div>
        @endif
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} TalentQX &mdash; OrgHealth</p>
    </div>
</div>
</body>
</html>
