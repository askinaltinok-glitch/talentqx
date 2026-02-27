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

        <p style="color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px;">{{ $brandName }} platformuna hoş geldiniz!</p>

        <div style="background: #f0fdf4; border-left: 4px solid #22c55e; padding: 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
            <p style="margin: 4px 0; color: #166534; font-size: 15px;"><strong>{{ $companyName }} firmasının mülakatı başarıyla tamamlanmıştır.</strong></p>
        </div>

        <p style="color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px;">Yanıtlarınız kaydedilmiş olup yapay zekâ destekli değerlendirme sürecine alınmıştır.</p>

        <div style="background: #f0f7ff; border-left: 4px solid #667eea; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
            <p style="margin: 4px 0; color: #2d3748; font-size: 14px;"><strong style="color: #4c1d95;">Pozisyon:</strong> {{ $positionTitle }}</p>
            <p style="margin: 4px 0; color: #2d3748; font-size: 14px;"><strong style="color: #4c1d95;">Firma:</strong> {{ $companyName }}</p>
            <p style="margin: 4px 0; color: #2d3748; font-size: 14px;"><strong style="color: #4c1d95;">Tamamlanma Tarihi:</strong> {{ now()->format('d.m.Y H:i') }}</p>
        </div>

        <div style="background: #fefce8; border-left: 4px solid #eab308; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
            <p style="margin: 4px 0; color: #713f12; font-size: 14px;"><strong>Sonraki Adımlar</strong></p>
            <p style="margin: 4px 0; color: #713f12; font-size: 14px;">Değerlendirme sonuçlarınız incelendikten sonra size en kısa sürede dönüş yapılacaktır. Bu süreçte herhangi bir işlem yapmanıza gerek yoktur.</p>
        </div>

        <p style="color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px;">İlginiz ve başvurunuz için teşekkür ederiz.</p>
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
