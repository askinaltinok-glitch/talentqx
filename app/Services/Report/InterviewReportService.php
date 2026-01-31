<?php

namespace App\Services\Report;

use App\Models\InterviewReport;
use App\Models\InterviewSession;
use App\Models\InterviewSessionAnalysis;
use App\Models\JobContext;
use App\Models\ReportAuditLog;
use App\Services\Interview\ContextScoringService;
use App\Services\Report\NarrativeTemplateService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class InterviewReportService
{
    private string $nodeScript;
    private string $tempDir;
    private int $timeout;

    public function __construct()
    {
        $this->nodeScript = base_path('scripts/render-pdf.js');
        $this->tempDir = storage_path('app/temp/reports');
        $this->timeout = config('services.pdf.timeout', 60);

        // Ensure temp directory exists
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Generate PDF report for an interview session
     */
    public function generate(
        string $sessionId,
        ?string $tenantId = null,
        string $locale = 'tr',
        ?array $branding = null
    ): InterviewReport {
        // Get session with analysis
        $session = InterviewSession::with(['answers', 'analysis', 'consent'])->findOrFail($sessionId);

        if (!$session->analysis) {
            throw new \Exception('Interview analysis not found. Run analysis first.');
        }

        // Create report record
        $report = InterviewReport::create([
            'session_id' => $sessionId,
            'tenant_id' => $tenantId,
            'locale' => $locale,
            'status' => InterviewReport::STATUS_PENDING,
            'metadata' => [
                'branding' => $branding ?? $this->getDefaultBranding(),
                'session_role' => $session->role_key,
                'candidate_id' => $session->candidate_id,
            ],
            'expires_at' => now()->addDays(30),
        ]);

        try {
            $report->update(['status' => InterviewReport::STATUS_GENERATING]);

            // Generate HTML
            $html = $this->renderHtml($session, $report);

            // Save HTML to temp file
            $htmlPath = $this->tempDir . '/' . $report->id . '.html';
            file_put_contents($htmlPath, $html);

            // Generate PDF using Playwright
            $pdfPath = $this->tempDir . '/' . $report->id . '.pdf';
            $this->renderPdf($htmlPath, $pdfPath, $locale);

            // Move to private storage
            $storagePath = $this->storeReport($report, $pdfPath);

            // Update report
            $fullStoragePath = storage_path('app/private/' . $storagePath);
            $report->update([
                'status' => InterviewReport::STATUS_COMPLETED,
                'storage_path' => $storagePath,
                'file_size' => filesize($fullStoragePath),
                'checksum' => hash_file('sha256', $fullStoragePath),
                'generated_at' => now(),
            ]);

            // Cleanup temp files
            @unlink($htmlPath);
            @unlink($pdfPath);

            // Audit log
            ReportAuditLog::log($report->id, ReportAuditLog::ACTION_GENERATED);

            return $report->fresh();

        } catch (\Exception $e) {
            $report->update([
                'status' => InterviewReport::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('PDF generation failed', [
                'report_id' => $report->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Render HTML template
     */
    private function renderHtml(InterviewSession $session, InterviewReport $report): string
    {
        $analysis = $session->analysis;
        $branding = $report->getBranding();

        // Prepare radar chart data
        $radarData = $this->prepareRadarData($analysis);

        // Get questions and answers
        $questionsWithAnswers = $this->getQuestionsWithAnswers($session);

        // Get context comparison if role has contexts
        $contextComparison = $this->getContextComparison($session, $analysis, $report->locale);

        // Get current context info
        $currentContext = $session->context_key
            ? JobContext::findByKeys($session->role_key, $session->context_key)
            : null;

        // Get narrative templates
        $narratives = $this->getNarratives($session, $analysis, $contextComparison, $report->locale);

        return View::make('reports.interview', [
            'session' => $session,
            'analysis' => $analysis,
            'branding' => $branding,
            'locale' => $report->locale,
            'radarData' => $radarData,
            'questionsWithAnswers' => $questionsWithAnswers,
            'contextComparison' => $contextComparison,
            'currentContext' => $currentContext,
            'narratives' => $narratives,
            'generatedAt' => now(),
            'reportId' => $report->id,
        ])->render();
    }

    /**
     * Get narrative texts based on score
     */
    private function getNarratives(
        InterviewSession $session,
        InterviewSessionAnalysis $analysis,
        ?array $contextComparison,
        string $locale
    ): array {
        $narrativeService = app(NarrativeTemplateService::class);

        // Build placeholders
        $placeholders = $this->buildNarrativePlaceholders($session, $analysis, $contextComparison, $locale);

        // Get narratives based on overall score
        return $narrativeService->getNarratives(
            (float) $analysis->overall_score,
            $locale,
            $placeholders
        );
    }

    /**
     * Build placeholders for narrative templates
     */
    private function buildNarrativePlaceholders(
        InterviewSession $session,
        InterviewSessionAnalysis $analysis,
        ?array $contextComparison,
        string $locale
    ): array {
        // Context label
        $contextLabel = ucwords(str_replace('_', ' ', $session->role_key));
        if ($session->context_key) {
            $context = JobContext::findByKeys($session->role_key, $session->context_key);
            if ($context) {
                $contextLabel = $locale === 'en' ? $context->label_en : $context->label_tr;
            }
        }

        // High/low context from comparison
        $highContextLabel = $contextLabel;
        $lowContextLabel = $contextLabel;
        if ($contextComparison && count($contextComparison) > 0) {
            $highContextLabel = $contextComparison[0]['context'] ?? $contextLabel;
            $lowContextLabel = $contextComparison[count($contextComparison) - 1]['context'] ?? $contextLabel;
        }

        // Top strengths and weaker dimensions
        $dimensions = $analysis->dimension_scores ?? [];
        $scores = [];
        foreach ($dimensions as $key => $dim) {
            $scores[$key] = $dim['score'] ?? 0;
        }
        arsort($scores);
        $topKeys = array_slice(array_keys($scores), 0, 2);
        $weakKeys = array_slice(array_keys($scores), -2);

        $dimensionLabels = $locale === 'en' ? [
            'communication' => 'communication',
            'integrity' => 'integrity',
            'problem_solving' => 'problem solving',
            'stress_tolerance' => 'stress management',
            'teamwork' => 'teamwork',
            'customer_focus' => 'customer focus',
            'adaptability' => 'adaptability',
        ] : [
            'communication' => 'iletisim',
            'integrity' => 'durustluk',
            'problem_solving' => 'problem cozme',
            'stress_tolerance' => 'stres yonetimi',
            'teamwork' => 'takim calismasi',
            'customer_focus' => 'musteri odaklilik',
            'adaptability' => 'uyum saglama',
        ];

        $topStrengths = implode(', ', array_map(fn($k) => $dimensionLabels[$k] ?? $k, $topKeys));
        $weakerDimensions = implode(', ', array_map(fn($k) => $dimensionLabels[$k] ?? $k, $weakKeys));

        return [
            'context_label' => $contextLabel,
            'high_context_label' => $highContextLabel,
            'low_context_label' => $lowContextLabel,
            'top_strengths' => $topStrengths,
            'weaker_dimensions' => $weakerDimensions,
        ];
    }

    /**
     * Get context comparison data for PDF
     */
    private function getContextComparison(
        InterviewSession $session,
        InterviewSessionAnalysis $analysis,
        string $locale
    ): ?array {
        // Check if role has contexts defined
        $contexts = JobContext::getForRole($session->role_key);
        if ($contexts->isEmpty()) {
            return null;
        }

        $scoringService = app(ContextScoringService::class);
        return $scoringService->getContextComparisonForPdf($analysis, $session->role_key, $locale);
    }

    /**
     * Render PDF using Playwright Node script
     */
    private function renderPdf(string $htmlPath, string $pdfPath, string $locale): void
    {
        $process = new Process([
            'node',
            $this->nodeScript,
            '--input', $htmlPath,
            '--output', $pdfPath,
            '--locale', $locale,
            '--format', 'A4',
        ]);

        $process->setTimeout($this->timeout);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            throw new \Exception('PDF rendering failed: ' . $e->getMessage());
        }

        if (!file_exists($pdfPath)) {
            throw new \Exception('PDF file was not created');
        }
    }

    /**
     * Store report in private storage
     */
    private function storeReport(InterviewReport $report, string $pdfPath): string
    {
        $filename = sprintf(
            'reports/%s/%s/%s.pdf',
            date('Y/m'),
            $report->tenant_id ?? 'default',
            $report->id
        );

        // Use direct file copy to avoid finfo dependency
        $fullPath = storage_path('app/private/' . $filename);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        copy($pdfPath, $fullPath);

        return $filename;
    }

    /**
     * Prepare radar chart data
     */
    private function prepareRadarData(InterviewSessionAnalysis $analysis): array
    {
        $dimensions = $analysis->dimension_scores ?? [];

        // Map to 5 key dimensions for radar chart
        $radarDimensions = [
            'clarity' => $dimensions['communication']['score'] ?? 50,
            'ownership' => $dimensions['integrity']['score'] ?? 50,
            'problem_approach' => $dimensions['problem_solving']['score'] ?? 50,
            'stress_handling' => $dimensions['stress_tolerance']['score'] ?? 50,
            'consistency' => $analysis->behavior_analysis['consistency_score'] ?? 50,
        ];

        return [
            'labels' => [
                'tr' => ['Netlik', 'Sahiplenme', 'Problem Yaklaşımı', 'Stres Yönetimi', 'Tutarlılık'],
                'en' => ['Clarity', 'Ownership', 'Problem Approach', 'Stress Handling', 'Consistency'],
            ],
            'values' => array_values($radarDimensions),
            'max' => 100,
        ];
    }

    /**
     * Get questions with answers for the report
     */
    private function getQuestionsWithAnswers(InterviewSession $session): array
    {
        $result = [];
        $answers = $session->answers->keyBy('question_id');

        foreach ($session->analysis->question_analyses ?? [] as $qa) {
            $answer = $answers->get($qa['question_id']);
            $result[] = [
                'question_id' => $qa['question_id'],
                'score' => $qa['score'] ?? 0,
                'max_score' => $qa['max_score'] ?? 5,
                'analysis' => $qa['analysis'] ?? '',
                'positive_points' => $qa['positive_points'] ?? [],
                'concerns' => $qa['concerns'] ?? [],
                'answer_text' => $answer?->raw_text ?? '',
            ];
        }

        return $result;
    }

    /**
     * Get default branding
     *
     * BRANDING POLICY v1.0:
     * - Default PDF output is TalentQX-branded
     * - Customer logo is optional and only enabled if explicitly provided
     * - When customer logo is enabled:
     *   - Logo is placed in the header, small and unobtrusive
     *   - TalentQX logo remains visible in footer
     *   - Text: "Prepared for {{Company Name}}" is shown
     *
     * WHITE-LABEL POLICY v2.0 (Enterprise only):
     * - TalentQX logo removed from cover and footer
     * - Subtle "Powered by TalentQX" disclaimer remains in legal page
     * - Customer assumes responsibility for internal distribution
     * - Requires explicit contract clause acceptance
     */
    private function getDefaultBranding(): array
    {
        return [
            // TalentQX branding (visible unless white_label is true)
            'primary_color' => '#1E3A5F',      // Navy blue - corporate standard
            'secondary_color' => '#2E5A8F',

            // Customer branding (optional - only if explicitly provided)
            'customer_logo_url' => null,       // Customer logo for header
            'customer_company_name' => null,   // Customer company name

            // White-label mode (Enterprise only)
            'white_label' => false,            // Remove TalentQX branding (except legal disclaimer)

            // Legacy fields for backwards compatibility
            'logo_url' => null,
            'company_name' => null,
        ];
    }

    /**
     * Get report file stream
     */
    public function getFileStream(InterviewReport $report)
    {
        if (!$report->fileExists()) {
            throw new \Exception('Report file not found');
        }

        return Storage::disk($report->storage_disk)->readStream($report->storage_path);
    }

    /**
     * Delete report
     */
    public function delete(InterviewReport $report): bool
    {
        $report->deleteFile();

        ReportAuditLog::log($report->id, ReportAuditLog::ACTION_DELETED);

        return $report->delete();
    }
}
