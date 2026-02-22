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
        .cta-wrapper { text-align: center; margin: 28px 0; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff !important; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-size: 16px; font-weight: 600; }
        .steps { margin: 20px 0; padding: 0; }
        .steps li { color: #374151; font-size: 14px; line-height: 1.8; margin-bottom: 4px; }
        .footer { background-color: #f9fafb; padding: 24px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { color: #6b7280; font-size: 12px; margin: 0 0 8px; }
        .footer a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <span class="emoji">{{ $brand['header_emoji'] ?? '' }}</span>
        <h1>{{ $brand['name'] ?? 'Octopus AI' }}</h1>
    </div>

    <div class="body">
        @if($locale === 'tr')
            <h2>Merhaba {{ $candidate->first_name }},</h2>
            <p>Teknik mülakatınızı başarıyla tamamladığınız için tebrikler! Değerlendirme sürecimizin bir sonraki adımı olarak, sizi davranışsal değerlendirmemize davet ediyoruz.</p>

            <div class="info-box">
                <p><strong>Bu değerlendirme nedir?</strong> 12 soruluk bu kısa form, denizcilik deneyimlerinize dayalı olarak çalışma tarzınızı ve ekip uyumunuzu anlamamıza yardımcı olur. Doğru veya yanlış cevap yoktur — sadece gerçek deneyimlerinizi paylaşın.</p>
            </div>

            <p><strong>Ne beklemelisiniz:</strong></p>
            <ul class="steps">
                <li>4 kategori, her biri 3 soru (toplam 12 soru)</li>
                <li>Yaklaşık 15-20 dakika sürer</li>
                <li>Yanıtlarınız otomatik olarak kaydedilir</li>
                <li>Dilediğiniz zaman tamamlayabilirsiniz</li>
            </ul>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Değerlendirmeye Başla</a>
            </div>
        @elseif($locale === 'ru')
            <h2>Здравствуйте, {{ $candidate->first_name }}!</h2>
            <p>Поздравляем с успешным прохождением технического собеседования! В качестве следующего шага нашего процесса оценки мы приглашаем вас пройти поведенческую оценку.</p>

            <div class="info-box">
                <p><strong>Что это за оценка?</strong> Эта короткая форма из 12 вопросов помогает нам понять ваш стиль работы и совместимость с командой на основе вашего морского опыта. Нет правильных или неправильных ответов — просто поделитесь своим реальным опытом.</p>
            </div>

            <p><strong>Что вас ожидает:</strong></p>
            <ul class="steps">
                <li>4 категории по 3 вопроса (всего 12 вопросов)</li>
                <li>Займёт примерно 15-20 минут</li>
                <li>Ваши ответы сохраняются автоматически</li>
                <li>Можно завершить в удобное для вас время</li>
            </ul>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Начать оценку</a>
            </div>
        @elseif($locale === 'az')
            <h2>Salam {{ $candidate->first_name }},</h2>
            <p>Texniki müsahibəni uğurla tamamladığınız üçün təbrik edirik! Qiymətləndirmə prosesimizin növbəti addımı olaraq sizi davranış qiymətləndirməsindən keçməyə dəvət edirik.</p>

            <div class="info-box">
                <p><strong>Bu qiymətləndirmə nədir?</strong> 12 suallıq bu qısa forma dənizçilik təcrübənizə əsaslanaraq iş tərzinizi və komanda uyğunluğunuzu anlamamıza kömək edir. Doğru və ya yanlış cavab yoxdur — sadəcə real təcrübənizi paylaşın.</p>
            </div>

            <p><strong>Nə gözləməlisiniz:</strong></p>
            <ul class="steps">
                <li>4 kateqoriya, hər biri 3 sual (cəmi 12 sual)</li>
                <li>Təxminən 15-20 dəqiqə çəkir</li>
                <li>Cavablarınız avtomatik saxlanılır</li>
                <li>İstədiyiniz vaxt tamamlaya bilərsiniz</li>
            </ul>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Qiymətləndirməyə Başla</a>
            </div>
        @elseif($locale === 'fil')
            <h2>Kumusta {{ $candidate->first_name }},</h2>
            <p>Binabati ka sa matagumpay na pagkumpleto ng iyong technical interview! Bilang susunod na hakbang sa aming assessment process, inaanyayahan ka naming kumpletuhin ang behavioral assessment.</p>

            <div class="info-box">
                <p><strong>Ano ang assessment na ito?</strong> Ang maikling form na ito na may 12 tanong ay tumutulong sa amin na maunawaan ang iyong estilo ng pagtatrabaho at pagkakatugma sa koponan batay sa iyong karanasan sa dagat. Walang tama o maling sagot — ibahagi lang ang iyong tunay na karanasan.</p>
            </div>

            <p><strong>Ano ang aasahan mo:</strong></p>
            <ul class="steps">
                <li>4 na kategorya, bawat isa ay may 3 tanong (12 tanong lahat)</li>
                <li>Humigit-kumulang 15-20 minuto</li>
                <li>Awtomatikong nai-save ang mga sagot mo</li>
                <li>Maaari mong tapusin anumang oras</li>
            </ul>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Simulan ang Assessment</a>
            </div>
        @elseif($locale === 'id')
            <h2>Halo {{ $candidate->first_name }},</h2>
            <p>Selamat atas keberhasilan menyelesaikan wawancara teknis Anda! Sebagai langkah selanjutnya dalam proses penilaian kami, kami mengundang Anda untuk menyelesaikan penilaian perilaku.</p>

            <div class="info-box">
                <p><strong>Apa penilaian ini?</strong> Formulir singkat berisi 12 pertanyaan ini membantu kami memahami gaya kerja dan kecocokan tim Anda berdasarkan pengalaman maritim Anda. Tidak ada jawaban benar atau salah — cukup bagikan pengalaman nyata Anda.</p>
            </div>

            <p><strong>Apa yang bisa Anda harapkan:</strong></p>
            <ul class="steps">
                <li>4 kategori, masing-masing 3 pertanyaan (total 12 pertanyaan)</li>
                <li>Membutuhkan sekitar 15-20 menit</li>
                <li>Jawaban Anda disimpan secara otomatis</li>
                <li>Anda bisa menyelesaikan kapan saja</li>
            </ul>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Mulai Penilaian</a>
            </div>
        @elseif($locale === 'uk')
            <h2>Вітаємо, {{ $candidate->first_name }}!</h2>
            <p>Вітаємо з успішним проходженням технічного співбесіди! Як наступний крок нашого процесу оцінювання, ми запрошуємо вас пройти поведінкову оцінку.</p>

            <div class="info-box">
                <p><strong>Що це за оцінка?</strong> Ця коротка форма з 12 питань допоможе нам зрозуміти ваш стиль роботи та сумісність з командою на основі вашого морського досвіду. Немає правильних чи неправильних відповідей — просто поділіться своїм реальним досвідом.</p>
            </div>

            <p><strong>Що вас очікує:</strong></p>
            <ul class="steps">
                <li>4 категорії по 3 питання (всього 12 питань)</li>
                <li>Займе приблизно 15-20 хвилин</li>
                <li>Ваші відповіді зберігаються автоматично</li>
                <li>Можна завершити у зручний для вас час</li>
            </ul>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Почати оцінку</a>
            </div>
        @else
            <h2>Hello {{ $candidate->first_name }},</h2>
            <p>Congratulations on successfully completing your technical interview! As the next step in our assessment process, we invite you to complete a behavioral assessment.</p>

            <div class="info-box">
                <p><strong>What is this assessment?</strong> This short 12-question form helps us understand your work style and team compatibility based on your maritime experience. There are no right or wrong answers — just share your real experience.</p>
            </div>

            <p><strong>What to expect:</strong></p>
            <ul class="steps">
                <li>4 categories with 3 questions each (12 questions total)</li>
                <li>Takes approximately 15-20 minutes</li>
                <li>Your answers are auto-saved</li>
                <li>Complete at your convenience</li>
            </ul>

            <div class="cta-wrapper">
                <a href="{{ $interviewUrl }}" class="cta-button">Start Assessment</a>
            </div>
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
                Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayın.
            @elseif($locale === 'ru')
                Это письмо отправлено автоматически. Пожалуйста, не отвечайте на него.
            @else
                This is an automated email. Please do not reply.
            @endif
        </p>
    </div>
</div>
</body>
</html>
