<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Octopus AI'a Hoş Geldiniz</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f7fa;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" style="width: 600px; max-width: 100%; border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 40px 30px; border-radius: 12px 12px 0 0; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;">
                                Octopus AI
                            </h1>
                            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 14px;">
                                Yapay Zeka Destekli İşe Alım Platformu
                            </p>
                        </td>
                    </tr>

                    <!-- Welcome Message -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="color: #1a1a2e; margin: 0 0 20px; font-size: 24px;">
                                Hoş Geldiniz, {{ $user->first_name }}! 👋
                            </h2>
                            <p style="color: #4a5568; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                <strong>{{ $company->name }}</strong> için Octopus AI demo hesabınız başarıyla oluşturuldu.
                                Artık işe alım süreçlerinizi yapay zeka ile hızlandırmaya hazırsınız!
                            </p>

                            <!-- Login Credentials Box -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f8fafc; border-radius: 8px; margin: 25px 0;">
                                <tr>
                                    <td style="padding: 25px;">
                                        <h3 style="color: #667eea; margin: 0 0 15px; font-size: 16px;">
                                            🔐 Giriş Bilgileriniz
                                        </h3>
                                        <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="padding: 8px 0; color: #718096; font-size: 14px; width: 80px;">Platform:</td>
                                                <td style="padding: 8px 0; color: #1a1a2e; font-size: 14px; font-weight: 500;">
                                                    <a href="{{ $platformUrl }}" style="color: #667eea; text-decoration: none;">{{ $platformUrl }}</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #718096; font-size: 14px;">E-posta:</td>
                                                <td style="padding: 8px 0; color: #1a1a2e; font-size: 14px; font-weight: 500;">{{ $user->email }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #718096; font-size: 14px;">Şifre:</td>
                                                <td style="padding: 8px 0; color: #1a1a2e; font-size: 14px; font-weight: 600; font-family: monospace; background-color: #edf2f7; padding: 8px 12px; border-radius: 4px; display: inline-block;">{{ $password }}</td>
                                            </tr>
                                        </table>
                                        <p style="color: #e53e3e; font-size: 12px; margin: 15px 0 0;">
                                            ⚠️ Güvenliğiniz için ilk girişte şifrenizi değiştirmeniz istenecektir.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td align="center" style="padding: 10px 0 30px;">
                                        <a href="{{ $platformUrl }}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                                            Platforma Giriş Yap →
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Divider -->
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">

                            <!-- What is Octopus AI Section -->
                            <h3 style="color: #1a1a2e; margin: 30px 0 20px; font-size: 20px;">
                                🚀 Octopus AI Nedir?
                            </h3>
                            <p style="color: #4a5568; font-size: 15px; line-height: 1.7; margin: 0 0 20px;">
                                Octopus AI, <strong>perakende ve hizmet sektörü</strong> için özel olarak geliştirilmiş,
                                yapay zeka destekli işe alım platformudur. Yüksek cirolu pozisyonlarda (mağaza personeli,
                                barista, kasiyer vb.) hızlı, objektif ve ölçeklenebilir işe alım yapmanızı sağlar.
                            </p>

                            <!-- Features Grid -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 25px 0;">
                                <tr>
                                    <td style="width: 50%; padding: 15px; vertical-align: top;">
                                        <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f0fdf4; border-radius: 8px; height: 100%;">
                                            <tr>
                                                <td style="padding: 20px;">
                                                    <div style="font-size: 24px; margin-bottom: 10px;">⚡</div>
                                                    <h4 style="color: #166534; margin: 0 0 8px; font-size: 15px;">Dakikalar İçinde Mülakat</h4>
                                                    <p style="color: #4a5568; font-size: 13px; line-height: 1.5; margin: 0;">
                                                        QR kod ile anında başvuru. Aday telefonundan 10 dakikada mülakatı tamamlar.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="width: 50%; padding: 15px; vertical-align: top;">
                                        <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #eff6ff; border-radius: 8px; height: 100%;">
                                            <tr>
                                                <td style="padding: 20px;">
                                                    <div style="font-size: 24px; margin-bottom: 10px;">🤖</div>
                                                    <h4 style="color: #1e40af; margin: 0 0 8px; font-size: 15px;">AI Analiz & Puanlama</h4>
                                                    <p style="color: #4a5568; font-size: 13px; line-height: 1.5; margin: 0;">
                                                        Her mülakat yapay zeka ile analiz edilir. Yetkinlik puanları ve öneriler sunulur.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 50%; padding: 15px; vertical-align: top;">
                                        <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #fef3c7; border-radius: 8px; height: 100%;">
                                            <tr>
                                                <td style="padding: 20px;">
                                                    <div style="font-size: 24px; margin-bottom: 10px;">📊</div>
                                                    <h4 style="color: #92400e; margin: 0 0 8px; font-size: 15px;">Karşılaştırma & Sıralama</h4>
                                                    <p style="color: #4a5568; font-size: 13px; line-height: 1.5; margin: 0;">
                                                        Adayları yan yana karşılaştırın. En iyi adayı veriye dayalı seçin.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="width: 50%; padding: 15px; vertical-align: top;">
                                        <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #fce7f3; border-radius: 8px; height: 100%;">
                                            <tr>
                                                <td style="padding: 20px;">
                                                    <div style="font-size: 24px; margin-bottom: 10px;">🏪</div>
                                                    <h4 style="color: #9d174d; margin: 0 0 8px; font-size: 15px;">Çoklu Şube Yönetimi</h4>
                                                    <p style="color: #4a5568; font-size: 13px; line-height: 1.5; margin: 0;">
                                                        Tüm şubelerinizin işe alım süreçlerini tek panelden yönetin.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Divider -->
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">

                            <!-- How It Works -->
                            <h3 style="color: #1a1a2e; margin: 30px 0 20px; font-size: 20px;">
                                📋 Nasıl Çalışır?
                            </h3>

                            <!-- Steps -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="width: 40px; vertical-align: top;">
                                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; text-align: center; line-height: 32px; color: white; font-weight: bold; font-size: 14px;">1</div>
                                                </td>
                                                <td style="padding-left: 15px;">
                                                    <h4 style="color: #1a1a2e; margin: 0 0 5px; font-size: 15px;">İlan Oluşturun</h4>
                                                    <p style="color: #718096; font-size: 13px; line-height: 1.5; margin: 0;">
                                                        Pozisyon seçin, sorular otomatik oluşturulsun. 2 dakikada ilan hazır.
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
                                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; text-align: center; line-height: 32px; color: white; font-weight: bold; font-size: 14px;">2</div>
                                                </td>
                                                <td style="padding-left: 15px;">
                                                    <h4 style="color: #1a1a2e; margin: 0 0 5px; font-size: 15px;">QR Kod Paylaşın</h4>
                                                    <p style="color: #718096; font-size: 13px; line-height: 1.5; margin: 0;">
                                                        Mağazanıza, sosyal medyaya veya iş ilanlarına QR kodu ekleyin.
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
                                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; text-align: center; line-height: 32px; color: white; font-weight: bold; font-size: 14px;">3</div>
                                                </td>
                                                <td style="padding-left: 15px;">
                                                    <h4 style="color: #1a1a2e; margin: 0 0 5px; font-size: 15px;">Adaylar Mülakat Yapsın</h4>
                                                    <p style="color: #718096; font-size: 13px; line-height: 1.5; margin: 0;">
                                                        Aday QR'ı tarar, telefonda mülakatı tamamlar. Siz meşgul olmayın.
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
                                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; text-align: center; line-height: 32px; color: white; font-weight: bold; font-size: 14px;">4</div>
                                                </td>
                                                <td style="padding-left: 15px;">
                                                    <h4 style="color: #1a1a2e; margin: 0 0 5px; font-size: 15px;">AI Raporu İnceleyin</h4>
                                                    <p style="color: #718096; font-size: 13px; line-height: 1.5; margin: 0;">
                                                        Her aday için detaylı analiz raporu. En uygun adayı hızla belirleyin.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Divider -->
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 30px 0 20px;">

                            <!-- Demo Info Box -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse; background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border-radius: 8px; border-left: 4px solid #667eea;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h4 style="color: #667eea; margin: 0 0 10px; font-size: 16px;">🎁 Demo Hesabınızda</h4>
                                        <ul style="color: #4a5568; font-size: 14px; line-height: 1.8; margin: 0; padding-left: 20px;">
                                            <li><strong>{{ $credits }} mülakat kontürü</strong> tanımlıdır</li>
                                            <li>Sınırsız ilan oluşturabilirsiniz</li>
                                            <li>QR kod oluşturup paylaşabilirsiniz</li>
                                            <li>AI analiz raporlarına erişebilirsiniz</li>
                                            <li>Aday karşılaştırma yapabilirsiniz</li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>

                            <!-- Support Section -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse; margin-top: 30px;">
                                <tr>
                                    <td style="background-color: #f8fafc; border-radius: 8px; padding: 20px; text-align: center;">
                                        <p style="color: #4a5568; font-size: 14px; margin: 0 0 10px;">
                                            Sorularınız mı var? Size yardımcı olmaktan mutluluk duyarız!
                                        </p>
                                        <p style="margin: 0;">
                                            <a href="mailto:support@talentqx.com" style="color: #667eea; text-decoration: none; font-weight: 500;">📧 support@talentqx.com</a>
                                            <span style="color: #cbd5e0; margin: 0 10px;">|</span>
                                            <a href="https://talentqx.com" style="color: #667eea; text-decoration: none; font-weight: 500;">🌐 talentqx.com</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1a1a2e; padding: 30px 40px; border-radius: 0 0 12px 12px; text-align: center;">
                            <p style="color: #a0aec0; font-size: 13px; margin: 0 0 10px;">
                                © {{ date('Y') }} Octopus AI. Tüm hakları saklıdır.
                            </p>
                            <p style="color: #718096; font-size: 12px; margin: 0;">
                                Bu e-posta, Octopus AI demo hesabı talebi sonucu gönderilmiştir.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
