<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ResearchRun extends Model
{
    use HasUuids;

    protected $table = 'research_runs';

    protected $fillable = [
        'agent_name', 'status', 'started_at', 'finished_at',
        'companies_found', 'signals_detected', 'leads_created',
        'meta', 'error_log',
    ];

    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    // Agent name constants
    public const AGENT_HIRING_SIGNAL = 'hiring_signal';
    public const AGENT_MARITIME_DISCOVERY = 'maritime_discovery';
    public const AGENT_DOMAIN_ENRICHMENT = 'domain_enrichment';
    public const AGENT_LEAD_GENERATOR = 'lead_generator';

    public const AGENTS = [
        self::AGENT_HIRING_SIGNAL, self::AGENT_MARITIME_DISCOVERY,
        self::AGENT_DOMAIN_ENRICHMENT, self::AGENT_LEAD_GENERATOR,
    ];

    // Status constants (same pattern as ResearchJob)
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_RUNNING,
        self::STATUS_COMPLETED, self::STATUS_FAILED,
    ];

    // Scopes

    public function scopeAgent($query, string $agent)
    {
        return $query->where('agent_name', $agent);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // Lifecycle methods (same pattern as ResearchJob)

    public function start(): void
    {
        $this->update(['status' => self::STATUS_RUNNING, 'started_at' => now()]);
    }

    public function complete(int $companiesFound = 0, int $signalsDetected = 0, int $leadsCreated = 0): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'finished_at' => now(),
            'companies_found' => $companiesFound,
            'signals_detected' => $signalsDetected,
            'leads_created' => $leadsCreated,
        ]);
    }

    public function fail(string $error = ''): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'finished_at' => now(),
            'error_log' => $error,
        ]);
    }
}
