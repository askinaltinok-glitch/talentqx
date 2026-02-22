<?php

namespace App\Console\Commands\Reports;

use App\Helpers\LineChartGenerator;
use App\Models\SystemReportLog;
use App\Services\Reports\DailyReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DailyOctopusReport extends Command
{
    protected $signature = 'reports:daily-octopus
                            {--date= : Report date (Y-m-d), defaults to today}
                            {--to= : Override recipient email}
                            {--dry-run : Collect metrics without sending}';

    protected $description = 'Send daily Octopus AI operations report email';

    private const DEFAULT_RECIPIENT = 'askinaltinok@gmail.com';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : now();

        $to = $this->option('to') ?: self::DEFAULT_RECIPIENT;
        $isDryRun = $this->option('dry-run');

        $this->info("Collecting metrics for {$date->format('Y-m-d')}...");

        // Step 1: Collect all metrics
        $service = new DailyReportService($date);
        $metrics = $service->collect();

        $this->info("  Candidates: {$metrics['candidates']['today_new']}");
        $this->info("  Interviews completed: {$metrics['interviews']['today_completed']}");
        $this->info("  Credits used: {$metrics['company']['credits_used_today']}");

        if ($isDryRun) {
            $this->info(json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        // Step 2: Generate charts
        $trend = $metrics['monthly_trend'];
        $trendChart = LineChartGenerator::generate(
            $trend['current_month'],
            $trend['previous_month'],
            "Günlük Başvuru Trendi — " . $date->format('F Y'),
            'Bu Ay',
            'Geçen Ay',
        );

        $positionChart = '';
        if (!empty($metrics['candidates']['by_position'])) {
            $positionChart = LineChartGenerator::barChart(
                $metrics['candidates']['by_position'],
                'Pozisyon Dağılımı',
            );
        }

        $sourceChart = '';
        if (!empty($metrics['candidates']['by_source'])) {
            $sourceChart = LineChartGenerator::barChart(
                $metrics['candidates']['by_source'],
                'Kaynak Dağılımı',
            );
        }

        // Step 3: Create log entry
        $subject = "Octopus AI — Günlük Rapor — {$date->format('d.m.Y')}";
        $log = SystemReportLog::create([
            'report_type' => 'daily_octopus',
            'report_date' => $date->format('Y-m-d'),
            'to_email' => $to,
            'subject' => $subject,
            'status' => 'sending',
            'metrics_snapshot' => $metrics,
        ]);

        // Step 4: Send email
        try {
            Mail::send('emails.daily-octopus-report', [
                'metrics' => $metrics,
                'trendChart' => $trendChart,
                'positionChart' => $positionChart,
                'sourceChart' => $sourceChart,
                'date' => $date,
            ], function ($msg) use ($to, $subject) {
                $msg->to($to)
                    ->subject($subject);
            });

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $this->info("Report sent to {$to}");
            Log::info('DailyOctopusReport sent', [
                'to' => $to,
                'date' => $date->format('Y-m-d'),
                'log_id' => $log->id,
            ]);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 1000),
            ]);

            $this->error("Failed: {$e->getMessage()}");
            Log::error('DailyOctopusReport failed', [
                'to' => $to,
                'error' => $e->getMessage(),
                'log_id' => $log->id,
            ]);

            return self::FAILURE;
        }
    }
}
