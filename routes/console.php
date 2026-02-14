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

// Retention cleanup: delete incomplete >90d, anonymize completed >2y (runs daily at 3:15 AM)
Schedule::command('retention:cleanup --force')
    ->dailyAt('03:15')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/retention-cleanup.log'));

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

// Mark no-show interviews (runs every 5 minutes)
// Marks interviews as no-show if candidate didn't join within grace period
Schedule::command('interviews:mark-no-show --grace-minutes=10')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/interview-noshow.log'));

// ===========================================
// STCW CERTIFICATE EXPIRY CHECK
// ===========================================

// Check for expiring/expired certificates (runs nightly at 4 AM)
// Marks expired certs, logs expiring-soon warnings, generates risk flags
Schedule::command('certificates:check-expiry --days=90')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/certificate-expiry.log'));

// ===========================================
// CRM MAILBOX POLLING (IMAP)
// ===========================================

// Poll all configured IMAP mailboxes for inbound reply matching (every 2 minutes)
Schedule::command('crm:mailbox-poll --mailbox=all')
    ->everyTwoMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crm-mailbox.log'));

// ===========================================
// CREDIT SYSTEM SCHEDULED TASKS
// ===========================================

// Reset monthly credits for all companies (runs at midnight on the 1st of each month)
Schedule::command('credits:reset-monthly')
    ->monthlyOn(1, '00:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/credits-reset.log'));
