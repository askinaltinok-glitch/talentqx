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
        .success-box { background: #f0fdf4; border-left: 4px solid #22c55e; padding: 20px; margin: 24px 0; border-radius: 0 8px 8px 0; }
        .success-box p { margin: 4px 0; color: #166534; font-size: 15px; }
        .info-box { background: #f0f7ff; border-left: 4px solid #0f4c81; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0; }
        .info-box p { margin: 4px 0; color: #2d3748; font-size: 14px; }
        .info-box strong { color: #0f4c81; }
        .next-steps { background: #fefce8; border-left: 4px solid #eab308; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0; }
        .next-steps p { margin: 4px 0; color: #713f12; font-size: 14px; }
        .footer { background: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #94a3b8; font-size: 12px; margin: 4px 0; }
        .footer a { color: #0f4c81; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1 style="font-size: 24px; font-weight: 700; letter-spacing: 1px;">{{ $brand['header_emoji'] }} {{ $brand['name'] }}</h1>
        <h1>{{ strtoupper($brand['tagline']) }}</h1>
    </div>

    <div class="body">
        @if($locale === 'tr')
            <h2>Tebrikler {{ $candidate->first_name }}!</h2>

            <div class="success-box">
                <p><strong>Mülakatınız başarıyla tamamlanmıştır.</strong></p>
            </div>

            <p>Yapay zeka destekli değerlendirme süreciniz sona ermiştir. Cevaplarınız uzman sistemimiz tarafından analiz edilmiştir.</p>

            @if($positionName)
            <div class="info-box">
                <p><strong>Pozisyon:</strong> {{ $positionName }}</p>
                <p><strong>Tamamlanma Tarihi:</strong> {{ now()->format('d.m.Y H:i') }}</p>
            </div>
            @endif

            <div class="next-steps">
                <p><strong>Sırada ne var?</strong></p>
                <p>Değerlendirme sonuçlarınız incelenmektedir. Uygun pozisyonlar için sizinle iletişime geçilecektir. Bu süreçte herhangi bir işlem yapmanıza gerek yoktur.</p>
            </div>

            <p>Sorularınız için <a href="mailto:{{ $brand['support_email'] }}" style="color: #0f4c81;">{{ $brand['support_email'] }}</a> adresinden bize ulaşabilirsiniz.</p>

        @elseif($locale === 'ru')
            <h2>Поздравляем, {{ $candidate->first_name }}!</h2>

            <div class="success-box">
                <p><strong>Ваше собеседование успешно завершено.</strong></p>
            </div>

            <p>Процесс оценки с использованием искусственного интеллекта завершён. Ваши ответы были проанализированы нашей экспертной системой.</p>

            @if($positionName)
            <div class="info-box">
                <p><strong>Должность:</strong> {{ $positionName }}</p>
                <p><strong>Дата завершения:</strong> {{ now()->format('d.m.Y H:i') }}</p>
            </div>
            @endif

            <div class="next-steps">
                <p><strong>Что дальше?</strong></p>
                <p>Результаты вашей оценки находятся на рассмотрении. С вами свяжутся при наличии подходящих вакансий. На данном этапе никаких действий от вас не требуется.</p>
            </div>

            <p>По вопросам обращайтесь: <a href="mailto:{{ $brand['support_email'] }}" style="color: #0f4c81;">{{ $brand['support_email'] }}</a></p>

        @elseif($locale === 'az')
            <h2>Təbrik edirik {{ $candidate->first_name }}!</h2>

            <div class="success-box">
                <p><strong>Müsahibəniz uğurla tamamlanmışdır.</strong></p>
            </div>

            <p>Süni intellekt dəstəkli qiymətləndirmə prosesiniz başa çatmışdır. Cavablarınız ekspert sistemimiz tərəfindən təhlil edilmişdir.</p>

            @if($positionName)
            <div class="info-box">
                <p><strong>Vəzifə:</strong> {{ $positionName }}</p>
                <p><strong>Tamamlanma Tarixi:</strong> {{ now()->format('d.m.Y H:i') }}</p>
            </div>
            @endif

            <div class="next-steps">
                <p><strong>Növbəti addım nədir?</strong></p>
                <p>Qiymətləndirmə nəticələriniz nəzərdən keçirilir. Uyğun vəzifələr üçün sizinlə əlaqə saxlanılacaq. Bu mərhələdə heç bir əməliyyat etmənizə ehtiyac yoxdur.</p>
            </div>

            <p>Suallarınız üçün <a href="mailto:{{ $brand['support_email'] }}" style="color: #0f4c81;">{{ $brand['support_email'] }}</a> ünvanından bizimlə əlaqə saxlayın.</p>

        @else
            <h2>Congratulations {{ $candidate->first_name }}!</h2>

            <div class="success-box">
                <p><strong>Your interview has been completed successfully.</strong></p>
            </div>

            <p>Your AI-powered assessment process has been completed. Your responses have been analyzed by our expert system.</p>

            @if($positionName)
            <div class="info-box">
                <p><strong>Position:</strong> {{ $positionName }}</p>
                <p><strong>Completion Date:</strong> {{ now()->format('d.m.Y H:i') }}</p>
            </div>
            @endif

            <div class="next-steps">
                <p><strong>What's next?</strong></p>
                <p>Your assessment results are being reviewed. You will be contacted for suitable positions. No action is required from you at this stage.</p>
            </div>

            <p>For questions, contact us at <a href="mailto:{{ $brand['support_email'] }}" style="color: #0f4c81;">{{ $brand['support_email'] }}</a></p>
        @endif
    </div>

    <div class="footer">
        <p><a href="{{ $brand['website_url'] }}">{{ $brand['domain'] }}</a></p>
        <p>&copy; {{ date('Y') }} {{ $brand['name'] }} — {{ $brand['tagline'] }}</p>
        <p style="margin-top: 8px; font-size: 11px; color: #b0b8c4;">
            @if($locale === 'tr') Bu e-posta otomatik olarak gönderilmiştir. Lütfen bu adrese yanıt vermeyiniz.
            @elseif($locale === 'ru') Это письмо отправлено автоматически. Пожалуйста, не отвечайте на него.
            @elseif($locale === 'az') Bu e-poçt avtomatik olaraq göndərilmişdir. Zəhmət olmasa bu ünvana cavab verməyin.
            @else This email was sent automatically. Please do not reply to this address.
            @endif
        </p>
    </div>
</div>
</body>
</html>
