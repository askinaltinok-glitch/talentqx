<?php

namespace App\Mail;

use App\Models\CompanyPanelAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public CompanyPanelAppointment $appointment
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "TalentQX — Randevu Hatırlatması: {$this->appointment->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-reminder',
            with: [
                'appointment' => $this->appointment,
                'lead' => $this->appointment->lead,
                'startsAt' => $this->appointment->starts_at->format('d.m.Y H:i'),
            ],
        );
    }
}
