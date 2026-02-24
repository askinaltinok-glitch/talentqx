<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$candidate = App\Models\PoolCandidate::where('first_name', 'like', '%Alperen%')->first();
$interview = App\Models\FormInterview::where('pool_candidate_id', $candidate->id)->with('answers')->first();

$pdf = Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.candidate-report', [
    'candidate' => $candidate,
    'interview' => $interview,
    'answers' => $interview->answers,
    'generatedAt' => now()->format('d.m.Y H:i'),
    'generatedBy' => 'askinaltinok@gmail.com',
]);

$pdf->setPaper('A4', 'portrait');
$pdf->setOption('isHtml5ParserEnabled', true);

$path = '/www/wwwroot/talentqx.com/reports/alperen-yagci-assessment-report.pdf';
file_put_contents($path, $pdf->output());
echo "OK - " . number_format(filesize($path)) . " bytes\n";
