<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
<div style="max-width: 600px; margin: 0 auto; background: #ffffff;">
    <div style="background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%); padding: 30px 40px; text-align: center;">
        @include('emails.partials.logo')
        <h1 style="color: #ffffff; font-size: 22px; margin: 0; font-weight: 600;">Yeni Aday Değerlendirmesi</h1>
    </div>

    <div style="padding: 40px;">
        <h2 style="color: #5b21b6; font-size: 20px; margin: 0 0 20px;">Yeni bir aday değerlendirmesi tamamlandı</h2>

        <div style="background: #f5f3ff; border-left: 4px solid #7c3aed; padding: 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
            <p style="margin: 4px 0; color: #4c1d95; font-size: 14px;"><strong style="color: #5b21b6;">Aday:</strong> {{ $candidate->first_name }} {{ $candidate->last_name }}</p>
            <p style="margin: 4px 0; color: #4c1d95; font-size: 14px;"><strong style="color: #5b21b6;">Pozisyon:</strong> {{ $positionTitle }}</p>
            @if($completedAt)
                <p style="margin: 4px 0; color: #4c1d95; font-size: 14px;"><strong style="color: #5b21b6;">Tamamlanma:</strong> {{ $completedAt->format('d.m.Y H:i') }}</p>
            @endif
        </div>

        @if($overallScore !== null)
            <div style="background: #f0f7ff; border-left: 4px solid #2563eb; padding: 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
                <p style="margin: 4px 0; color: #2d3748; font-size: 14px;"><strong style="color: #1e40af;">AI Değerlendirme Özeti</strong></p>
                <p style="margin: 4px 0; color: #2d3748; font-size: 14px;"><strong style="color: #1e40af;">Genel Puan:</strong> {{ $overallScore }}/100</p>
                @if($recommendation)
                    <p style="margin: 4px 0; color: #2d3748; font-size: 14px;"><strong style="color: #1e40af;">Öneri:</strong>
                        @if($recommendation === 'strongly_recommend')
                            Kesinlikle Öneriliyor
                        @elseif($recommendation === 'recommend')
                            Öneriliyor
                        @elseif($recommendation === 'consider')
                            Değerlendirilmeli
                        @elseif($recommendation === 'not_recommended')
                            Önerilmiyor
                        @else
                            {{ $recommendation }}
                        @endif
                    </p>
                @endif
                @if($confidence)
                    <p style="margin: 4px 0; color: #2d3748; font-size: 14px;"><strong style="color: #1e40af;">Güven Oranı:</strong> %{{ $confidence }}</p>
                @endif
            </div>
        @endif

        @if($hasRedFlags)
            <div style="background: #fef3f2; border-left: 4px solid #f87171; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0;">
                <p style="margin: 4px 0; color: #991b1b; font-size: 14px;"><strong>Dikkat:</strong> Bu adayın değerlendirmesinde kırmızı bayrak(lar) tespit edilmiştir. Detaylar için aday profilini inceleyin.</p>
            </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ $adminUrl }}" style="display: inline-block; background: #7c3aed; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 15px; margin: 24px 0;">Adayı İncele</a>
        </div>
    </div>

    <div style="background: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0;">
        <p style="color: #94a3b8; font-size: 12px; margin: 4px 0;">&copy; {{ date('Y') }} {{ $brandName }} &middot; {{ $companyName }}</p>
        <p style="margin-top: 8px; font-size: 11px; color: #b0b8c4;">
            Bu e-posta otomatik olarak gönderilmiştir.
        </p>
    </div>
</div>
</body>
</html>
