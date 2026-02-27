<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssessmentCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Interview $interview
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $candidate = $this->interview->candidate;
        $job = $this->interview->job;
        $company = $job->company;

        $brandName = ($company->platform ?? 'octopus') === 'octopus' ? 'Octopus AI' : 'TalentQX';

        return (new MailMessage)
            ->subject("{$brandName} – Değerlendirmeniz tamamlandı – {$job->title}")
            ->greeting("Merhaba {$candidate->first_name},")
            ->line("{$company->name} bünyesindeki **{$job->title}** pozisyonu için tamamladığınız mülakatın değerlendirmesi sonuçlanmıştır.")
            ->line("Sonuçlarınız İK ekibimiz tarafından incelenmektedir. Süreçle ilgili gelişmeler size ayrıca bildirilecektir.")
            ->line("---")
            ->line("**🔒 Gizlilik Notu**")
            ->line("Değerlendirme yapay zeka destekli analiz sistemleriyle hazırlanmıştır. Nihai karar her zaman insan kaynakları tarafından verilmektedir.")
            ->line("---")
            ->line("Katılımınız için teşekkür ederiz.")
            ->salutation("Saygılarımızla,\n{$company->name} İnsan Kaynakları");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'interview_id' => $this->interview->id,
        ];
    }
}
