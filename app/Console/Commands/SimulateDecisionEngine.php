<?php

namespace App\Console\Commands;

use App\Services\DecisionEngine\DecisionEngineSimulator;
use Illuminate\Console\Command;

class SimulateDecisionEngine extends Command
{
    protected $signature = 'decision-engine:simulate';
    protected $description = 'Run decision engine simulation with test candidates';

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║       TALENTQX KARAR MOTORU SIMULASYONU                         ║');
        $this->info('║       Decision Engine Validation Test                            ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->info('');

        $simulator = new DecisionEngineSimulator();
        $results = $simulator->runTestSimulation();

        foreach ($results as $index => $result) {
            $this->renderCandidateResult($index + 1, $result);
        }

        $this->renderSummary($results);

        return Command::SUCCESS;
    }

    private function renderCandidateResult(int $num, array $result): void
    {
        $decisionColor = match ($result['decision']) {
            'HIRE' => 'green',
            'HOLD' => 'yellow',
            'REJECT' => 'red',
            default => 'white',
        };

        $this->info('');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("  ADAY #{$num}: {$result['candidate_name']}");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Competency Scores
        $this->info('');
        $this->info('  YETKINLIK SKORLARI (1-5 -> 0-100):');
        $this->info('  ─────────────────────────────────────');
        foreach ($result['competency_scores'] as $code => $data) {
            $bar = $this->renderScoreBar($data['percentage']);
            $this->line("    {$data['name']}: {$bar} {$data['percentage']}% (raw: {$data['raw']})");
        }

        // Primary Scores
        $this->info('');
        $this->info('  BIRINCIL SKORLAR:');
        $this->info('  ─────────────────────────────────────');
        foreach ($result['primary_scores'] as $code => $data) {
            $bar = $this->renderScoreBar($data['value']);
            $label = str_pad($data['label'], 10);
            $this->line("    {$code}: {$bar} {$data['value']}% [{$label}]");
        }

        // Risk Scores
        $this->info('');
        $this->info('  RISK SKORLARI:');
        $this->info('  ─────────────────────────────────────');
        foreach ($result['risk_scores'] as $code => $data) {
            $bar = $this->renderRiskBar($data['value']);
            $statusIcon = match ($data['status']) {
                'critical' => '[!!!]',
                'warning' => '[!!]',
                default => '[OK]',
            };
            $this->line("    {$code}: {$bar} {$data['value']}% {$statusIcon}");
        }

        // Red Flags
        $this->info('');
        $this->info('  KIRMIZI BAYRAKLAR:');
        $this->info('  ─────────────────────────────────────');
        if (empty($result['red_flags_triggered'])) {
            $this->line('    Tespit edilen red flag yok');
        } else {
            foreach ($result['red_flags_triggered'] as $flag) {
                $severityIcon = match ($flag['severity']) {
                    'critical' => '[KRITIK]',
                    'high' => '[YUKSEK]',
                    'medium' => '[ORTA]',
                    'low' => '[DUSUK]',
                    default => '',
                };
                $autoReject = $flag['causes_auto_reject'] ? ' -> AUTO-REJECT!' : '';
                $this->line("    {$flag['code']} {$severityIcon}: {$flag['name']}{$autoReject}");
                $this->line("      Tetikleyen: {$flag['triggered_by']}");
            }
        }

        // Overall Score and Decision
        $this->info('');
        $this->info('  ═══════════════════════════════════════');
        $overallBar = $this->renderScoreBar($result['overall_score']);
        $this->info("  GENEL SKOR: {$overallBar} {$result['overall_score']}%");
        $this->info('  ═══════════════════════════════════════');

        $this->info('');
        $this->line("  KARAR: <fg={$decisionColor};options=bold>{$result['decision']} - {$result['decision_label']}</>");
        $this->line("  Gerekce: {$result['decision_reason']}");
    }

    private function renderScoreBar(int $value): string
    {
        $filled = (int) round($value / 5);
        $empty = 20 - $filled;
        return '[' . str_repeat('█', $filled) . str_repeat('░', $empty) . ']';
    }

    private function renderRiskBar(int $value): string
    {
        $filled = (int) round($value / 5);
        $empty = 20 - $filled;
        // Risk bars show danger level (higher = worse)
        return '[' . str_repeat('▓', $filled) . str_repeat('░', $empty) . ']';
    }

    private function renderSummary(array $results): void
    {
        $this->info('');
        $this->info('');
        $this->line('╔══════════════════════════════════════════════════════════════════╗');
        $this->line('║                    SIMULASYON OZETI                              ║');
        $this->line('╠══════════════════════════════════════════════════════════════════╣');

        $hireCount = count(array_filter($results, fn($r) => $r['decision'] === 'HIRE'));
        $holdCount = count(array_filter($results, fn($r) => $r['decision'] === 'HOLD'));
        $rejectCount = count(array_filter($results, fn($r) => $r['decision'] === 'REJECT'));

        $this->line('║                                                                  ║');
        $this->line("║   ISE AL (HIRE):   {$hireCount} aday                                        ║");
        $this->line("║   BEKLET (HOLD):   {$holdCount} aday                                        ║");
        $this->line("║   REDDET (REJECT): {$rejectCount} aday                                        ║");
        $this->line('║                                                                  ║');
        $this->line('╠══════════════════════════════════════════════════════════════════╣');
        $this->line('║  ADAY TIPI                  │ SKOR │ KARAR  │ BEKLENEN          ║');
        $this->line('╠═════════════════════════════╪══════╪════════╪═══════════════════╣');

        $expected = [
            'strong_hire' => 'HIRE',
            'average_hire' => 'HOLD',
            'risky_skilled' => 'HOLD',
            'high_integrity_low_skill' => 'HOLD',
            'toxic_skilled' => 'REJECT',
        ];

        foreach ($results as $r) {
            $type = str_pad($r['candidate_type'], 25);
            $score = str_pad($r['overall_score'] . '%', 4, ' ', STR_PAD_LEFT);
            $decision = str_pad($r['decision'], 6);
            $exp = str_pad($expected[$r['candidate_type']] ?? '?', 17);
            $match = ($r['decision'] === ($expected[$r['candidate_type']] ?? '')) ? 'OK' : 'FARKLI';
            $this->line("║  {$type} │ {$score} │ {$decision} │ {$exp} ║");
        }

        $this->line('╚══════════════════════════════════════════════════════════════════╝');

        // Validation check
        $this->info('');
        $this->info('DOGRULAMA KONTROLLERI:');
        $this->info('─────────────────────────────────────────');

        $checks = [
            ['Guclu aday HIRE kararini aldi mi?', $results[0]['decision'] === 'HIRE'],
            ['Ortalama aday HOLD/HIRE arasi mi?', in_array($results[1]['decision'], ['HOLD', 'HIRE'])],
            ['Riskli-yetenekli aday HOLD kararini aldi mi?', $results[2]['decision'] === 'HOLD'],
            ['Dusuk beceri-yuksek durustluk HOLD mu?', $results[3]['decision'] === 'HOLD'],
            ['Toksik aday REJECT edildi mi?', $results[4]['decision'] === 'REJECT'],
            ['Agresif dil kritik red flag olarak tespit edildi mi?',
                !empty(array_filter($results[4]['red_flags_triggered'], fn($f) => $f['code'] === 'RF_AGGRESSION'))],
        ];

        $allPassed = true;
        foreach ($checks as [$question, $passed]) {
            $icon = $passed ? '[GECTI]' : '[BASARISIZ]';
            $color = $passed ? 'green' : 'red';
            $this->line("  <fg={$color}>{$icon}</> {$question}");
            if (!$passed) $allPassed = false;
        }

        $this->info('');
        if ($allPassed) {
            $this->info('═══════════════════════════════════════════════════════════════════');
            $this->info('  SONUC: Karar motoru dogru calisiyor!');
            $this->info('═══════════════════════════════════════════════════════════════════');
        } else {
            $this->error('═══════════════════════════════════════════════════════════════════');
            $this->error('  SONUC: Karar motorunda duzeltme gerekiyor!');
            $this->error('═══════════════════════════════════════════════════════════════════');
        }
    }
}
