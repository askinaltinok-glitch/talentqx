<?php

namespace App\Http\Controllers\Api\Admin\ML;

use App\Http\Controllers\Controller;
use App\Models\ModelFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatasetController extends Controller
{
    /**
     * Export training dataset.
     *
     * GET /v1/admin/ml/dataset/export?from=YYYY-MM-DD&to=YYYY-MM-DD&industry=...&format=csv|jsonl
     */
    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'industry' => ['nullable', 'string', 'max:64'],
            'format' => ['nullable', 'string', 'in:csv,jsonl'],
        ]);

        $from = $data['from'] ?? now()->subDays(90)->toDateString();
        $to = $data['to'] ?? now()->toDateString();
        $format = $data['format'] ?? 'jsonl';

        $query = DB::table('model_features as mf')
            ->leftJoin('interview_outcomes as io', 'io.form_interview_id', '=', 'mf.form_interview_id')
            ->whereBetween('mf.created_at', [$from, $to . ' 23:59:59']);

        if (!empty($data['industry'])) {
            $query->where('mf.industry_code', $data['industry']);
        }

        $query->select([
            // Features
            'mf.form_interview_id',
            'mf.industry_code',
            'mf.position_code',
            'mf.language',
            'mf.country_code',
            'mf.source_channel',
            'mf.competency_scores_json',
            'mf.risk_flags_json',
            'mf.raw_final_score',
            'mf.calibrated_score',
            'mf.z_score',
            'mf.policy_decision',
            'mf.policy_code',
            'mf.answers_meta_json',
            'mf.created_at as feature_created_at',
            // Labels (outcome)
            'io.outcome_score',
            'io.hired',
            'io.started',
            'io.still_employed_30d',
            'io.still_employed_90d',
            'io.incident_flag',
            'io.performance_rating',
        ]);

        $filename = "dataset_{$from}_{$to}.{$format}";

        if ($format === 'csv') {
            return $this->exportCsv($query, $filename);
        }

        return $this->exportJsonl($query, $filename);
    }

    /**
     * Export as CSV.
     */
    protected function exportCsv($query, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Header row
            $headerWritten = false;

            $query->orderBy('mf.created_at')->chunk(500, function ($rows) use ($handle, &$headerWritten) {
                foreach ($rows as $row) {
                    $flat = $this->flattenRow($row);

                    if (!$headerWritten) {
                        fputcsv($handle, array_keys($flat));
                        $headerWritten = true;
                    }

                    fputcsv($handle, array_values($flat));
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export as JSONL (one JSON object per line).
     */
    protected function exportJsonl($query, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'application/jsonl',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            $query->orderBy('mf.created_at')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    $data = $this->structureRow($row);
                    fwrite($handle, json_encode($data, JSON_UNESCAPED_UNICODE) . "\n");
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Flatten row for CSV export.
     */
    protected function flattenRow(object $row): array
    {
        // Parse JSON fields
        $competencyScores = json_decode($row->competency_scores_json ?? '{}', true) ?: [];
        $riskFlags = json_decode($row->risk_flags_json ?? '[]', true) ?: [];
        $answersMeta = json_decode($row->answers_meta_json ?? '{}', true) ?: [];

        // Extract risk flag codes as comma-separated
        $riskFlagCodes = [];
        foreach ($riskFlags as $flag) {
            $code = is_array($flag) ? ($flag['code'] ?? null) : $flag;
            if ($code) {
                $riskFlagCodes[] = $code;
            }
        }

        return [
            'form_interview_id' => $row->form_interview_id,
            'industry_code' => $row->industry_code,
            'position_code' => $row->position_code,
            'language' => $row->language,
            'country_code' => $row->country_code,
            'source_channel' => $row->source_channel,
            'raw_final_score' => $row->raw_final_score,
            'calibrated_score' => $row->calibrated_score,
            'z_score' => $row->z_score,
            'policy_decision' => $row->policy_decision,
            'policy_code' => $row->policy_code,
            // Flattened competency scores
            'competency_count' => count($competencyScores),
            'competency_avg' => count($competencyScores) > 0 ? round(array_sum($competencyScores) / count($competencyScores), 2) : null,
            // Risk flags
            'risk_flag_count' => count($riskFlagCodes),
            'risk_flags' => implode(',', $riskFlagCodes),
            // Answer meta
            'answer_count' => $answersMeta['answer_count'] ?? null,
            'answers_with_text' => $answersMeta['answers_with_text'] ?? null,
            'avg_answer_len' => $answersMeta['avg_answer_len'] ?? null,
            'rf_incomplete' => ($answersMeta['rf_incomplete'] ?? false) ? 1 : 0,
            'rf_sparse' => ($answersMeta['rf_sparse'] ?? false) ? 1 : 0,
            // Timestamp
            'created_at' => $row->feature_created_at,
            // Labels
            'outcome_score' => $row->outcome_score,
            'hired' => $row->hired,
            'started' => $row->started,
            'still_employed_30d' => $row->still_employed_30d,
            'still_employed_90d' => $row->still_employed_90d,
            'incident_flag' => $row->incident_flag,
            'performance_rating' => $row->performance_rating,
        ];
    }

    /**
     * Structure row for JSONL export.
     */
    protected function structureRow(object $row): array
    {
        return [
            'features' => [
                'form_interview_id' => $row->form_interview_id,
                'industry_code' => $row->industry_code,
                'position_code' => $row->position_code,
                'language' => $row->language,
                'country_code' => $row->country_code,
                'source_channel' => $row->source_channel,
                'competency_scores' => json_decode($row->competency_scores_json ?? '{}', true),
                'risk_flags' => json_decode($row->risk_flags_json ?? '[]', true),
                'raw_final_score' => $row->raw_final_score,
                'calibrated_score' => $row->calibrated_score,
                'z_score' => $row->z_score,
                'policy_decision' => $row->policy_decision,
                'policy_code' => $row->policy_code,
                'answers_meta' => json_decode($row->answers_meta_json ?? '{}', true),
                'created_at' => $row->feature_created_at,
            ],
            'label' => [
                'outcome_score' => $row->outcome_score,
                'hired' => (bool) $row->hired,
                'started' => (bool) $row->started,
                'still_employed_30d' => (bool) $row->still_employed_30d,
                'still_employed_90d' => (bool) $row->still_employed_90d,
                'incident_flag' => (bool) $row->incident_flag,
                'performance_rating' => $row->performance_rating,
            ],
        ];
    }
}
