<?php

namespace App\Jobs;

use App\Models\CandidateContract;
use App\Services\Ais\ContractAisVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Traits\BrandAware;
use Illuminate\Support\Facades\Log;

class VerifyContractAisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use BrandAware;

    public int $tries = 3;
    public array $backoff = [30, 60];
    public int $timeout = 120;

    public function __construct(
        private string $contractId,
        private string $triggeredBy = 'system',
        private ?string $userId = null,
    ) {
        $this->onQueue('default');
        $this->captureBrand();
    }

    public function handle(ContractAisVerificationService $service): void
    {
        $this->setBrandDatabase();
        $contract = CandidateContract::find($this->contractId);

        if (!$contract) {
            Log::channel('daily')->warning('[AIS] Contract not found for verification', [
                'contract_id' => $this->contractId,
            ]);
            return;
        }

        $result = $service->verify($contract, $this->triggeredBy, $this->userId);

        if ($result) {
            Log::channel('daily')->info('[AIS] Verification completed', [
                'contract_id' => $this->contractId,
                'status' => $result->status,
                'confidence' => $result->confidence_score,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('daily')->error('[AIS] Verification job failed', [
            'contract_id' => $this->contractId,
            'error' => $e->getMessage(),
        ]);
    }
}
