<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontürünüz Azalıyor</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #18181b; padding: 24px 32px; text-align: center;">
                            @include('emails.partials.logo')
                            <h1 style="color: #ffffff; font-size: 20px; margin: 0;">{{ $brandName }}</h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 32px;">
                            <p style="font-size: 16px; color: #18181b; margin: 0 0 16px;">
                                Sayın {{ $userName }},
                            </p>
                            <p style="font-size: 14px; color: #52525b; margin: 0 0 24px; line-height: 1.6;">
                                <strong>{{ $company->name }}</strong> hesabınızın mülakat kontürü azalmaktadır.
                                Mülakat süreçlerinizin kesintisiz devam edebilmesi için kontür satın almanızı öneriyoruz.
                            </p>

                            {{-- Credit Status --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px; text-align: center;">
                                        <p style="font-size: 14px; color: #991b1b; margin: 0 0 8px;">Kalan Kontür</p>
                                        <p style="font-size: 36px; font-weight: bold; color: #dc2626; margin: 0 0 8px;">
                                            {{ $remaining }} / {{ $total }}
                                        </p>

                                        {{-- Progress Bar --}}
                                        <div style="background-color: #fee2e2; border-radius: 4px; height: 8px; width: 100%; max-width: 300px; margin: 0 auto;">
                                            <div style="background-color: #dc2626; border-radius: 4px; height: 8px; width: {{ $percentage }}%;"></div>
                                        </div>

                                        <p style="font-size: 12px; color: #991b1b; margin: 8px 0 0;">
                                            %{{ $percentage }} kaldı
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0 24px;">
                                        <a href="{{ $billingUrl }}"
                                           style="display: inline-block; background-color: #18181b; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; padding: 12px 32px; border-radius: 6px;">
                                            Kontür Satın Al
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 12px; color: #a1a1aa; margin: 0; line-height: 1.6;">
                                Bu e-posta, kontür bakiyeniz %10'un altına düştüğü için otomatik olarak gönderilmiştir.
                                Sorularınız için destek ekibimizle iletişime geçebilirsiniz.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f4f4f5; padding: 16px 32px; text-align: center; border-top: 1px solid #e4e4e7;">
                            <p style="font-size: 12px; color: #a1a1aa; margin: 0;">
                                &copy; {{ date('Y') }} {{ $brandName }}. Tüm hakları saklıdır.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
