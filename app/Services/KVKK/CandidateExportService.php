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
     * Export candidate data as CSV
     */
    public function exportAsCsv(Candidate $candidate): string
    {
        $data = $this->gatherCandidateData($candidate);
        $csvContent = $this->generateCsvContent($candidate, $data);

        $filename = 'exports/candidate_' . $candidate->id . '_' . now()->format('Ymd_His') . '.csv';
        Storage::disk('local')->put($filename, $csvContent);

        // Log the export
        AuditLog::log('export', $candidate, null, ['format' => 'csv']);

        return $filename;
    }

    /**
     * Generate CSV content from candidate data
     */
    protected function generateCsvContent(Candidate $candidate, array $data): string
    {
        $lines = [];

        // Add BOM for UTF-8 Excel compatibility
        $bom = "\xEF\xBB\xBF";

        // Export info header
        $lines[] = '# ADAY VERİ DIŞA AKTARIM RAPORU';
        $lines[] = '# Dışa Aktarım Tarihi: ' . $data['export_info']['exported_at'];
        $lines[] = '# Yasal Dayanak: ' . $data['export_info']['legal_basis'];
        $lines[] = '';

        // Personal info section
        $lines[] = '## KİŞİSEL BİLGİLER';
        $lines[] = $this->csvLine(['Alan', 'Değer']);
        $lines[] = $this->csvLine(['Ad', $data['personal_info']['first_name']]);
        $lines[] = $this->csvLine(['Soyad', $data['personal_info']['last_name']]);
        $lines[] = $this->csvLine(['E-posta', $data['personal_info']['email']]);
        $lines[] = $this->csvLine(['Telefon', $data['personal_info']['phone']]);
        $lines[] = $this->csvLine(['Başvuru Tarihi', $data['personal_info']['application_date']]);
        $lines[] = $this->csvLine(['Durum', $data['personal_info']['status']]);
        $lines[] = '';

        // Application info section
        $lines[] = '## BAŞVURU BİLGİLERİ';
        $lines[] = $this->csvLine(['Alan', 'Değer']);
        $lines[] = $this->csvLine(['Pozisyon', $data['application_info']['job_title'] ?? '-']);
        $lines[] = $this->csvLine(['Lokasyon', $data['application_info']['job_location'] ?? '-']);
        $lines[] = $this->csvLine(['Kaynak', $data['application_info']['source']]);
        $lines[] = $this->csvLine(['KVKK Onayı', $data['application_info']['consent_given'] ? 'Evet' : 'Hayır']);
        $lines[] = $this->csvLine(['Onay Versiyonu', $data['application_info']['consent_version'] ?? '-']);
        $lines[] = '';

        // Interviews section
        if (!empty($data['interviews'])) {
            $lines[] = '## MÜLAKATLAR';
            $lines[] = $this->csvLine(['Mülakat ID', 'Durum', 'Başlangıç', 'Bitiş', 'Süre (sn)', 'Puan', 'Öneri']);

            foreach ($data['interviews'] as $interview) {
                $lines[] = $this->csvLine([
                    $interview['id'],
                    $interview['status'],
                    $interview['started_at'] ?? '-',
                    $interview['completed_at'] ?? '-',
                    $interview['duration_seconds'] ?? '-',
                    $interview['analysis']['overall_score'] ?? '-',
                    $interview['analysis']['recommendation'] ?? '-',
                ]);
            }
            $lines[] = '';
        }

        // Data retention section
        $lines[] = '## VERİ SAKLAMA';
        $lines[] = $this->csvLine(['Alan', 'Değer']);
        $lines[] = $this->csvLine(['Saklama Süresi (gün)', $data['data_retention']['retention_days']]);
        $lines[] = $this->csvLine(['Planlanan Silme Tarihi', $data['data_retention']['scheduled_deletion']]);
        $lines[] = '';

        // Audit trail section
        if (!empty($data['audit_trail'])) {
            $lines[] = '## İŞLEM GEÇMİŞİ';
            $lines[] = $this->csvLine(['İşlem', 'Tarih', 'IP Adresi']);

            foreach ($data['audit_trail'] as $log) {
                $lines[] = $this->csvLine([
                    $log['action'],
                    $log['timestamp'],
                    $log['ip_address'] ?? '-',
                ]);
            }
        }

        return $bom . implode("\n", $lines);
    }

    /**
     * Format an array as a CSV line
     */
    protected function csvLine(array $values): string
    {
        $escaped = array_map(function ($value) {
            // Handle null values
            if ($value === null) {
                return '';
            }
            // Convert to string
            $value = (string) $value;
            // Escape quotes and wrap in quotes if contains special chars
            if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
                return '"' . str_replace('"', '""', $value) . '"';
            }
            return $value;
        }, $values);

        return implode(',', $escaped);
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
