<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TalentQX — Ödeme Bağlantısı</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f7fa;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" style="width: 600px; max-width: 100%; border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 40px 30px; border-radius: 12px 12px 0 0; text-align: center;">
                            <img src="https://talentqx.com/assets/logo-email.png" alt="TalentQX" style="max-height: 48px; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;">TalentQX</h1>
                            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 14px;">Ödeme Bağlantısı</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="color: #1a1a2e; margin: 0 0 20px; font-size: 22px;">Merhaba,</h2>
                            <p style="color: #4a4a6a; font-size: 15px; line-height: 1.6;">
                                <strong>{{ $company->name }}</strong> için <strong>{{ $package->name }}</strong> paketi ödeme bağlantınız aşağıdadır.
                            </p>
                            <table role="presentation" style="width: 100%; margin: 20px 0; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 12px 16px; background: #f8f9ff; border-radius: 8px;">
                                        <strong>Paket:</strong> {{ $package->name }}<br>
                                        <strong>Tutar:</strong> {{ number_format($amount, 2) }} {{ $currency }}<br>
                                        <strong>Kredi:</strong> {{ $package->credits }} mülakat kredisi
                                    </td>
                                </tr>
                            </table>
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{ $checkoutUrl }}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 14px 40px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 16px;">
                                    Ödemeyi Tamamla
                                </a>
                            </div>
                            <p style="color: #999; font-size: 12px; text-align: center;">Bu bağlantı güvenli iyzico ödeme sayfasına yönlendirecektir.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 40px; background-color: #f8f9ff; border-radius: 0 0 12px 12px; text-align: center;">
                            <p style="color: #999; font-size: 12px; margin: 0;">&copy; {{ date('Y') }} TalentQX. Tüm hakları saklıdır.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
