<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
<div style="max-width: 600px; margin: 0 auto; background: #ffffff;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 40px; text-align: center;">
        @include('emails.partials.logo')
        <h1 style="color: #ffffff; font-size: 22px; margin: 0; font-weight: 600;">{{ $companyName }}</h1>
    </div>

    <div style="padding: 40px;">
        <h2 style="color: #4c1d95; font-size: 20px; margin: 0 0 20px;">Merhaba {{ $candidate->first_name ?? 'Aday' }},</h2>

        <div style="background: #fffbeb; border: 2px solid #f59e0b; border-radius: 12px; padding: 24px; margin: 24px 0; text-align: center;">
            <p style="font-size: 18px; font-weight: 700; color: #92400e; margin: 0 0 8px;">Mülakatınız 1 saat sonra başlıyor!</p>
            <p style="font-size: 20px; font-weight: 700; color: #78350f; margin: 0;">{{ $scheduledAt->format('d.m.Y H:i') }}</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ $interviewUrl }}" style="display: inline-block; background: #667eea; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 16px; font-weight: 600; margin: 24px 0;">Mülakatı Başlat</a>
        </div>

        <div style="background: #f0f7ff; border-left: 4px solid #667eea; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
            <p style="margin: 4px 0; color: #2d3748; font-size: 14px;"><strong>Pozisyon:</strong> {{ $job->title ?? 'Belirtilmemiş' }}</p>
            <p style="margin: 4px 0; color: #2d3748; font-size: 14px;"><strong>Firma:</strong> {{ $companyName }}</p>
        </div>

        <p style="color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px;">Sessiz ve rahat bir ortamda olduğunuzdan emin olunuz. Mülakat tamamlanana kadar sayfayı kapatmayınız.</p>
    </div>

    <div style="background: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0;">
        <p style="color: #94a3b8; font-size: 12px; margin: 4px 0;">&copy; {{ date('Y') }} {{ $companyName }} &middot; {{ $brandName }}</p>
        <p style="margin-top: 8px; font-size: 11px; color: #b0b8c4;">
            Bu e-posta otomatik olarak gönderilmiştir. Lütfen bu adrese yanıt vermeyiniz.
        </p>
    </div>
</div>
</body>
</html>
