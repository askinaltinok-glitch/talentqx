<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
<div style="max-width: 600px; margin: 0 auto; background: #ffffff;">
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); padding: 30px 40px; text-align: center;">
        <img src="https://talentqx.com/assets/octopus-logo-email.png" alt="Octopus AI" style="max-height: 48px; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;">
        <h1 style="color: #ffffff; font-size: 22px; margin: 0; font-weight: 600;">Octopus AI</h1>
        <p style="color: #94a3b8; font-size: 13px; margin: 6px 0 0;">Maritime Crew Assessment</p>
    </div>

    <div style="padding: 40px;">
        @php
            $greetings = [
                'en' => 'Hello',
                'tr' => 'Merhaba',
                'ru' => 'Здравствуйте',
                'az' => 'Salam',
                'fil' => 'Kumusta',
                'id' => 'Halo',
                'uk' => 'Вітаємо',
            ];
            $appReceived = [
                'en' => 'Your application has been received. Please enter the verification code below to continue to your assessment.',
                'tr' => 'Başvurunuz alınmıştır. Değerlendirmeye devam etmek için aşağıdaki doğrulama kodunu giriniz.',
                'ru' => 'Ваша заявка получена. Введите код подтверждения ниже, чтобы перейти к оценке.',
                'az' => 'Müraciətiniz qəbul edildi. Qiymətləndirməyə davam etmək üçün aşağıdakı doğrulama kodunu daxil edin.',
                'fil' => 'Natanggap na ang iyong aplikasyon. I-enter ang verification code sa ibaba para magpatuloy sa assessment.',
                'id' => 'Lamaran Anda telah diterima. Masukkan kode verifikasi di bawah ini untuk melanjutkan ke penilaian.',
                'uk' => 'Вашу заявку отримано. Введіть код підтвердження нижче, щоб перейти до оцінювання.',
            ];
            $codeLabel = [
                'en' => 'Your Verification Code',
                'tr' => 'Doğrulama Kodunuz',
                'ru' => 'Ваш код подтверждения',
                'az' => 'Doğrulama Kodunuz',
                'fil' => 'Iyong Verification Code',
                'id' => 'Kode Verifikasi Anda',
                'uk' => 'Ваш код підтвердження',
            ];
            $validFor = [
                'en' => 'This code is valid for 10 minutes.',
                'tr' => 'Bu kod 10 dakika içerisinde geçerlidir.',
                'ru' => 'Этот код действителен 10 минут.',
                'az' => 'Bu kod 10 dəqiqə ərzində keçərlidir.',
                'fil' => 'Ang code na ito ay valid sa loob ng 10 minuto.',
                'id' => 'Kode ini berlaku selama 10 menit.',
                'uk' => 'Цей код дійсний протягом 10 хвилин.',
            ];
            $doNotShare = [
                'en' => 'Do not share this code with anyone.',
                'tr' => 'Bu kodu kimseyle paylaşmayınız.',
                'ru' => 'Не сообщайте этот код никому.',
                'az' => 'Bu kodu heç kimlə paylaşmayın.',
                'fil' => 'Huwag ibahagi ang code na ito sa kahit sino.',
                'id' => 'Jangan bagikan kode ini kepada siapa pun.',
                'uk' => 'Не повідомляйте цей код нікому.',
            ];
            $ignoreLine = [
                'en' => 'If you did not apply, please disregard this email.',
                'tr' => 'Eğer bu başvuruyu siz yapmadıysanız bu e-postayı dikkate almayınız.',
                'ru' => 'Если вы не подавали заявку, проигнорируйте это письмо.',
                'az' => 'Əgər bu müraciəti siz etməmisinizsə, bu e-poçtu nəzərə almayın.',
                'fil' => 'Kung hindi kayo nag-apply, huwag pansinin ang email na ito.',
                'id' => 'Jika Anda tidak mengajukan lamaran, abaikan email ini.',
                'uk' => 'Якщо ви не подавали заявку, проігноруйте цей лист.',
            ];
            $autoEmail = [
                'en' => 'This email was sent automatically. Please do not reply to this address.',
                'tr' => 'Bu e-posta otomatik olarak gönderilmiştir. Lütfen bu adrese yanıt vermeyiniz.',
                'ru' => 'Это письмо отправлено автоматически. Пожалуйста, не отвечайте на него.',
                'az' => 'Bu e-poçt avtomatik göndərilmişdir. Zəhmət olmasa cavab verməyin.',
                'fil' => 'Awtomatikong ipinadala ang email na ito. Huwag sumagot sa address na ito.',
                'id' => 'Email ini dikirim secara otomatis. Mohon jangan membalas alamat ini.',
                'uk' => 'Цей лист надіслано автоматично. Будь ласка, не відповідайте на нього.',
            ];
            $lang = $locale ?? 'en';
        @endphp

        <h2 style="color: #1e3a5f; font-size: 20px; margin: 0 0 20px;">{{ $greetings[$lang] ?? $greetings['en'] }} {{ $candidate->first_name ?? '' }},</h2>

        <p style="color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px;">{{ $appReceived[$lang] ?? $appReceived['en'] }}</p>

        <div style="background: #f0f7ff; border: 2px solid #1e3a5f; border-radius: 12px; padding: 24px; margin: 24px 0; text-align: center;">
            <p style="font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #1e3a5f; font-family: 'Courier New', monospace; margin: 0;">{{ $code }}</p>
            <p style="font-size: 13px; color: #64748b; margin: 8px 0 0;">{{ $codeLabel[$lang] ?? $codeLabel['en'] }}</p>
        </div>

        <div style="background: #fefce8; border-left: 4px solid #eab308; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
            <p style="margin: 4px 0; color: #713f12; font-size: 14px;"><strong>{{ $validFor[$lang] ?? $validFor['en'] }}</strong></p>
            <p style="margin: 4px 0; color: #713f12; font-size: 14px;">{{ $doNotShare[$lang] ?? $doNotShare['en'] }}</p>
        </div>

        <p style="color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px;">{{ $ignoreLine[$lang] ?? $ignoreLine['en'] }}</p>
    </div>

    <div style="background: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0;">
        <p style="color: #94a3b8; font-size: 12px; margin: 4px 0;">&copy; {{ date('Y') }} Octopus AI &middot; Maritime Crew Assessment</p>
        <p style="margin-top: 8px; font-size: 11px; color: #b0b8c4;">
            {{ $autoEmail[$lang] ?? $autoEmail['en'] }}
        </p>
    </div>
</div>
</body>
</html>
