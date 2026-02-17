<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Interview $interview,
        public string $reminderType = 'T-24' // 'T-24' or 'T-1'
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

        $scheduledAt = $this->interview->scheduled_at ?? $this->interview->token_expires_at;
        $dateStr = $scheduledAt ? $scheduledAt->format('d.m.Y') : '';
        $timeStr = $scheduledAt ? $scheduledAt->format('H:i') : '';

        if ($this->reminderType === 'T-1') {
            return $this->buildT1Reminder($candidate, $company, $job, $interviewUrl, $timeStr);
        }

        return $this->buildT24Reminder($candidate, $company, $job, $interviewUrl, $dateStr, $timeStr);
    }

    private function buildT24Reminder($candidate, $company, $job, $interviewUrl, $dateStr, $timeStr): MailMessage
    {
        return (new MailMessage)
            ->subject("Octopus AI – Hatırlatma: Yarın mülakatınız var – {$job->title}")
            ->greeting("Merhaba {$candidate->first_name},")
            ->line("{$company->name} için başvurduğunuz **{$job->title}** pozisyonuna ait mülakatınız **yarın** gerçekleşecektir.")
            ->line("---")
            ->line("📅 **Tarih:** {$dateStr}")
            ->line("⏰ **Saat:** {$timeStr}")
            ->line("---")
            ->action('Mülakat Bağlantısı', $interviewUrl)
            ->line("**Hazırlık Önerileri:**")
            ->line("• Sessiz ve iyi aydınlatılmış bir ortam tercih edin")
            ->line("• İnternet bağlantınızı önceden test edin")
            ->line("• Bağlantıyı görüşmeden 5 dakika önce açın")
            ->line("---")
            ->line("Katılamayacaksanız lütfen bu e-postaya yanıt vererek bilgilendiriniz.")
            ->salutation("Başarılar,\n{$company->name} İnsan Kaynakları");
    }

    private function buildT1Reminder($candidate, $company, $job, $interviewUrl, $timeStr): MailMessage
    {
        return (new MailMessage)
            ->subject("Octopus AI – ⏰ 1 saat sonra: Mülakatınız başlıyor")
            ->greeting("Merhaba {$candidate->first_name},")
            ->line("**{$job->title}** mülakatınız **1 saat sonra** başlayacak.")
            ->line("---")
            ->line("⏰ **Saat:** {$timeStr}")
            ->line("---")
            ->action('Mülakata Katıl', $interviewUrl)
            ->line("Bağlantıyı şimdiden test edebilirsiniz.")
            ->salutation("Görüşmek üzere,\n{$company->name} İK");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'interview_id' => $this->interview->id,
            'reminder_type' => $this->reminderType,
        ];
    }
}
