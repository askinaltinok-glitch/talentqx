<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ===========================================
// QUEUE WORKER (process database queue jobs)
// ===========================================

Schedule::command('queue:work database --queue=emails --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/queue-worker.log'));

// ===========================================
// DAILY OPERATIONS REPORT (22:00 Istanbul)
// ===========================================

Schedule::command('reports:daily-octopus')
    ->dailyAt('22:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/daily-report.log'));

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
// CREDENTIAL EXPIRY REMINDERS
// ===========================================

// Send credential expiry reminders (60/30/7/1 days before expiry)
// Only sends to candidates with reminders_opt_in=true AND verified email
Schedule::command('credentials:send-reminders')
    ->dailyAt('09:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/credential-reminders.log'));

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
// MAIL AUTOPILOT — CRM SEQUENCES & OUTBOUND
// ===========================================

// Process sequence steps (every 5 minutes)
Schedule::command('crm:run-sequences')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crm-sequences.log'));

// Send approved queued mails (every minute)
Schedule::command('crm:send-queued-mails --batch=20')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/crm-outbound.log'));

// ===========================================
// SALES ENGINE — STALE LEAD DETECTION
// ===========================================

// Check for stale leads and fire no-reply triggers (every 6 hours)
Schedule::command('crm:check-stale-leads')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crm-stale-leads.log'));

// ===========================================
// RESEARCH INTELLIGENCE AGENTS
// ===========================================

// Domain enrichment: classify new research companies (every hour)
Schedule::command('research:run-agent domain_enrichment --sync')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/research-enrichment.log'));

// Lead generator: push qualified companies to CRM (daily at 5 AM)
Schedule::command('research:run-agent lead_generator --sync')
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/research-leads.log'));

// ===========================================
// ML FAIRNESS REPORTING
// ===========================================

// Generate ML fairness reports (runs daily at 6 AM)
Schedule::command('ml:fairness-report --days=30')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ml-fairness.log'));

// ===========================================
// SYSTEM HEALTH & ML STABILITY
// ===========================================

// System health check (runs every 30 minutes)
Schedule::command('system:health-check')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/system-health.log'));

// ML stability check (runs daily at 6:30 AM)
Schedule::command('ml:stability-check --days=7')
    ->dailyAt('06:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ml-stability.log'));

// ===========================================
// AIS VERIFICATION BATCH
// ===========================================

// Verify pending contracts via AIS engine (runs daily at 2:30 AM Istanbul)
Schedule::command('trust:ais:verify-pending --limit=200')
    ->dailyAt('02:30')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ais-verify.log'));

// ===========================================
// SEA-TIME INTELLIGENCE BATCH
// ===========================================

// Compute sea-time intelligence for candidates with updated contracts (runs daily at 3:00 AM Istanbul)
Schedule::command('trust:sea-time:compute-pending --limit=500')
    ->dailyAt('03:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/sea-time-compute.log'));

// ===========================================
// RANK & STCW TECHNICAL SCORE BATCH
// ===========================================

// Compute Rank & STCW technical scores for candidates with updated contracts (runs daily at 3:30 AM Istanbul)
Schedule::command('trust:rank-stcw:compute-pending --limit=500')
    ->dailyAt('03:30')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/rank-stcw-compute.log'));

// ===========================================
// STABILITY & RISK ENGINE BATCH
// ===========================================

// Compute stability & risk scores for candidates with updated contracts (runs daily at 4:00 AM Istanbul)
Schedule::command('trust:stability:compute-pending --limit=500')
    ->dailyAt('04:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/stability-risk-compute.log'));

// ===========================================
// COMPLIANCE PACK BATCH
// ===========================================

// Compute compliance pack for candidates with CRI scores (runs daily at 4:30 AM Istanbul)
Schedule::command('trust:compliance:compute-pending --limit=500')
    ->dailyAt('04:30')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/compliance-compute.log'));

// ===========================================
// COMPETENCY ENGINE BATCH
// ===========================================

// Compute competency assessments for candidates with completed interviews (runs daily at 5:00 AM Istanbul)
Schedule::command('trust:competency:compute-pending --limit=500')
    ->dailyAt('05:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/competency-compute.log'));

// ===========================================
// PREDICTIVE RISK ENGINE BATCH
// ===========================================

// Compute predictive risk for candidates with risk profiles (runs daily at 5:30 AM Istanbul)
Schedule::command('trust:predictive:compute-pending --limit=500')
    ->dailyAt('05:30')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/predictive-risk-compute.log'));

// ===========================================
// CREDIT SYSTEM SCHEDULED TASKS
// ===========================================

// ===========================================
// BEHAVIORAL INTERVIEW INVITES (every 30 min)
// ===========================================
Schedule::command('maritime:send-behavioral-invites')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/behavioral-invites.log'));

// Reset monthly credits for all companies (runs at midnight on the 1st of each month)
Schedule::command('credits:reset-monthly')
    ->monthlyOn(1, '00:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/credits-reset.log'));
