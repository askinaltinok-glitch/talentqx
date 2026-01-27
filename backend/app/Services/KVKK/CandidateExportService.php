<?php

namespace App\Services\KVKK;

use App\Models\AuditLog;
use App\Models\Candidate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CandidateExportService
{
    /**
     * Export candidate data as JSON
     */
    public function exportAsJson(Candidate $candidate): array
    {
        $data = $this->gatherCandidateData($candidate);

        // Log the export
        AuditLog::log('export', $candidate, null, null);

        return $data;
    }

    /**
     * Export candidate data as PDF
     */
    public function exportAsPdf(Candidate $candidate): string
    {
        $data = $this->gatherCandidateData($candidate);

        $pdf = Pdf::loadView('exports.candidate-data', [
            'candidate' => $candidate,
            'data' => $data,
            'exportDate' => now()->format('d.m.Y H:i'),
        ]);

        $filename = 'exports/candidate_' . $candidate->id . '_' . now()->format('Ymd_His') . '.pdf';
        Storage::disk('local')->put($filename, $pdf->output());

        // Log the export
        AuditLog::log('export', $candidate, null, ['format' => 'pdf']);

        return $filename;
    }

    /**
     * Gather all candidate data for export
     */
    protected function gatherCandidateData(Candidate $candidate): array
    {
        $candidate->load([
            'job',
            'interviews.responses.question',
            'interviews.analysis',
        ]);

        return [
            'export_info' => [
                'exported_at' => now()->toIso8601String(),
                'data_subject' => 'Aday Kisisel Verileri',
                'legal_basis' => 'KVKK Madde 11 - Ilgili Kisinin Haklari',
            ],
            'personal_info' => [
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'application_date' => $candidate->created_at->toIso8601String(),
                'status' => $candidate->status,
            ],
            'application_info' => [
                'job_title' => $candidate->job->title ?? null,
                'job_location' => $candidate->job->location ?? null,
                'source' => $candidate->source,
                'consent_given' => $candidate->consent_given,
                'consent_version' => $candidate->consent_version,
            ],
            'cv_data' => $candidate->cv_parsed_data,
            'interviews' => $candidate->interviews->map(function ($interview) {
                return [
                    'id' => $interview->id,
                    'status' => $interview->status,
                    'started_at' => $interview->started_at?->toIso8601String(),
                    'completed_at' => $interview->completed_at?->toIso8601String(),
                    'duration_seconds' => $interview->duration_seconds,
                    'responses' => $interview->responses->map(function ($response) {
                        return [
                            'question' => $response->question->question_text ?? null,
                            'transcript' => $response->transcript,
                            'duration_seconds' => $response->duration_seconds,
                        ];
                    })->toArray(),
                    'analysis' => $interview->analysis ? [
                        'overall_score' => $interview->analysis->overall_score,
                        'recommendation' => $interview->analysis->decision_snapshot['recommendation'] ?? null,
                        'competency_scores' => $interview->analysis->competency_scores,
                        'analyzed_at' => $interview->analysis->analyzed_at?->toIso8601String(),
                    ] : null,
                ];
            })->toArray(),
            'audit_trail' => AuditLog::forEntity(Candidate::class, $candidate->id)
                ->orderBy('created_at')
                ->get()
                ->map(function ($log) {
                    return [
                        'action' => $log->action,
                        'timestamp' => $log->created_at->toIso8601String(),
                        'ip_address' => $log->ip_address,
                    ];
                })->toArray(),
            'data_retention' => [
                'retention_days' => $candidate->job->retention_days ?? 180,
                'scheduled_deletion' => $candidate->created_at
                    ->addDays($candidate->job->retention_days ?? 180)
                    ->toIso8601String(),
            ],
        ];
    }
}
