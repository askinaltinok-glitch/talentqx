<?php
/**
 * PROD SMOKE TEST: Compute Competency for Alperen ONLY
 *
 * 1. Find Alperen candidate
 * 2. Show BEFORE executive summary
 * 3. Compute competency (single candidate)
 * 4. Show AFTER executive summary
 * 5. Verify no other candidates affected
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CompetencyAssessment;
use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Services\Competency\CompetencyEngine;
use App\Services\ExecutiveSummary\ExecutiveSummaryBuilder;

echo "=== PROD SMOKE TEST: Competency Engine v1 ===\n";
echo "Date: " . now()->toDateTimeString() . "\n\n";

// ── Step 1: Feature flag check ──
echo "── Step 1: Feature Flags ──\n";
echo "  competency_v1: " . (config('maritime.competency_v1') ? 'ON' : 'OFF') . "\n";
echo "  competency_auto_compute: " . (config('maritime.competency_auto_compute') ? 'ON' : 'OFF') . "\n\n";

if (!config('maritime.competency_v1')) {
    echo "ABORT: competency_v1 is OFF\n";
    exit(1);
}
if (config('maritime.competency_auto_compute')) {
    echo "WARNING: auto_compute is ON — this should be OFF for smoke test!\n";
}

// ── Step 2: Find Alperen ──
echo "── Step 2: Find Alperen ──\n";
$candidate = PoolCandidate::where('first_name', 'like', '%Alperen%')
    ->orWhere('last_name', 'like', '%Alperen%')
    ->first();

if (!$candidate) {
    echo "ABORT: Alperen not found\n";
    exit(1);
}

echo "  ID: {$candidate->id}\n";
echo "  Name: {$candidate->first_name} {$candidate->last_name}\n";
echo "  Created: {$candidate->created_at}\n";

// Check interview
$interview = \App\Models\FormInterview::where('pool_candidate_id', $candidate->id)
    ->where('status', 'completed')
    ->whereNotNull('completed_at')
    ->latest('completed_at')
    ->first();

if (!$interview) {
    echo "ABORT: No completed interview for Alperen\n";
    exit(1);
}

$answers = $interview->answers()->get();
echo "  Interview ID: {$interview->id}\n";
echo "  Position Code: {$interview->position_code}\n";
echo "  Answers: {$answers->count()}\n";
echo "  Avg Answer Length: " . round($answers->avg(fn($a) => mb_strlen($a->answer_text ?? ''))) . " chars\n";
echo "  Answer Competency Codes: " . $answers->pluck('competency')->filter()->unique()->implode(', ') . "\n\n";

// ── Step 3: Assessment count BEFORE ──
echo "── Step 3: Assessment Count BEFORE ──\n";
$assessmentCountBefore = CompetencyAssessment::count();
$alperenAssessmentsBefore = CompetencyAssessment::where('pool_candidate_id', $candidate->id)->count();
echo "  Total assessments in DB: {$assessmentCountBefore}\n";
echo "  Alperen assessments: {$alperenAssessmentsBefore}\n\n";

// ── Step 4: Executive Summary BEFORE ──
echo "── Step 4: Executive Summary BEFORE ──\n";
$builder = app(ExecutiveSummaryBuilder::class);
$candidate->load(['trustProfile', 'contracts.latestAisVerification']);
$execBefore = $builder->build($candidate);

if ($execBefore) {
    echo "  Decision: {$execBefore['decision']}\n";
    echo "  Confidence: {$execBefore['confidence_level']}\n";
    echo "  Action: {$execBefore['action_line']}\n";
    echo "  Competency Score (before): " . ($execBefore['scores']['competency']['competency_score'] ?? 'null') . "\n";
    echo "  Competency Status (before): " . ($execBefore['scores']['competency']['competency_status'] ?? 'null') . "\n";
    echo "  Strengths: " . json_encode($execBefore['top_strengths']) . "\n";
    echo "  Risks: " . json_encode($execBefore['top_risks']) . "\n";
} else {
    echo "  Exec summary: NULL (feature flag off?)\n";
}
echo "\n";

// ── Step 5: COMPUTE COMPETENCY ──
echo "── Step 5: COMPUTING Competency for Alperen ONLY ──\n";
$engine = app(CompetencyEngine::class);
$result = $engine->compute($candidate->id);

if ($result === null) {
    echo "  RESULT: null (engine returned null — check logs)\n";
    echo "  Check: php82 artisan log:tail or tail -50 storage/logs/laravel.log\n";
} else {
    echo "  score_total: {$result['score_total']}\n";
    echo "  status: {$result['status']}\n";
    echo "  questions_evaluated: {$result['questions_evaluated']}\n";
    echo "  interview_id: {$result['interview_id']}\n\n";

    echo "  Dimension Scores:\n";
    if (!empty($result['score_by_dimension'])) {
        arsort($result['score_by_dimension']);
        foreach ($result['score_by_dimension'] as $dim => $score) {
            echo "    {$dim}: {$score}/100\n";
        }
    }

    echo "\n  Flags:\n";
    if (empty($result['flags'])) {
        echo "    (none)\n";
    } else {
        foreach ($result['flags'] as $flag) {
            echo "    - {$flag}\n";
        }
    }

    echo "\n  Evidence Summary:\n";
    $ev = $result['evidence_summary'] ?? [];
    if (!empty($ev['strengths'])) {
        echo "    Strengths:\n";
        foreach ($ev['strengths'] as $s) {
            echo "      + {$s}\n";
        }
    }
    if (!empty($ev['concerns'])) {
        echo "    Concerns:\n";
        foreach ($ev['concerns'] as $c) {
            echo "      - {$c}\n";
        }
    }
    if (!empty($ev['why_lines'])) {
        echo "    Why Lines:\n";
        foreach ($ev['why_lines'] as $w) {
            echo "      [{$w['severity']}] {$w['flag']}: {$w['reason']}\n";
        }
    }
}
echo "\n";

// ── Step 6: Assessment count AFTER ──
echo "── Step 6: Assessment Count AFTER ──\n";
$assessmentCountAfter = CompetencyAssessment::count();
$alperenAssessmentsAfter = CompetencyAssessment::where('pool_candidate_id', $candidate->id)->count();
echo "  Total assessments in DB: {$assessmentCountAfter} (delta: +" . ($assessmentCountAfter - $assessmentCountBefore) . ")\n";
echo "  Alperen assessments: {$alperenAssessmentsAfter} (delta: +" . ($alperenAssessmentsAfter - $alperenAssessmentsBefore) . ")\n\n";

// ── Step 7: Executive Summary AFTER ──
echo "── Step 7: Executive Summary AFTER ──\n";
$candidate->unsetRelation('trustProfile');
$candidate->load(['trustProfile', 'contracts.latestAisVerification']);
$execAfter = $builder->build($candidate);

if ($execAfter) {
    echo "  Decision: {$execAfter['decision']}\n";
    echo "  Confidence: {$execAfter['confidence_level']}\n";
    echo "  Action: {$execAfter['action_line']}\n";
    echo "  Competency Score (after): " . ($execAfter['scores']['competency']['competency_score'] ?? 'null') . "\n";
    echo "  Competency Status (after): " . ($execAfter['scores']['competency']['competency_status'] ?? 'null') . "\n";
    echo "  Strengths: " . json_encode($execAfter['top_strengths']) . "\n";
    echo "  Risks: " . json_encode($execAfter['top_risks']) . "\n";
} else {
    echo "  Exec summary: NULL\n";
}
echo "\n";

// ── Step 8: Before vs After Comparison ──
echo "── Step 8: BEFORE vs AFTER Comparison ──\n";
$decBefore = $execBefore['decision'] ?? 'N/A';
$decAfter = $execAfter['decision'] ?? 'N/A';
$confBefore = $execBefore['confidence_level'] ?? 'N/A';
$confAfter = $execAfter['confidence_level'] ?? 'N/A';
$compScoreBefore = $execBefore['scores']['competency']['competency_score'] ?? 'null';
$compScoreAfter = $execAfter['scores']['competency']['competency_score'] ?? 'null';

echo "  Decision:    {$decBefore} → {$decAfter}" . ($decBefore !== $decAfter ? ' *** CHANGED ***' : ' (unchanged)') . "\n";
echo "  Confidence:  {$confBefore} → {$confAfter}" . ($confBefore !== $confAfter ? ' *** CHANGED ***' : ' (unchanged)') . "\n";
echo "  Competency:  {$compScoreBefore} → {$compScoreAfter}\n";
echo "\n";

// ── Step 9: Verify NO other candidates affected ──
echo "── Step 9: Isolation Verification ──\n";
$otherAssessments = CompetencyAssessment::where('pool_candidate_id', '!=', $candidate->id)
    ->where('computed_at', '>=', now()->subMinutes(10))
    ->count();
echo "  Other candidates with new assessments (last 10 min): {$otherAssessments}\n";
echo "  " . ($otherAssessments === 0 ? 'PASS: No other candidates affected' : 'FAIL: Other candidates were modified!') . "\n\n";

// ── Summary ──
echo "========================================\n";
echo "SMOKE TEST COMPLETE\n";
echo "  Candidate: {$candidate->first_name} {$candidate->last_name} ({$candidate->id})\n";
echo "  Competency Score: {$compScoreAfter}\n";
echo "  Decision: {$decBefore} → {$decAfter}\n";
echo "  Confidence: {$confBefore} → {$confAfter}\n";
echo "  Isolation: " . ($otherAssessments === 0 ? 'PASSED' : 'FAILED') . "\n";
echo "========================================\n";
