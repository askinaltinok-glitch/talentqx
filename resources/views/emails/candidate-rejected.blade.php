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

        <p style="color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px;">{{ $companyName }} firmasının {{ $positionTitle }} pozisyonu için yaptığınız başvuruyu dikkatle incelediğimizi bilmenizi isteriz.</p>

        <div style="background: #fef3f2; border-left: 4px solid #f87171; padding: 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
            <p style="margin: 4px 0; color: #991b1b; font-size: 15px;"><strong>Bu pozisyon için olumlu dönüş yapılamadığını üzülerek bildiririz.</strong></p>
        </div>

        <p style="color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px;">Değerlendirme sürecinde gösterdiğiniz ilgi ve ayırdığınız zaman için içtenlikle teşekkür ederiz. Bu sonuç yalnızca mevcut pozisyon ve koşullarla ilgili olup, mesleki yetkinliklerinizin değerini azaltmamaktadır.</p>

        <div style="background: #f0f7ff; border-left: 4px solid #667eea; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
            <p style="margin: 4px 0; color: #2d3748; font-size: 14px;"><strong style="color: #4c1d95;">Pozisyon:</strong> {{ $positionTitle }}</p>
            <p style="margin: 4px 0; color: #2d3748; font-size: 14px;"><strong style="color: #4c1d95;">Firma:</strong> {{ $companyName }}</p>
        </div>

        <div style="background: #f0fdf4; border-left: 4px solid #22c55e; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
            <p style="margin: 4px 0; color: #166534; font-size: 14px;"><strong>Gelecek Fırsatlar</strong></p>
            <p style="margin: 4px 0; color: #166534; font-size: 14px;">Yeni pozisyonlar açıldığında platformumuz üzerinden tekrar başvurmanızı memnuniyetle karşılarız. Profiliniz sistemimizde kayıtlı olup uygun fırsatlarda değerlendirilecektir.</p>
        </div>

        <p style="color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px;">Kariyer yolculuğunuzda başarılar dileriz.</p>
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
