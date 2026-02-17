<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewInvitationNotification extends Notification
{

    public function __construct(
        public Interview $interview,
        public string $type = 'written' // 'written' or 'video'
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

        $interviewUrl = $this->interview->getInterviewUrl();

        if ($this->type === 'video') {
            return $this->buildVideoInvitation($candidate, $job, $company, $interviewUrl);
        }

        return $this->buildWrittenInvitation($candidate, $job, $company, $interviewUrl);
    }

    private function buildWrittenInvitation($candidate, $job, $company, $interviewUrl): MailMessage
    {
        return (new MailMessage)
            ->subject("Octopus AI – {$company->name} | Yazılı Mülakat Daveti – {$job->title}")
            ->greeting("Merhaba {$candidate->first_name},")
            ->line("{$company->name} bünyesindeki **{$job->title}** pozisyonu için başvurunuz değerlendirme sürecine alınmıştır.")
            ->line("Bu aşamada sizi yazılı mülakat aşamasına davet ediyoruz.")
            ->line("---")
            ->line("**📝 Yazılı Mülakat Hakkında**")
            ->line("• Mülakat self-servistir.")
            ->line("• Aşağıdaki bağlantı üzerinden size uygun bir zamanda giriş yapabilirsiniz.")
            ->line("• Sorulara verdiğiniz yanıtlar, yalnızca başvurunuzun değerlendirilmesi amacıyla kullanılacaktır.")
            ->line("---")
            ->action('Mülakata Başla', $interviewUrl)
            ->line("⏱️ Tahmini süre: 15–20 dakika")
            ->line("---")
            ->line("**🔒 Gizlilik ve Bilgilendirme**")
            ->line("6698 sayılı KVKK kapsamında, verdiğiniz yanıtlar güvenli şekilde saklanacak ve yalnızca yetkili İK ekibi tarafından değerlendirilecektir.")
            ->line("Yanıtlarınız, yapay zeka destekli analiz sistemleriyle incelenebilir; nihai değerlendirme her zaman insan kaynakları tarafından yapılır.")
            ->salutation("Başarılar dileriz,\n{$company->name} İnsan Kaynakları");
    }

    private function buildVideoInvitation($candidate, $job, $company, $interviewUrl): MailMessage
    {
        $scheduledAt = $this->interview->scheduled_at;
        $dateStr = $scheduledAt ? $scheduledAt->format('d.m.Y') : 'Belirtilecek';
        $timeStr = $scheduledAt ? $scheduledAt->format('H:i') : 'Belirtilecek';

        return (new MailMessage)
            ->subject("Octopus AI – {$company->name} | Görüntülü Mülakat Daveti – {$job->title}")
            ->greeting("Merhaba {$candidate->first_name},")
            ->line("{$company->name} için yürütülen **{$job->title}** değerlendirme sürecinde sizi görüntülü mülakat aşamasına davet etmekten memnuniyet duyarız.")
            ->line("---")
            ->line("**📅 Mülakat Bilgileri**")
            ->line("• Tarih: {$dateStr}")
            ->line("• Saat: {$timeStr}")
            ->line("• Görüşme Türü: Görüntülü mülakat")
            ->line("---")
            ->action('Görüşmeye Katıl', $interviewUrl)
            ->line("Lütfen görüşmeye randevu saatinde katılmaya özen gösteriniz.")
            ->line("---")
            ->line("**⏱️ Önemli Bilgilendirme**")
            ->line("• Zamanında katılım, mülakat sürecinin sağlıklı ilerlemesi için önemlidir.")
            ->line("• Olası gecikmeler değerlendirme raporunda bilgilendirici bir gözlem olarak yer alabilir.")
            ->line("---")
            ->line("**🔒 Gizlilik ve KVKK**")
            ->line("Görüşme sürecinde alınan ses/görüntü kayıtları ve yanıtlar, KVKK kapsamında korunur.")
            ->line("Yapay zeka destekli analizler yalnızca verdiğiniz yanıtlarla sınırlıdır; nihai karar insan kaynakları tarafından verilir.")
            ->salutation("Görüşmek üzere,\n{$company->name} İnsan Kaynakları");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'interview_id' => $this->interview->id,
            'type' => $this->type,
        ];
    }
}
