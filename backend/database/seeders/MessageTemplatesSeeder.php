<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class MessageTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Password Reset Email
            [
                'code' => 'password_reset',
                'name' => 'Şifre Sıfırlama E-postası',
                'channel' => 'email',
                'locale' => 'tr',
                'subject' => 'Şifre Sıfırlama Talebi - TalentQX',
                'body' => <<<'HTML'
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırlama</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">TalentQX</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Merhaba {{name}},</h2>
        <p>TalentQX hesabınız için şifre sıfırlama talebi aldık.</p>
        <p>Şifrenizi sıfırlamak için aşağıdaki butona tıklayın:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{reset_link}}" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Şifremi Sıfırla</a>
        </div>
        <p style="color: #666; font-size: 14px;">Bu bağlantı <strong>{{minutes}} dakika</strong> boyunca geçerlidir.</p>
        <p style="color: #666; font-size: 14px;">Eğer bu talebi siz yapmadıysanız, bu e-postayı dikkate almayın. Hesabınız güvende.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
        <p style="color: #999; font-size: 12px; margin: 0;">Bu e-posta TalentQX tarafından otomatik olarak gönderilmiştir.</p>
    </div>
</body>
</html>
HTML,
                'available_variables' => ['name', 'reset_link', 'minutes'],
                'is_active' => true,
                'is_system' => true,
            ],

            // Welcome / First Login Email
            [
                'code' => 'welcome_first_login',
                'name' => 'Hoş Geldiniz - İlk Giriş',
                'channel' => 'email',
                'locale' => 'tr',
                'subject' => 'TalentQX Hesabınız Oluşturuldu',
                'body' => <<<'HTML'
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoş Geldiniz</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">TalentQX</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Hoş Geldiniz {{name}}!</h2>
        <p><strong>{{company}}</strong> tarafından TalentQX hesabınız oluşturuldu.</p>
        <p>İlk girişinizde geçici şifrenizi değiştirmeniz istenecektir.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{login_link}}" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Giriş Yap</a>
        </div>
        <p style="color: #666; font-size: 14px;">Geçici şifreniz size ayrıca iletilecektir.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
        <p style="color: #999; font-size: 12px; margin: 0;">Bu e-posta TalentQX tarafından otomatik olarak gönderilmiştir.</p>
    </div>
</body>
</html>
HTML,
                'available_variables' => ['name', 'company', 'login_link'],
                'is_active' => true,
                'is_system' => true,
            ],

            // SMS OTP
            [
                'code' => 'sms_otp',
                'name' => 'SMS Doğrulama Kodu',
                'channel' => 'sms',
                'locale' => 'tr',
                'subject' => null,
                'body' => 'TalentQX doğrulama kodunuz: {{code}}. Bu kod {{minutes}} dakika geçerlidir. Kimseyle paylaşmayın.',
                'available_variables' => ['code', 'minutes'],
                'is_active' => true,
                'is_system' => true,
            ],

            // Application Received Email
            [
                'code' => 'application_received',
                'name' => 'Başvuru Alındı',
                'channel' => 'email',
                'locale' => 'tr',
                'subject' => 'Başvurunuz Alındı - {{company}}',
                'body' => <<<'HTML'
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Başvuru Alındı</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">{{company}}</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Merhaba {{name}},</h2>
        <p><strong>{{position}}</strong> pozisyonu için başvurunuz başarıyla alındı.</p>
        <p>Başvurunuz incelendikten sonra sizinle iletişime geçilecektir.</p>
        <div style="background: #fff; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #667eea;">
            <p style="margin: 0;"><strong>Pozisyon:</strong> {{position}}</p>
            <p style="margin: 10px 0 0;"><strong>Şube:</strong> {{branch}}</p>
        </div>
        <p style="color: #666; font-size: 14px;">Başvuru sürecinizi takip etmek için herhangi bir işlem yapmanıza gerek yok. Gerektiğinde sizinle iletişime geçeceğiz.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
        <p style="color: #999; font-size: 12px; margin: 0;">Bu e-posta TalentQX üzerinden otomatik olarak gönderilmiştir.</p>
    </div>
</body>
</html>
HTML,
                'available_variables' => ['name', 'company', 'position', 'branch'],
                'is_active' => true,
                'is_system' => true,
            ],

            // Application Received SMS
            [
                'code' => 'application_received_sms',
                'name' => 'Başvuru Alındı SMS',
                'channel' => 'sms',
                'locale' => 'tr',
                'subject' => null,
                'body' => '{{company}} - {{position}} başvurunuz alındı. İnceleme sonrası bilgilendirileceksiniz.',
                'available_variables' => ['company', 'position'],
                'is_active' => true,
                'is_system' => true,
            ],

            // Interview Invitation Email
            [
                'code' => 'interview_invitation',
                'name' => 'Mülakat Daveti',
                'channel' => 'email',
                'locale' => 'tr',
                'subject' => 'Mülakat Daveti - {{company}}',
                'body' => <<<'HTML'
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mülakat Daveti</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">{{company}}</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Merhaba {{name}},</h2>
        <p><strong>{{position}}</strong> pozisyonu için başvurunuz değerlendirildi ve sizi mülakata davet ediyoruz!</p>
        <div style="background: #fff; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #28a745;">
            <p style="margin: 0;"><strong>Tarih:</strong> {{interview_date}}</p>
            <p style="margin: 10px 0 0;"><strong>Saat:</strong> {{interview_time}}</p>
            <p style="margin: 10px 0 0;"><strong>Konum:</strong> {{location}}</p>
        </div>
        <p>Lütfen belirtilen tarih ve saatte hazır bulunun.</p>
        <p style="color: #666; font-size: 14px;">Herhangi bir sorunuz varsa bizimle iletişime geçmekten çekinmeyin.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
        <p style="color: #999; font-size: 12px; margin: 0;">Bu e-posta TalentQX üzerinden otomatik olarak gönderilmiştir.</p>
    </div>
</body>
</html>
HTML,
                'available_variables' => ['name', 'company', 'position', 'interview_date', 'interview_time', 'location'],
                'is_active' => true,
                'is_system' => true,
            ],

            // Interview Invitation SMS
            [
                'code' => 'interview_invitation_sms',
                'name' => 'Mülakat Daveti SMS',
                'channel' => 'sms',
                'locale' => 'tr',
                'subject' => null,
                'body' => '{{company}} - {{position}} mülakatınız {{interview_date}} saat {{interview_time}} için planlandı. Konum: {{location}}',
                'available_variables' => ['company', 'position', 'interview_date', 'interview_time', 'location'],
                'is_active' => true,
                'is_system' => true,
            ],
        ];

        foreach ($templates as $template) {
            MessageTemplate::updateOrCreate(
                [
                    'code' => $template['code'],
                    'channel' => $template['channel'],
                    'locale' => $template['locale'],
                ],
                $template
            );
        }

        $this->command->info('Message templates seeded successfully.');
    }
}
