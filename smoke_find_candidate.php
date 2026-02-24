<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$interviews = App\Models\FormInterview::where('status', 'completed')
    ->whereNotNull('completed_at')
    ->whereNotNull('pool_candidate_id')
    ->whereHas('answers')
    ->with(['poolCandidate', 'answers'])
    ->latest('completed_at')
    ->take(10)
    ->get();

echo "=== Candidates with Completed Interviews ===\n\n";

foreach ($interviews as $i) {
    $c = $i->poolCandidate;
    if (!$c) continue;
    $answerCount = $i->answers->count();
    $avgLen = round($i->answers->avg(fn($a) => mb_strlen($a->answer_text ?? '')));
    $tp = \App\Models\CandidateTrustProfile::where('pool_candidate_id', $c->id)->first();
    $hasCompetency = $tp && isset($tp->detail_json['competency_engine']);

    echo "ID: {$c->id}\n";
    echo "  Name: {$c->first_name} {$c->last_name}\n";
    echo "  Position: {$i->position_code}\n";
    echo "  Interview: {$i->id}\n";
    echo "  Answers: {$answerCount} (avg length: {$avgLen} chars)\n";
    echo "  Trust Profile: " . ($tp ? 'YES' : 'NO') . "\n";
    echo "  Competency: " . ($hasCompetency ? 'ALREADY COMPUTED' : 'NOT YET') . "\n";
    echo "  Completed: {$i->completed_at}\n";

    // Show answer competency codes
    $competencies = $i->answers->pluck('competency')->filter()->unique()->values()->toArray();
    echo "  Answer Competency Codes: " . (empty($competencies) ? 'none' : implode(', ', $competencies)) . "\n";

    echo "\n";
}
