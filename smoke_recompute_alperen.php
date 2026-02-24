<?php
/**
 * RECOMPUTE: Alperen with Turkish keyword support
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

echo "=== RECOMPUTE: Alperen — Turkish Keyword Support ===\n\n";

// ── BEFORE state ──
$candidate = PoolCandidate::find($candidateId);
$tp = CandidateTrustProfile::where('pool_candidate_id', $candidateId)->first();
$assessmentsBefore = CompetencyAssessment::where('pool_candidate_id', $candidateId)->count();

echo "── BEFORE ──\n";
echo "  Assessments: {$assessmentsBefore}\n";
echo "  Competency Score: {$tp->competency_score}\n";
echo "  Competency Status: {$tp->competency_status}\n";

$candidate->load(['trustProfile', 'contracts.latestAisVerification']);
$builder = app(ExecutiveSummaryBuilder::class);
$execBefore = $builder->build($candidate);
echo "  Decision: {$execBefore['decision']}\n";
echo "  Confidence: {$execBefore['confidence_level']}\n\n";

// ── COMPUTE ──
echo "── COMPUTING (Turkish keywords enabled) ──\n";
$engine = app(CompetencyEngine::class);
$result = $engine->compute($candidateId);

if (!$result) {
    echo "  FAILED: engine returned null\n";
    exit(1);
}

echo "  score_total: {$result['score_total']}\n";
echo "  status: {$result['status']}\n";
echo "  questions_evaluated: {$result['questions_evaluated']}\n\n";

echo "  Dimension Scores:\n";
arsort($result['score_by_dimension']);
foreach ($result['score_by_dimension'] as $dim => $score) {
    echo "    {$dim}: {$score}/100\n";
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
    foreach ($ev['strengths'] as $s) echo "      + {$s}\n";
}
if (!empty($ev['concerns'])) {
    echo "    Concerns:\n";
    foreach ($ev['concerns'] as $c) echo "      - {$c}\n";
}
if (!empty($ev['why_lines'])) {
    echo "    Why Lines:\n";
    foreach ($ev['why_lines'] as $w) echo "      [{$w['severity']}] {$w['flag']}: {$w['reason']}\n";
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
echo "  Score:      {$execBefore['scores']['competency']['competency_score']} → {$execAfter['scores']['competency']['competency_score']}\n";
echo "  Status:     " . ($execBefore['scores']['competency']['competency_status'] ?? 'null') . " → {$execAfter['scores']['competency']['competency_status']}\n";
echo "  Decision:   {$execBefore['decision']} → {$execAfter['decision']}\n";
echo "  Confidence: {$execBefore['confidence_level']} → {$execAfter['confidence_level']}\n";

// ── Per-answer debug: show keyword matches ──
echo "\n── Keyword Match Debug (per answer) ──\n";
$interview = \App\Models\FormInterview::find($result['interview_id']);
$answers = $interview->answers()->get();

$legacyMap = [
    'COMMUNICATION' => 'COMMS', 'ACCOUNTABILITY' => 'DISCIPLINE',
    'TEAMWORK' => 'TEAMWORK', 'STRESS_RESILIENCE' => 'STRESS',
    'ADAPTABILITY' => 'TECH_PRACTICAL', 'LEARNING_AGILITY' => 'LEADERSHIP',
    'INTEGRITY' => 'DISCIPLINE', 'ROLE_COMPETENCE' => 'TECH_PRACTICAL',
];

$structureKw = [
    'önce', 'sonra', 'ardından', 'adım', 'prosedür', 'süreç',
    'kontrol', 'sağla', 'doğrula', 'takip', 'izle',
    'uygun', 'çünkü', 'neden', 'önemli', 'örnek', 'durum',
    'karar', 'çözüm', 'yöntem', 'şekilde', 'olarak',
];

$profKw = [
    'güvenlik', 'emniyet', 'risk', 'tatbikat', 'acil durum', 'denetim',
    'mürettebat', 'kurtarma', 'köprüüstü', 'makine dairesi', 'kargo',
    'balast', 'palamar', 'vardiya', 'devir teslim', 'seyir', 'manevra',
    'rapor', 'kayıt', 'belge', 'geri bildirim', 'bildirim', 'iletişim',
    'lider', 'koordin', 'eğitim', 'yetkinlik', 'değerlendirme', 'yönetim',
    'personel', 'ekip', 'arıza', 'bakım', 'muayene', 'yedek parça',
    'kalibrasyon', 'onarım', 'teçhizat', 'donanım',
];

$expertKw = [
    'kök neden', 'düzeltici', 'önleyici', 'alınan dersler',
    'tetkik', 'uygunluk', 'mevzuat', 'yönetmelik',
    'risk matris', 'yorgunluk yönetim', 'kültürel', 'refah',
    'psikoloj', 'dayanıklılık', 'farkındalık',
    'sürekli iyileştirme', 'en iyi uygulama', 'etik',
];

foreach ($answers as $a) {
    $comp = strtoupper(trim($a->competency ?? ''));
    $dim = $legacyMap[$comp] ?? $comp;
    $text = mb_strtolower(trim($a->answer_text ?? ''));
    $wc = count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));

    $sMatches = [];
    foreach ($structureKw as $kw) {
        if (str_contains($text, $kw)) $sMatches[] = $kw;
    }
    $pMatches = [];
    foreach ($profKw as $kw) {
        if (str_contains($text, $kw)) $pMatches[] = $kw;
    }
    $eMatches = [];
    foreach ($expertKw as $kw) {
        if (str_contains($text, $kw)) $eMatches[] = $kw;
    }

    echo "  [{$a->competency} → {$dim}] words={$wc}\n";
    echo "    Structure(" . count($sMatches) . "): " . implode(', ', $sMatches) . "\n";
    echo "    Professional(" . count($pMatches) . "): " . implode(', ', $pMatches) . "\n";
    echo "    Expert(" . count($eMatches) . "): " . implode(', ', $eMatches) . "\n";
}

// ── Isolation ──
echo "\n── Isolation ──\n";
$otherNew = CompetencyAssessment::where('pool_candidate_id', '!=', $candidateId)
    ->where('computed_at', '>=', now()->subMinutes(10))->count();
echo "  Other candidates modified: {$otherNew}\n";
echo "  " . ($otherNew === 0 ? 'PASS' : 'FAIL') . "\n";

echo "\n=== DONE ===\n";
