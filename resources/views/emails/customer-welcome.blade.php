<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Octopus AI — Portalınız Hazır</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f7fa;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" style="width: 600px; max-width: 100%; border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0f4c81 0%, #1a6fb5 100%); padding: 40px 40px 30px; border-radius: 12px 12px 0 0; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;">
                                Octopus AI
                            </h1>
                            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 14px;">
                                AI-Powered Maritime Crew Assessment
                            </p>
                        </td>
                    </tr>

                    <!-- Welcome -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="color: #1a1a2e; margin: 0 0 20px; font-size: 24px;">
                                Hoş Geldiniz, {{ $user->first_name }}!
                            </h2>
                            <p style="color: #4a5568; font-size: 16px; line-height: 1.6; margin: 0 0 25px;">
                                <strong>{{ $company->name }}</strong> için Octopus AI mürettebat değerlendirme portalınız
                                başarıyla oluşturuldu. Artık mürettebatınızı yapay zeka ile değerlendirebilirsiniz.
                            </p>

                            <!-- Login Credentials -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f0f7ff; border-radius: 8px; border-left: 4px solid #0f4c81; margin: 25px 0;">
                                <tr>
                                    <td style="padding: 25px;">
                                        <h3 style="color: #0f4c81; margin: 0 0 15px; font-size: 16px;">
                                            Giriş Bilgileriniz
                                        </h3>
                                        <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="padding: 8px 0; color: #718096; font-size: 14px; width: 80px;">Portal:</td>
                                                <td style="padding: 8px 0;">
                                                    <a href="{{ $portalUrl }}" style="color: #0f4c81; text-decoration: none; font-weight: 600; font-size: 14px;">{{ $portalUrl }}</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #718096; font-size: 14px;">E-posta:</td>
                                                <td style="padding: 8px 0; color: #1a1a2e; font-size: 14px; font-weight: 500;">{{ $user->email }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #718096; font-size: 14px;">Şifre:</td>
                                                <td style="padding: 8px 0;">
                                                    <span style="color: #1a1a2e; font-size: 14px; font-weight: 600; font-family: monospace; background-color: #e2e8f0; padding: 4px 10px; border-radius: 4px;">{{ $password }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style="color: #e53e3e; font-size: 12px; margin: 15px 0 0;">
                                            Güvenliğiniz için ilk girişte şifrenizi değiştirmenizi öneririz.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td align="center" style="padding: 10px 0 30px;">
                                        <a href="{{ $portalUrl }}" style="display: inline-block; background: linear-gradient(135deg, #0f4c81 0%, #1a6fb5 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(15, 76, 129, 0.4);">
                                            Portala Giriş Yap
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">

                            <!-- Credits -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f0fdf4; border-radius: 8px; border-left: 4px solid #16a34a; margin: 25px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h4 style="color: #166534; margin: 0 0 10px; font-size: 16px;">Hesabınızda</h4>
                                        <p style="color: #4a5568; font-size: 15px; line-height: 1.6; margin: 0;">
                                            <strong>{{ $credits }} mülakat kontürü</strong> tanımlıdır.
                                            Her tamamlanan mülakat 1 kontür harcar.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">

                            <!-- How It Works -->
                            <h3 style="color: #1a1a2e; margin: 30px 0 20px; font-size: 20px;">
                                Nasıl Çalışır?
                            </h3>

                            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="width: 40px; vertical-align: top;">
                                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #0f4c81 0%, #1a6fb5 100%); border-radius: 50%; text-align: center; line-height: 32px; color: white; font-weight: bold; font-size: 14px;">1</div>
                                                </td>
                                                <td style="padding-left: 15px;">
                                                    <h4 style="color: #1a1a2e; margin: 0 0 5px; font-size: 15px;">Mürettebat Listesini Yükleyin</h4>
                                                    <p style="color: #718096; font-size: 13px; line-height: 1.5; margin: 0;">
                                                        Portaldan CSV dosyası ile mürettebatınızı toplu yükleyin. İsim, email, pozisyon, departman bilgileri yeterli.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="width: 40px; vertical-align: top;">
                                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #0f4c81 0%, #1a6fb5 100%); border-radius: 50%; text-align: center; line-height: 32px; color: white; font-weight: bold; font-size: 14px;">2</div>
                                                </td>
                                                <td style="padding-left: 15px;">
                                                    <h4 style="color: #1a1a2e; margin: 0 0 5px; font-size: 15px;">AI Mülakat Başlasın</h4>
                                                    <p style="color: #718096; font-size: 13px; line-height: 1.5; margin: 0;">
                                                        Her mürettebat için pozisyonuna özel sorular hazırlanır. Kaptan, başmühendis, güverte zabiti — her rol için ayrı soru seti.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="width: 40px; vertical-align: top;">
                                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #0f4c81 0%, #1a6fb5 100%); border-radius: 50%; text-align: center; line-height: 32px; color: white; font-weight: bold; font-size: 14px;">3</div>
                                                </td>
                                                <td style="padding-left: 15px;">
                                                    <h4 style="color: #1a1a2e; margin: 0 0 5px; font-size: 15px;">Sonuçları Takip Edin</h4>
                                                    <p style="color: #718096; font-size: 13px; line-height: 1.5; margin: 0;">
                                                        Portaldan sonuçları anlık görün. HIRE / REVIEW / REJECT kararları, yetkinlik puanları ve PDF raporlar elinizde.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Support -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse; margin-top: 30px;">
                                <tr>
                                    <td style="background-color: #f8fafc; border-radius: 8px; padding: 20px; text-align: center;">
                                        <p style="color: #4a5568; font-size: 14px; margin: 0 0 10px;">
                                            Sorularınız mı var? Size yardımcı olmaktan mutluluk duyarız.
                                        </p>
                                        <p style="margin: 0;">
                                            <a href="mailto:support@octopus-ai.net" style="color: #0f4c81; text-decoration: none; font-weight: 500;">support@octopus-ai.net</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #0f2b40; padding: 30px 40px; border-radius: 0 0 12px 12px; text-align: center;">
                            <p style="color: #a0aec0; font-size: 13px; margin: 0 0 10px;">
                                &copy; {{ date('Y') }} Octopus AI. All rights reserved.
                            </p>
                            <p style="color: #718096; font-size: 12px; margin: 0;">
                                AI-Powered Maritime Crew Assessment Platform
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
