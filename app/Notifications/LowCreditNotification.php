<?php

namespace App\Notifications;

use App\Models\Company;
use App\Support\BrandConfig;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowCreditNotification extends Notification
{
    public function __construct(
        public Company $company,
        public int $remaining,
        public int $total,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $percentage = $this->total > 0 ? round(($this->remaining / $this->total) * 100) : 0;
        $billingUrl = config('app.frontend_url', 'https://octopus-ai.net') . '/portal/billing';
        $brandName = BrandConfig::brandName($this->company->platform ?? 'octopus');

        return (new MailMessage)
            ->subject("Kontürünüz Azalıyor — {$this->company->name}")
            ->view('emails.low-credit-warning', [
                'company' => $this->company,
                'remaining' => $this->remaining,
                'total' => $this->total,
                'percentage' => $percentage,
                'billingUrl' => $billingUrl,
                'userName' => $notifiable->first_name ?? $notifiable->name ?? 'Sayın Kullanıcı',
                'brandName' => $brandName,
            ]);
    }
}
