<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ===========================================
// KVKK / DATA RETENTION SCHEDULED TASKS
// ===========================================

// Process expired data based on retention policies (runs every night at 2 AM)
Schedule::command('kvkk:process-retention')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/retention.log'));

// Process pending erasure requests (runs every hour)
Schedule::command('kvkk:process-erasure-requests')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/erasure.log'));

// Process employee data retention (runs every night at 3 AM)
Schedule::command('kvkk:process-employee-retention')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/employee-retention.log'));

// ===========================================
// MESSAGE OUTBOX WORKER
// ===========================================

// Register outbox processing command
Artisan::command('outbox:process {--batch=50}', function (int $batch = 50) {
    dispatch(new \App\Jobs\ProcessOutboxMessagesJob($batch));
    $this->info("Outbox processing job dispatched (batch size: {$batch})");
})->purpose('Process pending outbox messages');

// Process outbox messages (runs every minute)
Schedule::command('outbox:process --batch=50')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/outbox.log'));

// ===========================================
// INTERVIEW REMINDERS
// ===========================================

// Send interview reminder emails (runs hourly)
// Sends reminders for interviews expiring within 24 hours
Schedule::command('interviews:send-reminders')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/interview-reminders.log'));
