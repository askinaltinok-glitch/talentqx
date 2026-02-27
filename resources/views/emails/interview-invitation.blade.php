<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; background-color: #f4f7fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .wrapper { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #1a365d 0%, #2563eb 100%); padding: 32px 24px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 22px; margin: 0; font-weight: 600; }
        .header .emoji { font-size: 32px; display: block; margin-bottom: 12px; }
        .body { padding: 32px 24px; }
        .body h2 { color: #1a365d; font-size: 18px; margin: 0 0 16px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .info-box { background-color: #eff6ff; border-left: 4px solid #2563eb; padding: 16px; border-radius: 0 8px 8px 0; margin: 20px 0; }
        .info-box p { margin: 0; color: #1e40af; font-size: 14px; }
        .deadline-box { background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 0 8px 8px 0; margin: 20px 0; }
        .deadline-box p { margin: 0; color: #92400e; font-size: 14px; font-weight: 600; }
        .cta-wrapper { text-align: center; margin: 28px 0; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff !important; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-size: 16px; font-weight: 600; }
        .steps { margin: 20px 0; padding: 0; }
        .steps li { color: #374151; font-size: 14px; line-height: 1.8; margin-bottom: 4px; }
        .note { color: #6b7280; font-size: 13px; font-style: italic; margin-top: 20px; }
        .footer { background-color: #f9fafb; padding: 24px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { color: #6b7280; font-size: 12px; margin: 0 0 8px; }
        .footer a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        @include('emails.partials.logo')
        <h1>{{ $brand['name'] ?? 'Octopus AI' }}</h1>
    </div>

    <div class="body">
        @if($locale === 'tr')
            <h2>Merhaba {{ $candidate->first_name }},</h2>
            <p>Basvurunuz basariyla alindi. Degerlendirme surecimizin bir sonraki adimi olarak, sizi davranissal degerlendirmemizi tamamlamaya davet ediyoruz{{ $rank ? " ({$rank} pozisyonu icin)" : '' }}.</p>

            <div class="deadline-box">
                <p>Bu baglantinin son kullanma tarihi: {{ $expiresAt->format('d.m.Y') }} saat {{ $expiresAt->format('H:i') }} (Europe/Istanbul)</p>
            </div>

            <div class="info-box">
                <p><strong>Degerlendirme hakkinda:</strong> {{ $questionCount }} soruluk bu form, denizcilik deneyimlerinize dayali olarak calisma tarzinizi ve ekip uyumunuzu anlamamiza yardimci olur. Yaklasik {{ $duration }} dakika surer.</p>
            </div>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Degerlendirmeye Basla</a>
            </div>

            <p class="note">Bu baglantinin suresi dolarsa, 30 gun sonra yeniden basvurabilirsiniz.</p>

        @elseif($locale === 'ru')
            <h2>Здравствуйте, {{ $candidate->first_name }}!</h2>
            <p>Ваша заявка успешно получена. В качестве следующего шага нашего процесса оценки мы приглашаем вас пройти поведенческую оценку{{ $rank ? " (для должности {$rank})" : '' }}.</p>

            <div class="deadline-box">
                <p>Срок действия ссылки: {{ $expiresAt->format('d.m.Y') }} в {{ $expiresAt->format('H:i') }} (Europe/Istanbul)</p>
            </div>

            <div class="info-box">
                <p><strong>Об оценке:</strong> Эта форма из {{ $questionCount }} вопросов помогает нам понять ваш стиль работы и совместимость с командой. Занимает примерно {{ $duration }} минут.</p>
            </div>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Начать оценку</a>
            </div>

            <p class="note">Если срок действия ссылки истечёт, вы можете подать заявку повторно через 30 дней.</p>

        @elseif($locale === 'az')
            <h2>Salam {{ $candidate->first_name }},</h2>
            <p>Muracietiniz ugurla qebul edildi. Qiymetlendirme prosesimizin novbeti addimi olaraq sizi davranis qiymetlendirmesini tamamlamaga devet edirik{{ $rank ? " ({$rank} vezifesi ucun)" : '' }}.</p>

            <div class="deadline-box">
                <p>Bu kecidin son istifade tarixi: {{ $expiresAt->format('d.m.Y') }} saat {{ $expiresAt->format('H:i') }} (Europe/Istanbul)</p>
            </div>

            <div class="info-box">
                <p><strong>Qiymetlendirme haqqinda:</strong> {{ $questionCount }} sualliq bu forma is terzinizi ve komanda uygunlugunuzu anlamamiza komek edir. Texminen {{ $duration }} deqiqe cekir.</p>
            </div>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Qiymetlendirmeye Basla</a>
            </div>

            <p class="note">Bu kecidin muddeti biterse, 30 gun sonra yeniden muraciet ede bilersiniz.</p>

        @elseif($locale === 'fil')
            <h2>Kumusta {{ $candidate->first_name }},</h2>
            <p>Matagumpay na natanggap ang iyong aplikasyon. Bilang susunod na hakbang, inaanyayahan ka naming kumpletuhin ang behavioral assessment{{ $rank ? " (para sa posisyon ng {$rank})" : '' }}.</p>

            <div class="deadline-box">
                <p>Mag-e-expire ang link na ito sa: {{ $expiresAt->format('M d, Y') }} {{ $expiresAt->format('H:i') }} (Europe/Istanbul)</p>
            </div>

            <div class="info-box">
                <p><strong>Tungkol sa assessment:</strong> Ang {{ $questionCount }}-tanong na form na ito ay tumutulong sa amin na maunawaan ang iyong estilo ng pagtatrabaho. Humigit-kumulang {{ $duration }} minuto.</p>
            </div>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Simulan ang Assessment</a>
            </div>

            <p class="note">Kung mag-expire ang link na ito, maaari kang mag-apply muli pagkatapos ng 30 araw.</p>

        @elseif($locale === 'id')
            <h2>Halo {{ $candidate->first_name }},</h2>
            <p>Lamaran Anda berhasil diterima. Sebagai langkah selanjutnya, kami mengundang Anda untuk menyelesaikan penilaian perilaku{{ $rank ? " (untuk posisi {$rank})" : '' }}.</p>

            <div class="deadline-box">
                <p>Link ini akan kedaluwarsa pada: {{ $expiresAt->format('d/m/Y') }} pukul {{ $expiresAt->format('H:i') }} (Europe/Istanbul)</p>
            </div>

            <div class="info-box">
                <p><strong>Tentang penilaian:</strong> Formulir {{ $questionCount }} pertanyaan ini membantu kami memahami gaya kerja dan kecocokan tim Anda. Membutuhkan sekitar {{ $duration }} menit.</p>
            </div>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Mulai Penilaian</a>
            </div>

            <p class="note">Jika link ini kedaluwarsa, Anda dapat mendaftar kembali setelah 30 hari.</p>

        @elseif($locale === 'uk')
            <h2>Вітаємо, {{ $candidate->first_name }}!</h2>
            <p>Вашу заявку успішно отримано. Як наступний крок, ми запрошуємо вас пройти поведінкову оцінку{{ $rank ? " (для посади {$rank})" : '' }}.</p>

            <div class="deadline-box">
                <p>Термін дії посилання: {{ $expiresAt->format('d.m.Y') }} о {{ $expiresAt->format('H:i') }} (Europe/Istanbul)</p>
            </div>

            <div class="info-box">
                <p><strong>Про оцінку:</strong> Ця форма з {{ $questionCount }} питань допоможе нам зрозуміти ваш стиль роботи та сумісність з командою. Займе приблизно {{ $duration }} хвилин.</p>
            </div>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Почати оцінку</a>
            </div>

            <p class="note">Якщо термін дії посилання закінчиться, ви можете подати заявку повторно через 30 днів.</p>

        @else
            <h2>Hello {{ $candidate->first_name }},</h2>
            <p>Your application has been successfully received. As the next step in our assessment process, we invite you to complete a behavioral assessment{{ $rank ? " for the {$rank} position" : '' }}.</p>

            <div class="deadline-box">
                <p>This link expires on {{ $expiresAt->format('M d, Y') }} at {{ $expiresAt->format('H:i') }} (Europe/Istanbul)</p>
            </div>

            <div class="info-box">
                <p><strong>About the assessment:</strong> This {{ $questionCount }}-question form helps us understand your work style and team compatibility based on your maritime experience. It takes approximately {{ $duration }} minutes.</p>
            </div>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Start Assessment</a>
            </div>

            <p class="note">If this link expires, you may re-apply after 30 days.</p>
        @endif
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ $brand['name'] ?? 'Octopus AI' }} — {{ $brand['tagline'] ?? '' }}</p>
        <p>
            <a href="{{ $brand['website_url'] ?? '#' }}">{{ $brand['domain'] ?? '' }}</a>
            @if(!empty($brand['support_email']))
                | <a href="mailto:{{ $brand['support_email'] }}">{{ $brand['support_email'] }}</a>
            @endif
        </p>
        <p style="font-size: 11px; color: #9ca3af;">
            @if($locale === 'tr')
                Bu e-posta otomatik olarak gonderilmistir. Lutfen yanitlamayin.
            @elseif($locale === 'ru')
                Это письмо отправлено автоматически. Пожалуйста, не отвечайте на него.
            @elseif($locale === 'uk')
                Цей лист надіслано автоматично. Будь ласка, не відповідайте на нього.
            @else
                This is an automated email. Please do not reply.
            @endif
        </p>
    </div>
</div>
</body>
</html>
