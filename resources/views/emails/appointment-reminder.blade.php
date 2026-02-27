<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TalentQX — Randevu Hatırlatması</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f7fa;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" style="width: 600px; max-width: 100%; border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 40px; border-radius: 12px 12px 0 0; text-align: center;">
                            <img src="https://talentqx.com/assets/logo-email.png" alt="TalentQX" style="max-height: 48px; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Randevu Hatırlatması</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #4a4a6a; font-size: 15px; line-height: 1.6;">
                                30 dakika sonra bir randevunuz var:
                            </p>
                            <table role="presentation" style="width: 100%; margin: 20px 0; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 16px; background: #f8f9ff; border-radius: 8px;">
                                        <strong style="font-size: 18px; color: #1a1a2e;">{{ $appointment->title }}</strong><br><br>
                                        <strong>Tarih/Saat:</strong> {{ $startsAt }}<br>
                                        @if($lead)
                                            <strong>Müşteri:</strong> {{ $lead->lead_name }}<br>
                                        @endif
                                        @if($appointment->notes)
                                            <strong>Notlar:</strong> {{ $appointment->notes }}
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 40px; background-color: #f8f9ff; border-radius: 0 0 12px 12px; text-align: center;">
                            <p style="color: #999; font-size: 12px; margin: 0;">&copy; {{ date('Y') }} TalentQX</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
