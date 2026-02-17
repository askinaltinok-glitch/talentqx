<?php

namespace App\Console\Commands;

use App\Services\Certification\CertificationService;
use Illuminate\Console\Command;

/**
 * Nightly job: detect expiring certificates, mark expired, flag risks.
 *
 * Schedule: daily at 4 AM
 * Detects certificates expiring within 90 days.
 */
class CheckCertificateExpiry extends Command
{
    protected $signature = 'certificates:check-expiry
        {--days=90 : Warning threshold in days}
        {--dry-run : Preview without making changes}';

    protected $description = 'Check for expiring/expired seafarer certificates and generate risk flags';

    public function handle(CertificationService $service): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Checking certificate expiry (threshold: {$days} days)...");

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
        }

        if ($dryRun) {
            // Just report without modifying
            $expired = \App\Models\SeafarerCertificate::where('verification_status', 'verified')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->count();

            $expiringSoon = \App\Models\SeafarerCertificate::where('verification_status', 'verified')
                ->expiringSoon($days)
                ->count();

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Expired (not yet marked)', $expired],
                    ["Expiring within {$days} days", $expiringSoon],
                ]
            );

            return self::SUCCESS;
        }

        $results = $service->processExpiryCheck($days);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Newly expired (marked)', $results['newly_expired']],
                ["Expiring within {$days} days", $results['expiring_soon']],
            ]
        );

        if ($results['newly_expired'] > 0) {
            $this->warn("{$results['newly_expired']} certificate(s) marked as expired.");
        }

        $this->info('Certificate expiry check complete.');

        return self::SUCCESS;
    }
}
