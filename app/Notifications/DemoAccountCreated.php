<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\User;
use App\Support\BrandConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemoAccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $password,
        public Company $company
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $platform = $this->company->platform ?? 'octopus';
        $brandName = BrandConfig::brandName($platform);
        $supportEmail = BrandConfig::supportEmail($platform);
        $platformUrl = config('app.frontend_url', 'https://octopus-ai.net') . '/platform';
        $credits = $this->company->monthly_credits;

        return (new MailMessage)
            ->subject("{$brandName} Demo Hesabınız Hazır!")
            ->greeting("Merhaba {$this->user->first_name},")
            ->line("{$brandName}'e hoş geldiniz! Demo hesabınız başarıyla oluşturuldu.")
            ->line("---")
            ->line("**Giriş Bilgileriniz**")
            ->line("**Platform:** {$platformUrl}")
            ->line("**Email:** {$this->user->email}")
            ->line("**Şifre:** {$this->password}")
            ->line("---")
            ->action('Platforma Giriş Yap', $platformUrl)
            ->line("Güvenliğiniz için ilk girişinizde şifrenizi değiştirmeniz istenecektir.")
            ->line("---")
            ->line("**Demo Hesabınızda**")
            ->line("• **{$credits} mülakat kontürü** tanımlanmıştır")
            ->line("• Tüm temel özelliklere erişiminiz bulunmaktadır")
            ->line("• İlan oluşturabilir, QR kod ile başvuru alabilirsiniz")
            ->line("• Yapay zeka destekli mülakat analizi yapabilirsiniz")
            ->line("---")
            ->line("**Destek**")
            ->line("Sorularınız için: **{$supportEmail}**")
            ->line("Demo sürecinizde size yardımcı olmaktan mutluluk duyarız.")
            ->salutation("İyi çalışmalar,\n{$brandName} Ekibi");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ];
    }
}
