<?php
/**
 * FINAL RECOMPUTE: Alperen — Stopwords + Evidence Bullets + Additive Rubric
 */
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CompetencyAssessment;
use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Services\Competency\CompetencyEngine;
use App\Services\ExecutiveSummary\ExecutiveSummaryBuilder;

$candidateId = 'a1199b85-b794-4ef3-a9e0-f85e61224ef0'; // Alperen

echo "=== FINAL RECOMPUTE: Alperen — v1.1 Scorer ===\n";
echo "Date: " . now()->toDateTimeString() . "\n\n";

// ── BEFORE state ──
$tp = CandidateTrustProfile::where('pool_candidate_id', $candidateId)->first();
$assessmentsBefore = CompetencyAssessment::where('pool_candidate_id', $candidateId)->count();

echo "── BEFORE ──\n";
echo "  Assessments: {$assessmentsBefore}\n";
echo "  Competency Score: {$tp->competency_score}\n";
echo "  Competency Status: {$tp->competency_status}\n";

$candidate = PoolCandidate::with(['trustProfile', 'contracts.latestAisVerification'])->find($candidateId);
$builder = app(ExecutiveSummaryBuilder::class);
$execBefore = $builder->build($candidate);
echo "  Decision: {$execBefore['decision']}\n";
echo "  Confidence: {$execBefore['confidence_level']}\n\n";

// ── COMPUTE ──
echo "── COMPUTING (v1.1: additive rubric + stopwords + evidence bullets) ──\n";
$engine = app(CompetencyEngine::class);
$result = $engine->compute($candidateId);

if (!$result) {
    echo "  FAILED: engine returned null\n";
    exit(1);
}

echo "  score_total: {$result['score_total']}\n";
echo "  status: {$result['status']}\n";
echo "  questions_evaluated: {$result['questions_evaluated']}\n";
echo "  language: {$result['language']}\n";
echo "  language_confidence: {$result['language_confidence']}\n";
echo "  coverage: {$result['coverage']}\n\n";

echo "  Dimension Scores:\n";
arsort($result['score_by_dimension']);
foreach ($result['score_by_dimension'] as $dim => $score) {
    echo "    {$dim}: {$score}/100\n";
}

echo "\n  Flags:\n";
if (empty($result['flags'])) {
    echo "    (none)\n";
} else {
    foreach ($result['flags'] as $flag) echo "    - {$flag}\n";
}

echo "\n  Evidence by Dimension:\n";
foreach ($result['evidence_by_dimension'] ?? [] as $dim => $kws) {
    echo "    {$dim}: " . implode(', ', $kws) . "\n";
}

echo "\n  Evidence Bullets (top 3):\n";
foreach ($result['evidence_summary']['evidence_bullets'] ?? [] as $b) {
    echo "    * {$b}\n";
}

echo "\n  Strengths:\n";
foreach ($result['evidence_summary']['strengths'] ?? [] as $s) echo "    + {$s}\n";
echo "  Concerns:\n";
foreach ($result['evidence_summary']['concerns'] ?? [] as $c) echo "    - {$c}\n";
if (!empty($result['evidence_summary']['why_lines'])) {
    echo "  Why Lines:\n";
    foreach ($result['evidence_summary']['why_lines'] as $w) echo "    [{$w['severity']}] {$w['flag']}: {$w['reason']}\n";
}

// ── AFTER state ──
echo "\n── AFTER ──\n";
$candidate->unsetRelation('trustProfile');
$candidate->load(['trustProfile', 'contracts.latestAisVerification']);
$execAfter = $builder->build($candidate);
$tp->refresh();

echo "  Assessments: " . CompetencyAssessment::where('pool_candidate_id', $candidateId)->count() . "\n";
echo "  Competency Score: {$tp->competency_score}\n";
echo "  Competency Status: {$tp->competency_status}\n";
echo "  Decision: {$execAfter['decision']}\n";
echo "  Confidence: {$execAfter['confidence_level']}\n";
echo "  Strengths: " . json_encode($execAfter['top_strengths']) . "\n";
echo "  Risks: " . json_encode($execAfter['top_risks']) . "\n\n";

// ── COMPARISON ──
echo "── BEFORE → AFTER ──\n";
$scoreBefore = $execBefore['scores']['competency']['competency_score'] ?? 'null';
$scoreAfter = $execAfter['scores']['competency']['competency_score'] ?? 'null';
echo "  Score:      {$scoreBefore} → {$scoreAfter}\n";
echo "  Status:     " . ($execBefore['scores']['competency']['competency_status'] ?? 'null') . " → {$execAfter['scores']['competency']['competency_status']}\n";
echo "  Decision:   {$execBefore['decision']} → {$execAfter['decision']}\n";
echo "  Confidence: {$execBefore['confidence_level']} → {$execAfter['confidence_level']}\n";

// ── Isolation ──
echo "\n── Isolation ──\n";
$otherNew = CompetencyAssessment::where('pool_candidate_id', '!=', $candidateId)
    ->where('computed_at', '>=', now()->subMinutes(10))->count();
echo "  Other candidates modified: {$otherNew}\n";
echo "  " . ($otherNew === 0 ? 'PASS' : 'FAIL') . "\n";

echo "\n=== DONE ===\n";
