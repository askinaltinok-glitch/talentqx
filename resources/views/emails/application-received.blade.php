<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; background: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: linear-gradient(135deg, #0f4c81 0%, #1a6bb5 100%); padding: 30px 40px; text-align: center; }
        .header img { height: 40px; }
        .header h1 { color: #ffffff; font-size: 18px; margin: 12px 0 0; font-weight: 500; letter-spacing: 0.5px; }
        .body { padding: 40px; }
        .body h2 { color: #0f4c81; font-size: 22px; margin: 0 0 20px; }
        .body p { color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
        .info-box { background: #f0f7ff; border-left: 4px solid #0f4c81; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0; }
        .info-box p { margin: 4px 0; color: #2d3748; font-size: 14px; }
        .info-box strong { color: #0f4c81; }
        .steps { margin: 24px 0; }
        .step { display: flex; align-items: flex-start; margin-bottom: 16px; }
        .step-num { background: #0f4c81; color: #fff; width: 28px; height: 28px; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 600; flex-shrink: 0; margin-right: 14px; }
        .step-text { color: #4a5568; font-size: 14px; line-height: 1.5; padding-top: 3px; }
        .footer { background: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #94a3b8; font-size: 12px; margin: 4px 0; }
        .footer a { color: #0f4c81; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        @include('emails.partials.logo')
        <h1 style="font-size: 24px; font-weight: 700; letter-spacing: 1px;">{{ $brand['name'] }}</h1>
        <h1>{{ strtoupper($brand['tagline']) }}</h1>
    </div>

    <div class="body">
        @if($locale === 'tr')
            <h2>Merhaba {{ $candidate->first_name }},</h2>
            <p>Başvurunuz başarıyla alınmıştır. Denizcilik sektöründeki kariyer yolculuğunuzda sizi değerlendirmekten mutluluk duyacağız.</p>

            <div class="info-box">
                <p><strong>Ad Soyad:</strong> {{ $candidate->first_name }} {{ $candidate->last_name }}</p>
                <p><strong>E-posta:</strong> {{ $candidate->email }}</p>
                <p><strong>Pozisyon:</strong> {{ $candidate->source_meta['rank'] ?? '—' }}</p>
                <p><strong>Başvuru Tarihi:</strong> {{ now()->format('d.m.Y H:i') }}</p>
            </div>

            <p><strong>Sonraki adımlar:</strong></p>
            <div class="steps">
                <div class="step"><div class="step-num">1</div><div class="step-text">Yapay zeka destekli mülakat davetiniz kısa süre içinde gönderilecektir.</div></div>
                <div class="step"><div class="step-num">2</div><div class="step-text">Mülakatı tamamladığınızda, yetkinlikleriniz otomatik olarak değerlendirilecektir.</div></div>
                <div class="step"><div class="step-num">3</div><div class="step-text">Değerlendirme sonucuna göre yetenek havuzumuza dahil edileceksiniz.</div></div>
            </div>

            <p>Herhangi bir sorunuz olursa <a href="mailto:{{ $brand['support_email'] }}" style="color: #0f4c81;">{{ $brand['support_email'] }}</a> adresinden bize ulaşabilirsiniz.</p>

        @elseif($locale === 'ru')
            <h2>Здравствуйте, {{ $candidate->first_name }}!</h2>
            <p>Ваша заявка успешно получена. Мы будем рады оценить вас в рамках вашего карьерного пути в морской отрасли.</p>

            <div class="info-box">
                <p><strong>ФИО:</strong> {{ $candidate->first_name }} {{ $candidate->last_name }}</p>
                <p><strong>Эл. почта:</strong> {{ $candidate->email }}</p>
                <p><strong>Должность:</strong> {{ $candidate->source_meta['rank'] ?? '—' }}</p>
                <p><strong>Дата подачи:</strong> {{ now()->format('d.m.Y H:i') }}</p>
            </div>

            <p><strong>Следующие шаги:</strong></p>
            <div class="steps">
                <div class="step"><div class="step-num">1</div><div class="step-text">Приглашение на собеседование с использованием ИИ будет отправлено в ближайшее время.</div></div>
                <div class="step"><div class="step-num">2</div><div class="step-text">После завершения собеседования ваши компетенции будут автоматически оценены.</div></div>
                <div class="step"><div class="step-num">3</div><div class="step-text">По результатам оценки вы будете добавлены в наш кадровый резерв.</div></div>
            </div>

            <p>Если у вас есть вопросы, свяжитесь с нами: <a href="mailto:{{ $brand['support_email'] }}" style="color: #0f4c81;">{{ $brand['support_email'] }}</a></p>

        @elseif($locale === 'az')
            <h2>Salam {{ $candidate->first_name }},</h2>
            <p>Müraciətiniz uğurla qəbul edilmişdir. Dənizçilik sektorundakı karyera yolunuzda sizi qiymətləndirməkdən məmnun olacağıq.</p>

            <div class="info-box">
                <p><strong>Ad Soyad:</strong> {{ $candidate->first_name }} {{ $candidate->last_name }}</p>
                <p><strong>E-poçt:</strong> {{ $candidate->email }}</p>
                <p><strong>Vəzifə:</strong> {{ $candidate->source_meta['rank'] ?? '—' }}</p>
                <p><strong>Müraciət Tarixi:</strong> {{ now()->format('d.m.Y H:i') }}</p>
            </div>

            <p><strong>Növbəti addımlar:</strong></p>
            <div class="steps">
                <div class="step"><div class="step-num">1</div><div class="step-text">Süni intellekt dəstəkli müsahibə dəvətiniz qısa müddət ərzində göndəriləcəkdir.</div></div>
                <div class="step"><div class="step-num">2</div><div class="step-text">Müsahibəni tamamladığınızda bacarıqlarınız avtomatik olaraq qiymətləndiriləcəkdir.</div></div>
                <div class="step"><div class="step-num">3</div><div class="step-text">Qiymətləndirmə nəticəsinə əsasən istedad hovuzumuza daxil ediləcəksiniz.</div></div>
            </div>

            <p>Sualınız varsa <a href="mailto:{{ $brand['support_email'] }}" style="color: #0f4c81;">{{ $brand['support_email'] }}</a> ünvanından bizimlə əlaqə saxlaya bilərsiniz.</p>

        @else
            <h2>Hello {{ $candidate->first_name }},</h2>
            <p>Your application has been received successfully. We look forward to evaluating you on your career journey in the maritime industry.</p>

            <div class="info-box">
                <p><strong>Full Name:</strong> {{ $candidate->first_name }} {{ $candidate->last_name }}</p>
                <p><strong>Email:</strong> {{ $candidate->email }}</p>
                <p><strong>Position:</strong> {{ $candidate->source_meta['rank'] ?? '—' }}</p>
                <p><strong>Application Date:</strong> {{ now()->format('d.m.Y H:i') }}</p>
            </div>

            <p><strong>Next steps:</strong></p>
            <div class="steps">
                <div class="step"><div class="step-num">1</div><div class="step-text">Your AI-powered interview invitation will be sent shortly.</div></div>
                <div class="step"><div class="step-num">2</div><div class="step-text">Once you complete the interview, your competencies will be automatically evaluated.</div></div>
                <div class="step"><div class="step-num">3</div><div class="step-text">Based on the assessment results, you will be added to our talent pool.</div></div>
            </div>

            <p>If you have any questions, contact us at <a href="mailto:{{ $brand['support_email'] }}" style="color: #0f4c81;">{{ $brand['support_email'] }}</a></p>
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
