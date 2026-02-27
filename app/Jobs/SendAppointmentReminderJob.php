<?php

namespace App\Jobs;

use App\Mail\AppointmentReminderMail;
use App\Models\CompanyPanelAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAppointmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Find appointments starting in the next 30-31 minutes that haven't been reminded
        $appointments = CompanyPanelAppointment::with(['salesRep', 'lead'])
            ->where('status', 'scheduled')
            ->where('reminder_sent', false)
            ->whereBetween('starts_at', [now()->addMinutes(29), now()->addMinutes(31)])
            ->get();

        foreach ($appointments as $appointment) {
            $rep = $appointment->salesRep;
            if (!$rep || !$rep->email) {
                continue;
            }

            try {
                Mail::to($rep->email)->send(new AppointmentReminderMail($appointment));
                $appointment->update(['reminder_sent' => true]);

                Log::info('Appointment reminder sent', [
                    'appointment_id' => $appointment->id,
                    'sales_rep' => $rep->email,
                    'starts_at' => $appointment->starts_at,
                ]);
            } catch (\Throwable $e) {
                Log::error('Appointment reminder failed', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
