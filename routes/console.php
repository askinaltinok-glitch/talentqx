<?php

use App\Console\BrandRunner;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ===========================================
// QUEUE WORKER (process database queue jobs)
// ===========================================
// Octopus queue (default mysql connection)
Schedule::command('queue:work database --queue=emails --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/queue-worker.log'));

// TalentQX queue (mysql_talentqx connection)
Schedule::command('queue:work mysql_talentqx --queue=emails --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/queue-worker-talentqx.log'));

// ===========================================
// DAILY OPERATIONS REPORT (22:00 Istanbul) — Octopus only
// ===========================================

Schedule::command('reports:daily-octopus')
    ->dailyAt('22:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/daily-report.log'));

// ===========================================
// KVKK / DATA RETENTION SCHEDULED TASKS — Both brands
// ===========================================

// Process expired data based on retention policies (runs every night at 2 AM)
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('kvkk:process-retention'));
})->name('kvkk:process-retention:all-brands')
  ->dailyAt('02:00')
  ->withoutOverlapping();

// Process pending erasure requests (runs every hour)
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('kvkk:process-erasure-requests'));
})->name('kvkk:process-erasure-requests:all-brands')
  ->hourly()
  ->withoutOverlapping();

// Process employee data retention (runs every night at 3 AM)
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('kvkk:process-employee-retention'));
})->name('kvkk:process-employee-retention:all-brands')
  ->dailyAt('03:00')
  ->withoutOverlapping();

// Retention cleanup: delete incomplete >90d, anonymize completed >2y (runs daily at 3:15 AM)
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('retention:cleanup', ['--force' => true]));
})->name('retention:cleanup:all-brands')
  ->dailyAt('03:15')
  ->withoutOverlapping();

// Role-fit evaluations retention: purge records older than 180 days (daily at 03:30) — Octopus only
Schedule::command('maritime:role-fit:retention-cleanup --days=180 --batch=500 --force')
    ->dailyAt('03:30')
    ->environments(['production'])
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/role-fit-retention.log'));

// ===========================================
// MESSAGE OUTBOX WORKER — Both brands
// ===========================================

// Register outbox processing command
Artisan::command('outbox:process {--batch=50}', function (int $batch = 50) {
    dispatch(new \App\Jobs\ProcessOutboxMessagesJob($batch));
    $this->info("Outbox processing job dispatched (batch size: {$batch})");
})->purpose('Process pending outbox messages');

// Process outbox messages (runs every minute)
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('outbox:process', ['--batch' => 50]));
})->name('outbox:process:all-brands')
  ->everyMinute()
  ->withoutOverlapping();

// ===========================================
// INTERVIEW REMINDERS — Both brands
// ===========================================

// Send interview reminder emails (runs hourly)
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('interviews:send-reminders'));
})->name('interviews:send-reminders:all-brands')
  ->hourly()
  ->withoutOverlapping();

// Mark no-show interviews (runs every 5 minutes)
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('interviews:mark-no-show', ['--grace-minutes' => 10]));
})->name('interviews:mark-no-show:all-brands')
  ->everyFiveMinutes()
  ->withoutOverlapping();

// ===========================================
// CREDENTIAL EXPIRY REMINDERS — Both brands
// ===========================================

// Send credential expiry reminders (60/30/7/1 days before expiry)
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('credentials:send-reminders'));
})->name('credentials:send-reminders:all-brands')
  ->dailyAt('09:00')
  ->timezone('Europe/Istanbul')
  ->withoutOverlapping();

// ===========================================
// STCW CERTIFICATE EXPIRY CHECK — Octopus only
// ===========================================

Schedule::command('certificates:check-expiry --days=90')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/certificate-expiry.log'));

// ===========================================
// CRM MAILBOX POLLING (IMAP) — Both brands
// ===========================================

Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('crm:mailbox-poll', ['--mailbox' => 'all']));
})->name('crm:mailbox-poll:all-brands')
  ->everyTwoMinutes()
  ->withoutOverlapping();

// ===========================================
// MAIL AUTOPILOT — CRM SEQUENCES & OUTBOUND — Both brands
// ===========================================

// Process sequence steps (every 5 minutes)
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('crm:run-sequences'));
})->name('crm:run-sequences:all-brands')
  ->everyFiveMinutes()
  ->withoutOverlapping();

// Send approved queued mails (every minute)
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('crm:send-queued-mails', ['--batch' => 20]));
})->name('crm:send-queued-mails:all-brands')
  ->everyMinute()
  ->withoutOverlapping();

// ===========================================
// SALES ENGINE — STALE LEAD DETECTION — Both brands
// ===========================================

Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('crm:check-stale-leads'));
})->name('crm:check-stale-leads:all-brands')
  ->everySixHours()
  ->withoutOverlapping();

// ===========================================
// RESEARCH INTELLIGENCE AGENTS — Both brands
// ===========================================

// Domain enrichment: classify new research companies (every hour)
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('research:run-agent', ['agent' => 'domain_enrichment', '--sync' => true]));
})->name('research:domain-enrichment:all-brands')
  ->hourly()
  ->withoutOverlapping();

// Lead generator: push qualified companies to CRM (daily at 5 AM)
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('research:run-agent', ['agent' => 'lead_generator', '--sync' => true]));
})->name('research:lead-generator:all-brands')
  ->dailyAt('05:00')
  ->withoutOverlapping();

// ===========================================
// ML FAIRNESS REPORTING — Both brands
// ===========================================

Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('ml:fairness-report', ['--days' => 30]));
})->name('ml:fairness-report:all-brands')
  ->dailyAt('06:00')
  ->withoutOverlapping();

// ===========================================
// SYSTEM HEALTH & ML STABILITY — Both brands
// ===========================================

Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('system:health-check'));
})->name('system:health-check:all-brands')
  ->everyThirtyMinutes()
  ->withoutOverlapping();

Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('ml:stability-check', ['--days' => 7]));
})->name('ml:stability-check:all-brands')
  ->dailyAt('06:30')
  ->withoutOverlapping();

// ===========================================
// AIS VERIFICATION BATCH — Octopus only
// ===========================================

Schedule::command('trust:ais:verify-pending --limit=200')
    ->dailyAt('02:30')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ais-verify.log'));

// ===========================================
// SEA-TIME INTELLIGENCE BATCH — Octopus only
// ===========================================

Schedule::command('trust:sea-time:compute-pending --limit=500')
    ->dailyAt('03:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/sea-time-compute.log'));

// ===========================================
// RANK & STCW TECHNICAL SCORE BATCH — Octopus only
// ===========================================

Schedule::command('trust:rank-stcw:compute-pending --limit=500')
    ->dailyAt('03:30')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/rank-stcw-compute.log'));

// ===========================================
// STABILITY & RISK ENGINE BATCH — Octopus only
// ===========================================

Schedule::command('trust:stability:compute-pending --limit=500')
    ->dailyAt('04:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/stability-risk-compute.log'));

// ===========================================
// COMPLIANCE PACK BATCH — Octopus only
// ===========================================

Schedule::command('trust:compliance:compute-pending --limit=500')
    ->dailyAt('04:30')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/compliance-compute.log'));

// ===========================================
// COMPETENCY ENGINE BATCH — Octopus only
// ===========================================

Schedule::command('trust:competency:compute-pending --limit=500')
    ->dailyAt('05:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/competency-compute.log'));

// ===========================================
// PREDICTIVE RISK ENGINE BATCH — Octopus only
// ===========================================

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
// BEHAVIORAL INTERVIEW INVITES (every 30 min) — Octopus only
// ===========================================
Schedule::command('maritime:send-behavioral-invites')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/behavioral-invites.log'));

// ===========================================
// INTERVIEW INVITATION EXPIRY SWEEP (every 5 min) — Octopus only
// ===========================================
Schedule::command('maritime:expire-invitations')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/invitation-expiry.log'));

// ===========================================
// ROLE-FIT ALERT CHECK (every 30 minutes) — Octopus only
// ===========================================
Schedule::call(function () {
    app(\App\Services\Maritime\RoleFitAlertService::class)->check();
})->name('role-fit-alert-check')
  ->everyThirtyMinutes()
  ->environments(['production'])
  ->withoutOverlapping()
  ->onOneServer();

// ===========================================
// OBSERVABILITY LOG RETENTION (daily at 3:45 AM) — Octopus only
// ===========================================
// Prune maritime_invite_runs older than 90 days
Schedule::call(function () {
    $deleted = \Illuminate\Support\Facades\DB::table('maritime_invite_runs')
        ->where('started_at', '<', now()->subDays(90))
        ->delete();
    if ($deleted > 0) {
        \Illuminate\Support\Facades\Log::info("maritime_invite_runs: pruned {$deleted} rows older than 90 days");
    }
})->name('prune-maritime-invite-runs')
  ->dailyAt('03:45')
  ->withoutOverlapping();

// ===========================================
// WEEKLY JOB PULSE (every Monday 10:00 Istanbul) — Octopus only
// ===========================================
Schedule::command('maritime:weekly-job-pulse --limit=200')
    ->weeklyOn(1, '10:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/weekly-job-pulse.log'));

// ===========================================
// SUBSCRIPTION EXPIRY REMINDERS (daily at 09:30 Istanbul) — Both brands
// ===========================================
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('subscriptions:send-reminders'));
})->name('subscriptions:send-reminders:all-brands')
  ->dailyAt('09:30')
  ->timezone('Europe/Istanbul')
  ->withoutOverlapping();

// ===========================================
// CANDIDATE MEMBERSHIP EXPIRY (daily at 04:30 Istanbul) — Octopus only
// ===========================================
Schedule::command('memberships:check-expiry')
    ->dailyAt('04:30')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/membership-expiry.log'));

// ===========================================
// TRASH UNVERIFIED CANDIDATES (daily at 3:30 AM) — Both brands
// ===========================================
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('candidates:trash-unverified', ['--hours' => 48]));
})->name('candidates:trash-unverified:all-brands')
  ->dailyAt('03:30')
  ->timezone('Europe/Istanbul')
  ->withoutOverlapping();

// Reset monthly credits for all companies (runs at midnight on the 1st of each month) — Both brands
Schedule::call(function () {
    BrandRunner::forEachBrand(fn () => Artisan::call('credits:reset-monthly'));
})->name('credits:reset-monthly:all-brands')
  ->monthlyOn(1, '00:00')
  ->withoutOverlapping();

// ===========================================
// AUDIT LOG RETENTION CLEANUP (weekly Sunday 04:00 Istanbul)
// ===========================================
Schedule::command('retention:audit-cleanup --force')
    ->weeklyOn(0, '04:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/audit-retention.log'));

// ===========================================
// MARKETPLACE — Expire pending access requests with expired tokens (every 30 min)
// ===========================================
Schedule::command('marketplace:expire-requests')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/marketplace-expire.log'));

// ===========================================
// COMPANY PANEL — Appointment reminders (every minute)
// ===========================================
Schedule::job(new \App\Jobs\SendAppointmentReminderJob())
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
