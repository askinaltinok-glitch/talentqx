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

        <p style="color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px;">{{ $companyName }} firmasına başvurunuz alınmıştır. Devam etmek için aşağıdaki doğrulama kodunu giriniz.</p>

        <div style="background: #f0f7ff; border: 2px solid #667eea; border-radius: 12px; padding: 24px; margin: 24px 0; text-align: center;">
            <p style="font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #4c1d95; font-family: 'Courier New', monospace; margin: 0;">{{ $code }}</p>
            <p style="font-size: 13px; color: #64748b; margin: 8px 0 0;">Doğrulama Kodunuz</p>
        </div>

        <div style="background: #fefce8; border-left: 4px solid #eab308; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
            <p style="margin: 4px 0; color: #713f12; font-size: 14px;"><strong>Bu kod 10 dakika içerisinde geçerlidir.</strong></p>
            <p style="margin: 4px 0; color: #713f12; font-size: 14px;">Bu kodu kimseyle paylaşmayınız.</p>
        </div>

        <p style="color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px;">Eğer bu başvuruyu siz yapmadıysanız bu e-postayı dikkate almayınız.</p>
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
