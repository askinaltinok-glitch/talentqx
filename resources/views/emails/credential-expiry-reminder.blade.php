<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; background: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: linear-gradient(135deg, #0f4c81 0%, #1a6bb5 100%); padding: 30px 40px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 18px; margin: 12px 0 0; font-weight: 500; letter-spacing: 0.5px; }
        .body { padding: 40px; }
        .body h2 { color: #0f4c81; font-size: 22px; margin: 0 0 20px; }
        .body p { color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
        .warning-box { background: #fff8f0; border-left: 4px solid #ed8936; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0; }
        .warning-box p { margin: 4px 0; color: #2d3748; font-size: 14px; }
        .warning-box strong { color: #c05621; }
        .urgent-box { background: #fff5f5; border-left: 4px solid #e53e3e; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0; }
        .urgent-box p { margin: 4px 0; color: #2d3748; font-size: 14px; }
        .urgent-box strong { color: #c53030; }
        .info-box { background: #f0f7ff; border-left: 4px solid #0f4c81; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0; }
        .info-box p { margin: 4px 0; color: #2d3748; font-size: 14px; }
        .info-box strong { color: #0f4c81; }
        .footer { background: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #94a3b8; font-size: 12px; margin: 4px 0; }
        .footer a { color: #0f4c81; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1 style="font-size: 24px; font-weight: 700; letter-spacing: 1px;">Octopus AI</h1>
        <h1>MARITIME TALENT INTELLIGENCE PLATFORM</h1>
    </div>

    <div class="body">
        @if($locale === 'tr')
            <h2>Merhaba {{ $candidate->first_name }},</h2>

            @if($daysLeft <= 7)
                <div class="urgent-box">
                    <p><strong>{{ $credentialType }}</strong> belgenizin suresinin dolmasina <strong>{{ $daysLeft }} gun</strong> kaldi!</p>
                    <p><strong>Son Gecerlilik:</strong> {{ $expiryDate }}</p>
                </div>
                <p>Belgenizi en kisa surede yenilemenizi onemle tavsiye ederiz. Suresi dolmus belgeler is basvurularinizi olumsuz etkileyebilir.</p>
            @else
                <div class="warning-box">
                    <p><strong>{{ $credentialType }}</strong> belgenizin suresi <strong>{{ $expiryDate }}</strong> tarihinde sona erecektir.</p>
                    <p>Kalan sure: <strong>{{ $daysLeft }} gun</strong></p>
                </div>
                <p>Belge yenileme islemleri zaman alabilir. Simdiden hazirliklara baslamanizi oneririz.</p>
            @endif

            <div class="info-box">
                <p><strong>Belge Turu:</strong> {{ $credentialType }}</p>
                @if($credential->credential_number)
                    <p><strong>Belge No:</strong> {{ $credential->credential_number }}</p>
                @endif
                @if($credential->issuer)
                    <p><strong>Veren Kurum:</strong> {{ $credential->issuer }}</p>
                @endif
                <p><strong>Son Gecerlilik:</strong> {{ $expiryDate }}</p>
            </div>

            <p>Belgenizi yeniledikten sonra platformumuzdaki bilgilerinizi guncellemeyi unutmayin.</p>

        @elseif($locale === 'ru')
            <h2>{{ $candidate->first_name }},</h2>

            @if($daysLeft <= 7)
                <div class="urgent-box">
                    <p>Cрок действия документа <strong>{{ $credentialType }}</strong> истекает через <strong>{{ $daysLeft }} дн.</strong></p>
                    <p><strong>Дата истечения:</strong> {{ $expiryDate }}</p>
                </div>
                <p>Настоятельно рекомендуем обновить документ как можно скорее. Просроченные документы могут негативно повлиять на ваши заявки.</p>
            @else
                <div class="warning-box">
                    <p>Срок действия документа <strong>{{ $credentialType }}</strong> истекает <strong>{{ $expiryDate }}</strong>.</p>
                    <p>Осталось: <strong>{{ $daysLeft }} дн.</strong></p>
                </div>
                <p>Обновление документов может занять время. Рекомендуем начать подготовку заранее.</p>
            @endif

            <div class="info-box">
                <p><strong>Тип документа:</strong> {{ $credentialType }}</p>
                @if($credential->credential_number)
                    <p><strong>Номер:</strong> {{ $credential->credential_number }}</p>
                @endif
                @if($credential->issuer)
                    <p><strong>Выдан:</strong> {{ $credential->issuer }}</p>
                @endif
                <p><strong>Дата истечения:</strong> {{ $expiryDate }}</p>
            </div>

            <p>После обновления документа не забудьте обновить информацию на платформе.</p>

        @elseif($locale === 'az')
            <h2>Salam {{ $candidate->first_name }},</h2>

            @if($daysLeft <= 7)
                <div class="urgent-box">
                    <p><strong>{{ $credentialType }}</strong> senedinizin muddetinin bitmesine <strong>{{ $daysLeft }} gun</strong> qaldi!</p>
                    <p><strong>Son tarix:</strong> {{ $expiryDate }}</p>
                </div>
                <p>Senedinizi en qisa zamanda yenilemeyinizi tovsiye edirik. Muddeti bitmis senedler is muracietlerinize menfi tesir gostere biler.</p>
            @else
                <div class="warning-box">
                    <p><strong>{{ $credentialType }}</strong> senedinizin muddeti <strong>{{ $expiryDate }}</strong> tarixinde basa catacaq.</p>
                    <p>Qalan muddet: <strong>{{ $daysLeft }} gun</strong></p>
                </div>
                <p>Sened yenilenme prosesi vaxt ala biler. Indiden hazirliga baslamaginizi tovsiye edirik.</p>
            @endif

            <div class="info-box">
                <p><strong>Sened novu:</strong> {{ $credentialType }}</p>
                @if($credential->credential_number)
                    <p><strong>Sened nomresi:</strong> {{ $credential->credential_number }}</p>
                @endif
                @if($credential->issuer)
                    <p><strong>Veren qurum:</strong> {{ $credential->issuer }}</p>
                @endif
                <p><strong>Son tarix:</strong> {{ $expiryDate }}</p>
            </div>

            <p>Senedinizi yeniledikden sonra platformdaki melumatlarinizi yenilemeyinizi unutmayin.</p>

        @else
            {{-- English (default) --}}
            <h2>Hello {{ $candidate->first_name }},</h2>

            @if($daysLeft <= 7)
                <div class="urgent-box">
                    <p>Your <strong>{{ $credentialType }}</strong> expires in <strong>{{ $daysLeft }} day{{ $daysLeft !== 1 ? 's' : '' }}</strong>!</p>
                    <p><strong>Expiry Date:</strong> {{ $expiryDate }}</p>
                </div>
                <p>We strongly recommend renewing your credential as soon as possible. Expired credentials may affect your job applications.</p>
            @else
                <div class="warning-box">
                    <p>Your <strong>{{ $credentialType }}</strong> will expire on <strong>{{ $expiryDate }}</strong>.</p>
                    <p>Time remaining: <strong>{{ $daysLeft }} days</strong></p>
                </div>
                <p>Credential renewal can take time. We recommend starting the process early.</p>
            @endif

            <div class="info-box">
                <p><strong>Credential Type:</strong> {{ $credentialType }}</p>
                @if($credential->credential_number)
                    <p><strong>Number:</strong> {{ $credential->credential_number }}</p>
                @endif
                @if($credential->issuer)
                    <p><strong>Issuer:</strong> {{ $credential->issuer }}</p>
                @endif
                <p><strong>Expiry Date:</strong> {{ $expiryDate }}</p>
            </div>

            <p>After renewing your credential, don't forget to update your information on the platform.</p>
        @endif
    </div>

    <div class="footer">
        <p>Octopus AI &mdash; Maritime Talent Intelligence Platform</p>
        <p><a href="https://octopus-ai.net">octopus-ai.net</a></p>
    </div>
</div>
</body>
</html>
