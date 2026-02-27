<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Mülakat Değerlendirme Raporu</title>
    <style>
        /* ── TalentQX Interview Report PDF v3 — Professional ── */
        @page {
            size: A4;
            margin: 18mm 16mm 20mm 16mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
            line-height: 1.55;
            color: #334155;
            background: #ffffff;
        }

        /* ── Page Container ── */
        .page {
            position: relative;
            padding-bottom: 30px;
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: auto;
        }

        /* ── Fixed Footer ── */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #cbd5e1;
            padding: 5px 0;
            font-size: 6.5pt;
            color: #94a3b8;
            text-align: center;
        }

        /* ── Cover: Accent Bar + Company ── */
        .cover-accent {
            height: 6px;
            background-color: #0f172a;
            margin-bottom: 28px;
        }
        .cover-top-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .cover-top-table td {
            vertical-align: top;
        }
        .cover-brand-text {
            font-size: 8pt;
            letter-spacing: 2.5px;

            color: #64748b;
            margin-bottom: 6px;
        }
        .cover-company-name {
            font-size: 20pt;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.2;
            margin-bottom: 4px;
        }
        .cover-report-type {
            font-size: 11pt;
            color: #475569;
            letter-spacing: 0.5px;
        }

        /* ── Horizontal Rule ── */
        .hr-line {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 16px 0;
        }
        .hr-thick {
            border: none;
            border-top: 2px solid #0f172a;
            margin: 16px 0;
        }

        /* ── Cover Info Grid ── */
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-grid td {
            padding: 6px 0;
            font-size: 9pt;
            vertical-align: top;
        }
        .info-grid .lbl {
            color: #64748b;
            width: 38%;
            font-size: 8pt;

            letter-spacing: 0.5px;
            padding-right: 12px;
        }
        .info-grid .val {
            font-weight: bold;
            color: #1e293b;
        }
        .info-grid tr {
            border-bottom: 1px solid #f1f5f9;
        }

        /* ── Executive Summary Box ── */
        .exec-summary {
            border: 1px solid #e2e8f0;
            padding: 16px 20px;
            margin-bottom: 20px;
            background-color: #f8fafc;
        }
        .exec-summary-title {
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;

            letter-spacing: 1px;
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .exec-kpi-table {
            width: 100%;
            border-collapse: collapse;
        }
        .exec-kpi-table td {
            text-align: center;
            vertical-align: top;
            padding: 4px 8px;
        }
        .kpi-label {
            font-size: 7pt;

            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .kpi-score-circle {
            display: inline-block;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            font-size: 22pt;
            font-weight: bold;
            color: #ffffff;
            text-align: center;
            padding-top: 14px;
        }
        .kpi-decision {
            display: inline-block;
            padding: 10px 22px;
            font-size: 11pt;
            font-weight: bold;
            color: #ffffff;

            letter-spacing: 1.5px;
        }
        .kpi-confidence {
            font-size: 22pt;
            font-weight: bold;
            color: #0f172a;
        }
        .kpi-status {
            display: inline-block;
            padding: 5px 16px;
            font-size: 8.5pt;
            font-weight: bold;
            background-color: #e2e8f0;
            color: #334155;

        }

        /* ── Reasons ── */
        .reasons-section {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
        }
        .reasons-heading {
            font-size: 8pt;
            font-weight: bold;
            color: #0f172a;

            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .reason-row {
            padding: 3px 0;
            font-size: 8pt;
            color: #475569;
        }
        .reason-num {
            display: inline-block;
            width: 16px;
            height: 16px;
            line-height: 16px;
            text-align: center;
            border-radius: 50%;
            background-color: #0f172a;
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            margin-right: 6px;
        }

        /* ── Page Header (pages 2+) ── */
        .pg-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        .pg-header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .pg-header-table td {
            vertical-align: bottom;
        }
        .pg-header-company {
            font-size: 10pt;
            font-weight: bold;
            color: #0f172a;
        }
        .pg-header-meta {
            text-align: right;
            font-size: 7.5pt;
            color: #64748b;
        }

        /* ── Section Header ── */
        .sec-header {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            padding-bottom: 6px;
            border-bottom: 2px solid #0f172a;
            margin-bottom: 14px;
            margin-top: 20px;
        }
        .sec-header:first-child,
        .pg-header + .sec-header {
            margin-top: 0;
        }

        /* ── Sub-section ── */
        .sub-header {
            font-size: 9pt;
            font-weight: bold;
            color: #334155;
            margin-bottom: 8px;
            margin-top: 14px;
        }

        /* ── Competency Bar Table ── */
        .bar-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .bar-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .bar-label {
            font-weight: bold;
            font-size: 8pt;
            color: #334155;
            width: 120px;
        }
        .bar-track {
            background-color: #e2e8f0;
            height: 12px;
            overflow: hidden;
            position: relative;
        }
        .bar-fill {
            height: 12px;
            display: block;
        }
        .bar-num {
            font-weight: bold;
            font-size: 9pt;
            width: 40px;
            text-align: right;
        }

        /* ── Competency Detail Table ── */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 7.5pt;
        }
        .detail-table th {
            background-color: #0f172a;
            color: #ffffff;
            padding: 5px 8px;
            text-align: left;
            font-size: 7.5pt;
            font-weight: bold;
        }
        .detail-table th:nth-child(2) {
            text-align: center;
            width: 42px;
        }
        .detail-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            color: #475569;
        }
        .detail-table td:nth-child(2) {
            text-align: center;
            font-weight: bold;
            font-size: 8.5pt;
        }
        .detail-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* ── Radar Chart ── */
        .radar-wrap {
            text-align: center;
            margin-bottom: 16px;
        }

        /* ── Behavior / Culture Metric Cards ── */
        .metric-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .metric-table td {
            padding: 3px;
            vertical-align: top;
        }
        .metric-card {
            border: 1px solid #e2e8f0;
            padding: 8px 4px;
            text-align: center;
            background-color: #ffffff;
        }
        .metric-card-label {
            font-size: 7pt;
            color: #64748b;
            margin-bottom: 4px;

            letter-spacing: 0.3px;
        }
        .metric-card-value {
            font-size: 16pt;
            font-weight: bold;
        }
        .metric-card-text {
            font-size: 8.5pt;
            font-weight: bold;

        }

        /* ── Question Analysis ── */
        .qa-block {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            margin-bottom: 8px;
            page-break-inside: avoid;
            background-color: #ffffff;
        }
        .qa-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .qa-header-table td {
            vertical-align: top;
        }
        .qa-meta {
            font-size: 7.5pt;
            color: #64748b;
        }
        .qa-score-tag {
            display: inline-block;
            padding: 2px 10px;
            font-size: 9pt;
            font-weight: bold;
        }
        .qa-analysis {
            font-size: 7.5pt;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 4px;
        }
        .qa-points-table {
            width: 100%;
            border-collapse: collapse;
        }
        .qa-points-table td {
            vertical-align: top;
            padding: 2px 4px;
            width: 50%;
        }
        .qa-point-good {
            font-size: 7pt;
            color: #16a34a;
        }
        .qa-point-bad {
            font-size: 7pt;
            color: #dc2626;
        }
        .qa-points-heading {
            font-size: 6.5pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        /* ── Risk Flags ── */
        .risk-block {
            padding: 6px 10px;
            margin-bottom: 6px;
            border-left: 3px solid;
            page-break-inside: avoid;
        }
        .risk-block-high { background-color: #fef2f2; border-left-color: #dc2626; }
        .risk-block-medium { background-color: #fffbeb; border-left-color: #d97706; }
        .risk-block-low { background-color: #f0fdf4; border-left-color: #16a34a; }
        .risk-severity-tag {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: bold;

            padding: 1px 6px;
            color: #ffffff;
            margin-right: 4px;
        }
        .risk-code-text {
            font-size: 8pt;
            font-weight: bold;
            color: #334155;
        }
        .risk-phrase-text {
            font-size: 7.5pt;
            font-style: italic;
            color: #64748b;
            margin-top: 2px;
        }
        .no-risk-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 10px 14px;
            margin-bottom: 14px;
        }

        /* ── Decision Details ── */
        .decision-box {
            border: 1px solid #e2e8f0;
            padding: 12px 14px;
            background-color: #f8fafc;
            margin-bottom: 14px;
        }
        .decision-box-heading {
            font-size: 8pt;
            font-weight: bold;
            color: #0f172a;

            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .follow-up-row {
            padding: 2px 0;
            font-size: 8pt;
            color: #475569;
        }

        /* ── Disclaimer ── */
        .disclaimer-block {
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            margin-top: 20px;
            font-size: 7pt;
            color: #94a3b8;
            line-height: 1.6;
        }
        .disclaimer-block strong {
            color: #64748b;
        }
    </style>
</head>
<body>

@php
    // ── Labels ──
    $competencyLabels = [
        'motivation'          => 'Motivasyon',
        'experience'          => 'Deneyim',
        'problem_solving'     => 'Problem Çözme',
        'teamwork'            => 'Takım Çalışması',
        'career_goals'        => 'Kariyer Hedefleri',
        'customer_relations'  => 'Müşteri İlişkileri',
        'stress_management'   => 'Stres Yönetimi',
        'adaptability'        => 'Uyum Yeteneği',
        'communication'       => 'İletişim',
        'leadership'          => 'Liderlik',
        'technical_knowledge' => 'Teknik Bilgi',
        'safety_awareness'    => 'Güvenlik Bilinci',
        'discipline'          => 'Disiplin',
        'initiative'          => 'İnisiyatif',
    ];

    $behaviorLabels = [
        'clarity_score'       => 'Netlik Puanı',
        'consistency_score'   => 'Tutarlılık Puanı',
        'stress_tolerance'    => 'Stres Toleransı',
        'communication_style' => 'İletişim Tarzı',
        'confidence_level'    => 'Güven Düzeyi',
    ];

    $cultureFitLabels = [
        'discipline_fit'                 => 'Disiplin Uyumu',
        'hygiene_quality_fit'            => 'Hijyen/Kalite Uyumu',
        'schedule_tempo_fit'             => 'Program/Tempo Uyumu',
        'cultural_communication_style'   => 'Kültürel İletişim',
        'cross_cultural_adaptability'    => 'Kültürlerarası Uyum',
        'overall_fit'                    => 'Genel Uyum',
        'notes'                          => 'Notlar',
    ];

    $decisionLabels = [
        'hire'   => 'İŞE AL',
        'hold'   => 'BEKLET',
        'review' => 'İNCELE',
        'reject' => 'REDDET',
    ];

    $overallScore = $analysis?->overall_score ?? 0;
    $recommendation = $decisionSnapshot['recommendation'] ?? null;
    $confidencePercent = $decisionSnapshot['confidence_percent'] ?? null;
    $reasons = $decisionSnapshot['reasons'] ?? [];

    $getScoreColor = function($score) {
        if ($score >= 70) return '#16a34a';
        if ($score >= 50) return '#d97706';
        return '#dc2626';
    };
    $getScoreBg = function($score) {
        if ($score >= 70) return '#f0fdf4';
        if ($score >= 50) return '#fffbeb';
        return '#fef2f2';
    };

    $scoreColorHex = $getScoreColor($overallScore);

    $decisionColorHex = '#64748b';
    if ($recommendation) {
        $recLower = strtolower($recommendation);
        if ($recLower === 'hire') $decisionColorHex = '#16a34a';
        elseif (in_array($recLower, ['hold', 'review'])) $decisionColorHex = '#d97706';
        elseif ($recLower === 'reject') $decisionColorHex = '#dc2626';
    }
    $decisionLabel = $recommendation ? ($decisionLabels[strtolower($recommendation)] ?? strtoupper($recommendation)) : '-';

    // Turkish-aware uppercase (CSS text-transform: uppercase breaks İ/ı/ş/ğ)
    $trUpper = function($text) {
        $map = ['i' => 'İ', 'ı' => 'I', 'ş' => 'Ş', 'ğ' => 'Ğ', 'ü' => 'Ü', 'ö' => 'Ö', 'ç' => 'Ç'];
        $text = strtr($text, $map);
        return mb_strtoupper($text, 'UTF-8');
    };

    $candidateFullName = trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? '')) ?: 'Aday';
    $companyName = $company->name ?? 'TalentQX';
    $jobTitle = $job->title ?? '-';
    $completedAt = $interview->completed_at ? $interview->completed_at->format('d.m.Y H:i') : '-';
    $interviewStatus = $interview->status ?? '-';

    // ── Radar Chart Generator (PHP GD with TTF for Turkish) ──
    $generateRadarChart = function($scores, $labels) {
        if (empty($scores) || count($scores) < 2) return null;

        $width = 540;
        $height = 540;
        $centerX = $width / 2;
        $centerY = $height / 2;
        $maxRadius = 155;
        $labelOffset = 45;

        $img = imagecreatetruecolor($width, $height);
        imagesavealpha($img, true);

        $bgColor = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $bgColor);

        $gridColor     = imagecolorallocate($img, 203, 213, 225);
        $gridLightColor= imagecolorallocate($img, 226, 232, 240);
        $axisColor     = imagecolorallocate($img, 148, 163, 184);
        $fillColor     = imagecolorallocatealpha($img, 22, 163, 74, 85);
        $borderColor   = imagecolorallocate($img, 22, 163, 74);
        $dotColor      = imagecolorallocate($img, 22, 163, 74);
        $labelColor    = imagecolorallocate($img, 15, 23, 42);
        $scoreNumColor = imagecolorallocate($img, 22, 163, 74);

        $keys = array_keys($scores);
        $n = count($keys);
        $angleStep = 2 * M_PI / $n;
        $startAngle = -M_PI / 2;

        // Grid rings: 25%, 50%, 75%, 100%
        foreach ([0.25, 0.50, 0.75, 1.0] as $ring) {
            $r = $maxRadius * $ring;
            $points = [];
            for ($i = 0; $i < $n; $i++) {
                $angle = $startAngle + $i * $angleStep;
                $points[] = $centerX + $r * cos($angle);
                $points[] = $centerY + $r * sin($angle);
            }
            for ($i = 0; $i < $n; $i++) {
                $x1 = $points[$i * 2]; $y1 = $points[$i * 2 + 1];
                $x2 = $points[(($i + 1) % $n) * 2]; $y2 = $points[(($i + 1) % $n) * 2 + 1];
                $lineColor = ($ring === 1.0) ? $gridColor : $gridLightColor;
                imageline($img, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $lineColor);
            }
            // Draw ring percentage label on first axis
            if ($ring < 1.0) {
                $pctLabel = (int)($ring * 100);
                $pctY = $centerY - $r;
                $pctFont = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
                $pctColor = imagecolorallocate($img, 148, 163, 184);
                imagettftext($img, 7, 0, (int)($centerX + 4), (int)($pctY + 3), $pctColor, $pctFont, (string)$pctLabel);
            }
        }

        // Axis lines
        for ($i = 0; $i < $n; $i++) {
            $angle = $startAngle + $i * $angleStep;
            $x = $centerX + $maxRadius * cos($angle);
            $y = $centerY + $maxRadius * sin($angle);
            imageline($img, (int)$centerX, (int)$centerY, (int)$x, (int)$y, $axisColor);
        }

        // Score polygon (filled)
        $scorePoints = [];
        $scoreCoords = [];
        for ($i = 0; $i < $n; $i++) {
            $key = $keys[$i];
            $score = max(0, min(100, $scores[$key]));
            $r = $maxRadius * ($score / 100);
            $angle = $startAngle + $i * $angleStep;
            $x = $centerX + $r * cos($angle);
            $y = $centerY + $r * sin($angle);
            $scorePoints[] = (int)$x;
            $scorePoints[] = (int)$y;
            $scoreCoords[] = ['x' => $x, 'y' => $y, 'score' => $score];
        }
        if (count($scorePoints) >= 6) {
            imagefilledpolygon($img, $scorePoints, $fillColor);
            imagesetthickness($img, 2);
            imagepolygon($img, $scorePoints, $borderColor);
            imagesetthickness($img, 1);
        }

        // Vertex dots
        foreach ($scoreCoords as $coord) {
            imagefilledellipse($img, (int)$coord['x'], (int)$coord['y'], 10, 10, $dotColor);
            imagefilledellipse($img, (int)$coord['x'], (int)$coord['y'], 5, 5, $bgColor);
        }

        // Labels + scores
        $ttfFont = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        $ttfBold = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        $fontSize = 8;
        $scoreSize = 9;

        for ($i = 0; $i < $n; $i++) {
            $key = $keys[$i];
            $label = $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
            $score = round($scores[$key]);
            $angle = $startAngle + $i * $angleStep;

            $lx = $centerX + ($maxRadius + $labelOffset) * cos($angle);
            $ly = $centerY + ($maxRadius + $labelOffset) * sin($angle);

            $bbox = imagettfbbox($fontSize, 0, $ttfFont, $label);
            $textWidth = abs($bbox[2] - $bbox[0]);
            $textHeight = abs($bbox[7] - $bbox[1]);

            $scoreText = (string)$score;
            $sBbox = imagettfbbox($scoreSize, 0, $ttfBold, $scoreText);
            $scoreWidth = abs($sBbox[2] - $sBbox[0]);

            $cosA = cos($angle);
            $sinA = sin($angle);

            // Position label based on angle quadrant
            if (abs($cosA) < 0.15) {
                // Top or bottom — center horizontally
                $tx = $lx - $textWidth / 2;
            } elseif ($cosA > 0) {
                // Right side — left-align from point
                $tx = $lx - 2;
            } else {
                // Left side — right-align to point
                $tx = $lx - $textWidth + 2;
            }

            if (abs($sinA) < 0.15) {
                // Left or right — center vertically
                $ty = $ly + $textHeight / 2;
            } elseif ($sinA < 0) {
                // Top — text above point
                $ty = $ly;
            } else {
                // Bottom — text below point
                $ty = $ly + $textHeight + 6;
            }

            // Clamp to image bounds
            $tx = max(4, min($width - $textWidth - 4, $tx));
            $ty = max($textHeight + 2, min($height - 4, $ty));

            imagettftext($img, $fontSize, 0, (int)$tx, (int)$ty, $labelColor, $ttfFont, $label);

            // Score below label
            $sx = $tx + ($textWidth - $scoreWidth) / 2;
            $sy = $ty + $textHeight + 3;
            $sy = min($height - 4, $sy);
            imagettftext($img, $scoreSize, 0, (int)$sx, (int)$sy, $scoreNumColor, $ttfBold, $scoreText);
        }

        // Center dot
        imagefilledellipse($img, (int)$centerX, (int)$centerY, 5, 5, $axisColor);

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($data);
    };

    // Build radar data
    $radarScores = [];
    if (!empty($competencyScores)) {
        foreach ($competencyScores as $code => $val) {
            $score = is_array($val) ? ($val['score'] ?? 0) : (is_numeric($val) ? $val : 0);
            if (is_numeric($score)) $radarScores[$code] = (float)$score;
        }
    }
    $radarChartDataUri = $generateRadarChart($radarScores, $competencyLabels);
@endphp

{{-- ════════════════════════════════════════════════════════
     PAGE 1: COVER + EXECUTIVE SUMMARY
     ════════════════════════════════════════════════════════ --}}
<div class="page">
    <div class="cover-accent"></div>

    <table class="cover-top-table">
        <tr>
            <td>
                <div class="cover-brand-text">OCTOPUS AI</div>
                <div class="cover-company-name">{{ $companyName }}</div>
                <div class="cover-report-type">Mülakat Değerlendirme Raporu</div>
            </td>
            <td style="text-align: right; width: 120px; padding-top: 10px;">
                <div style="font-size: 7pt; color: #94a3b8; letter-spacing: 0.5px;">RAPOR NO</div>
                <div style="font-size: 8pt; font-weight: bold; color: #334155;">{{ strtoupper(substr($interview->id, 0, 8)) }}</div>
            </td>
        </tr>
    </table>

    <div class="hr-thick"></div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 16px;">
                <table class="info-grid">
                    <tr>
                        <td class="lbl">{{ $trUpper('Aday') }}</td>
                        <td class="val">{{ $candidateFullName }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">{{ $trUpper('Şirket') }}</td>
                        <td class="val">{{ $companyName }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">{{ $trUpper('Tamamlanma') }}</td>
                        <td class="val">{{ $completedAt }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 16px;">
                <table class="info-grid">
                    <tr>
                        <td class="lbl">{{ $trUpper('Pozisyon') }}</td>
                        <td class="val">{{ $jobTitle }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">{{ $trUpper('Durum') }}</td>
                        <td class="val">{{ ucfirst($interviewStatus) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">{{ $trUpper('Rapor Tarihi') }}</td>
                        <td class="val">{{ $generatedAt }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="hr-line"></div>

    {{-- Executive Summary --}}
    <div class="exec-summary">
        <div class="exec-summary-title">{{ $trUpper('Yönetici Özeti') }}</div>
        <table class="exec-kpi-table">
            <tr>
                <td style="width: 28%;">
                    <div class="kpi-label">{{ $trUpper('Genel Puan') }}</div>
                    <div class="kpi-score-circle" style="background-color: {{ $scoreColorHex }};">
                        {{ round($overallScore) }}
                    </div>
                </td>
                <td style="width: 32%;">
                    <div class="kpi-label">{{ $trUpper('Karar') }}</div>
                    <div class="kpi-decision" style="background-color: {{ $decisionColorHex }};">
                        {{ $decisionLabel }}
                    </div>
                </td>
                <td style="width: 22%;">
                    <div class="kpi-label">{{ $trUpper('Güven Oranı') }}</div>
                    <div class="kpi-confidence">
                        {{ $confidencePercent !== null ? '%' . $confidencePercent : '-' }}
                    </div>
                </td>
                <td style="width: 18%;">
                    <div class="kpi-label">{{ $trUpper('Mülakat Durumu') }}</div>
                    <div class="kpi-status">{{ ucfirst($interviewStatus) }}</div>
                </td>
            </tr>
        </table>

        @if(!empty($reasons))
        <div class="reasons-section">
            <div class="reasons-heading">{{ $trUpper('Karar Gerekçesi') }}</div>
            @foreach($reasons as $i => $reason)
                <div class="reason-row">
                    <span class="reason-num">{{ $i + 1 }}</span>
                    {{ $reason }}
                </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Quick Stats if competencies exist --}}
    @if(!empty($competencyScores))
    @php
        $allScores = [];
        foreach ($competencyScores as $code => $val) {
            $s = is_array($val) ? ($val['score'] ?? 0) : (is_numeric($val) ? $val : 0);
            if (is_numeric($s)) $allScores[] = (float)$s;
        }
        $maxComp = !empty($allScores) ? max($allScores) : 0;
        $minComp = !empty($allScores) ? min($allScores) : 0;
        $avgComp = !empty($allScores) ? array_sum($allScores) / count($allScores) : 0;
    @endphp
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
        <tr>
            <td style="width: 33%; padding: 8px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 7pt; color: #64748b; letter-spacing: 0.5px;">{{ $trUpper('En Yüksek Puan') }}</div>
                <div style="font-size: 18pt; font-weight: bold; color: #16a34a;">{{ round($maxComp) }}</div>
            </td>
            <td style="width: 33%; padding: 8px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 7pt; color: #64748b; letter-spacing: 0.5px;">{{ $trUpper('Ortalama Puan') }}</div>
                <div style="font-size: 18pt; font-weight: bold; color: #0f172a;">{{ round($avgComp) }}</div>
            </td>
            <td style="width: 33%; padding: 8px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 7pt; color: #64748b; letter-spacing: 0.5px;">{{ $trUpper('En Düşük Puan') }}</div>
                <div style="font-size: 18pt; font-weight: bold; color: #dc2626;">{{ round($minComp) }}</div>
            </td>
        </tr>
    </table>
    @endif

    <div class="page-footer">
        Mülakat Değerlendirme Raporu &middot; {{ $generatedAt }} &middot; Octopus AI &middot; Sayfa 1
    </div>
</div>

{{-- ════════════════════════════════════════════════════════
     PAGE 2: COMPETENCY ANALYSIS + RADAR
     ════════════════════════════════════════════════════════ --}}
<div class="page">
    <div class="pg-header">
        <table class="pg-header-table">
            <tr>
                <td class="pg-header-company">{{ $companyName }}</td>
                <td class="pg-header-meta">{{ $candidateFullName }} &middot; {{ $jobTitle }}</td>
            </tr>
        </table>
    </div>

    <div class="sec-header">Yetkinlik Analizi</div>

    @if(!empty($competencyScores))
        @if($radarChartDataUri)
        <div class="radar-wrap">
            {!! '<img src="' . $radarChartDataUri . '" width="400" height="400" style="display: inline-block;" />' !!}
        </div>
        @endif

        <div class="sub-header">Yetkinlik Puanları</div>
        <table class="bar-table">
        @foreach($competencyScores as $code => $val)
            @php
                $score = is_array($val) ? ($val['score'] ?? 0) : (is_numeric($val) ? $val : 0);
                $label = $competencyLabels[$code] ?? ucwords(str_replace('_', ' ', $code));
                $barColor = $getScoreColor($score);
                $barWidth = min(100, max(0, $score));
            @endphp
            <tr>
                <td class="bar-label">{{ $label }}</td>
                <td>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: {{ $barWidth }}%; background-color: {{ $barColor }};"></div>
                    </div>
                </td>
                <td class="bar-num" style="color: {{ $barColor }};">{{ round($score) }}</td>
            </tr>
        @endforeach
        </table>

        <div class="sub-header">Detaylı Değerlendirme</div>
        <table class="detail-table">
            <tr>
                <th>Yetkinlik</th>
                <th>Puan</th>
                <th>Kanıtlar</th>
                <th>Gelişim Alanları</th>
            </tr>
            @foreach($competencyScores as $code => $val)
                @php
                    $score = is_array($val) ? ($val['score'] ?? 0) : (is_numeric($val) ? $val : 0);
                    $evidence = is_array($val) ? ($val['evidence'] ?? []) : [];
                    $improvements = is_array($val) ? ($val['improvement_areas'] ?? []) : [];
                    $label = $competencyLabels[$code] ?? ucwords(str_replace('_', ' ', $code));
                    $tdScoreColor = $getScoreColor($score);
                @endphp
                <tr>
                    <td style="font-weight: bold;">{{ $label }}</td>
                    <td style="color: {{ $tdScoreColor }};">{{ round($score) }}</td>
                    <td>
                        @if(!empty($evidence))
                            @foreach($evidence as $e)
                                <div style="margin-bottom: 1px;">&#8226; {{ $e }}</div>
                            @endforeach
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                    <td>
                        @if(!empty($improvements))
                            @foreach($improvements as $imp)
                                <div style="margin-bottom: 1px;">&#8226; {{ $imp }}</div>
                            @endforeach
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @else
        <p style="color: #94a3b8; font-style: italic; padding: 10px 0;">Yetkinlik puanı verisi bulunmamaktadır.</p>
    @endif

    <div class="page-footer">
        Mülakat Değerlendirme Raporu &middot; {{ $generatedAt }} &middot; Octopus AI &middot; Sayfa 2
    </div>
</div>

{{-- ════════════════════════════════════════════════════════
     PAGE 3: BEHAVIOR ANALYSIS + QUESTIONS
     ════════════════════════════════════════════════════════ --}}
<div class="page">
    <div class="pg-header">
        <table class="pg-header-table">
            <tr>
                <td class="pg-header-company">{{ $companyName }}</td>
                <td class="pg-header-meta">{{ $candidateFullName }} &middot; {{ $jobTitle }}</td>
            </tr>
        </table>
    </div>

    {{-- Behavior Analysis --}}
    @if(!empty($behaviorAnalysis))
    <div class="sec-header">Davranış Analizi</div>

    @php
        $behaviorScalar = [];
        foreach ($behaviorAnalysis as $key => $value) {
            if (!is_array($value)) $behaviorScalar[$key] = $value;
        }
        $bKeys = array_keys($behaviorScalar);
        $bCount = count($bKeys);
    @endphp

    @if($bCount > 0)
    <table class="metric-table">
        @for($row = 0; $row < ceil($bCount / 5); $row++)
        <tr>
            @for($col = 0; $col < 5; $col++)
                @php $idx = $row * 5 + $col; @endphp
                @if($idx < $bCount)
                    @php
                        $bKey = $bKeys[$idx];
                        $bValue = $behaviorScalar[$bKey];
                        $bLabel = $behaviorLabels[$bKey] ?? ucwords(str_replace('_', ' ', $bKey));
                        $bIsNumeric = is_numeric($bValue);
                        $bColorHex = $bIsNumeric ? $getScoreColor($bValue) : '#0f172a';
                        $bBgColor = $bIsNumeric ? $getScoreBg($bValue) : '#f8fafc';
                    @endphp
                    <td style="width: 20%;">
                        <div class="metric-card" style="background-color: {{ $bBgColor }};">
                            <div class="metric-card-label">{{ $trUpper($bLabel) }}</div>
                            @if($bIsNumeric)
                                <div class="metric-card-value" style="color: {{ $bColorHex }};">{{ round($bValue) }}</div>
                            @else
                                <div class="metric-card-text" style="color: {{ $bColorHex }};">{{ ucfirst(str_replace('_', ' ', (string) $bValue)) }}</div>
                            @endif
                        </div>
                    </td>
                @else
                    <td style="width: 20%;"></td>
                @endif
            @endfor
        </tr>
        @endfor
    </table>
    @endif
    @endif

    {{-- Question-by-Question --}}
    @if(!empty($questionAnalyses))
    <div class="sec-header">Soru Bazlı Analiz</div>

    @foreach($questionAnalyses as $qa)
        @php
            $qScore = $qa['score'] ?? 0;
            $qCompetency = $qa['competency_code'] ?? '';
            $qLabel = $competencyLabels[$qCompetency] ?? ucwords(str_replace('_', ' ', $qCompetency));
            $qAnalysis = $qa['analysis'] ?? '';
            $qPositive = $qa['positive_points'] ?? [];
            $qNegative = $qa['negative_points'] ?? [];
            $qOrder = $qa['question_order'] ?? '-';
            $qScorePercent = $qScore * 20;
            $qScoreColor = $getScoreColor($qScorePercent);
            $qScoreBg = $getScoreBg($qScorePercent);
        @endphp
        <div class="qa-block" style="border-left: 3px solid {{ $qScoreColor }};">
            <table class="qa-header-table">
                <tr>
                    <td>
                        <div class="qa-meta">
                            <span style="font-weight: bold; color: #0f172a;">Soru {{ $qOrder }}</span> &middot;
                            <span>{{ $qLabel }}</span>
                        </div>
                    </td>
                    <td style="text-align: right; width: 55px;">
                        <span class="qa-score-tag" style="background-color: {{ $qScoreBg }}; color: {{ $qScoreColor }};">
                            {{ $qScore }}/5
                        </span>
                    </td>
                </tr>
            </table>

            @if($qAnalysis)
                <div class="qa-analysis">{{ $qAnalysis }}</div>
            @endif

            @if(!empty($qPositive) || !empty($qNegative))
            <table class="qa-points-table">
                <tr>
                    <td>
                        @if(!empty($qPositive))
                            <div class="qa-points-heading" style="color: #16a34a;">Olumlu Noktalar</div>
                            @foreach($qPositive as $p)
                                <div class="qa-point-good">+ {{ $p }}</div>
                            @endforeach
                        @endif
                    </td>
                    <td>
                        @if(!empty($qNegative))
                            <div class="qa-points-heading" style="color: #dc2626;">Olumsuz Noktalar</div>
                            @foreach($qNegative as $n)
                                <div class="qa-point-bad">- {{ $n }}</div>
                            @endforeach
                        @endif
                    </td>
                </tr>
            </table>
            @endif
        </div>
    @endforeach
    @endif

    <div class="page-footer">
        Mülakat Değerlendirme Raporu &middot; {{ $generatedAt }} &middot; Octopus AI &middot; Sayfa 3
    </div>
</div>

{{-- ════════════════════════════════════════════════════════
     PAGE 4: CULTURE FIT + RISK FLAGS + DECISION + LEGAL
     ════════════════════════════════════════════════════════ --}}
@php
    $hasRedFlags = !empty($redFlagAnalysis) && (
        (!empty($redFlagAnalysis['flags_detected']) && $redFlagAnalysis['flags_detected']) ||
        !empty($redFlagAnalysis['flags'])
    );
@endphp

<div class="page">
    <div class="pg-header">
        <table class="pg-header-table">
            <tr>
                <td class="pg-header-company">{{ $companyName }}</td>
                <td class="pg-header-meta">{{ $candidateFullName }} &middot; {{ $jobTitle }}</td>
            </tr>
        </table>
    </div>

    {{-- Culture Fit --}}
    @if(!empty($cultureFit))
    <div class="sec-header">Kültür Uyumu</div>

    @php
        $cfScalar = [];
        foreach ($cultureFit as $key => $value) {
            if (!is_array($value)) $cfScalar[$key] = $value;
        }
        $cfKeys = array_keys($cfScalar);
        $cfCount = count($cfKeys);
    @endphp

    @if($cfCount > 0)
    <table class="metric-table">
        @for($row = 0; $row < ceil($cfCount / 4); $row++)
        <tr>
            @for($col = 0; $col < 4; $col++)
                @php $idx = $row * 4 + $col; @endphp
                @if($idx < $cfCount)
                    @php
                        $cfKey = $cfKeys[$idx];
                        $cfValue = $cfScalar[$cfKey];
                        $cfLabel = $cultureFitLabels[$cfKey] ?? ucwords(str_replace('_', ' ', $cfKey));
                        $cfIsNumeric = is_numeric($cfValue);
                        $cfColorHex = $cfIsNumeric ? $getScoreColor($cfValue) : '#0f172a';
                        $cfBgColor = $cfIsNumeric ? $getScoreBg($cfValue) : '#f8fafc';
                    @endphp
                    <td style="width: 25%;">
                        <div class="metric-card" style="background-color: {{ $cfBgColor }};">
                            <div class="metric-card-label">{{ $trUpper($cfLabel) }}</div>
                            @if($cfIsNumeric)
                                <div class="metric-card-value" style="color: {{ $cfColorHex }};">{{ round($cfValue) }}</div>
                            @else
                                <div class="metric-card-text" style="color: {{ $cfColorHex }};">{{ ucfirst(str_replace('_', ' ', (string) $cfValue)) }}</div>
                            @endif
                        </div>
                    </td>
                @else
                    <td style="width: 25%;"></td>
                @endif
            @endfor
        </tr>
        @endfor
    </table>
    @endif
    @endif

    {{-- Risk Flags --}}
    <div class="sec-header">Risk Bayrakları</div>

    @if($hasRedFlags && !empty($redFlagAnalysis['flags']))
        @if(!empty($redFlagAnalysis['overall_risk']))
            @php
                $overallRisk = strtolower($redFlagAnalysis['overall_risk']);
                $riskBgColor = ($overallRisk === 'high' || $overallRisk === 'critical') ? '#dc2626' : ($overallRisk === 'medium' ? '#d97706' : '#16a34a');
            @endphp
            <div style="margin-bottom: 10px; font-size: 8.5pt;">
                <strong style="color: #334155;">Genel Risk Seviyesi:</strong>
                <span style="display: inline-block; padding: 2px 10px; background-color: {{ $riskBgColor }}; color: #ffffff; font-size: 7pt; font-weight: bold; margin-left: 4px;">
                    {{ strtoupper($redFlagAnalysis['overall_risk']) }}
                </span>
            </div>
        @endif

        @foreach($redFlagAnalysis['flags'] as $flag)
            @php
                $severity = strtolower($flag['severity'] ?? 'medium');
                $severityBg = ($severity === 'high' || $severity === 'critical') ? '#dc2626' : ($severity === 'medium' ? '#d97706' : '#16a34a');
                $riskClass = in_array($severity, ['high', 'critical']) ? 'risk-block-high' : ($severity === 'medium' ? 'risk-block-medium' : 'risk-block-low');
            @endphp
            <div class="risk-block {{ $riskClass }}">
                <span class="risk-severity-tag" style="background-color: {{ $severityBg }};">{{ strtoupper($flag['severity'] ?? 'MEDIUM') }}</span>
                <span class="risk-code-text">{{ $flag['code'] ?? '-' }}</span>
                @if(!empty($flag['question_order']))
                    <span style="font-size: 7pt; color: #64748b; margin-left: 4px;">Soru {{ $flag['question_order'] }}</span>
                @endif
                @if(!empty($flag['detected_phrase']))
                    <div class="risk-phrase-text">&ldquo;{{ $flag['detected_phrase'] }}&rdquo;</div>
                @endif
            </div>
        @endforeach
    @else
        <div class="no-risk-box">
            <span style="color: #16a34a; font-weight: bold; font-size: 10pt;">&#10003;</span>
            <span style="color: #16a34a; font-size: 8.5pt; margin-left: 6px;">Kritik risk bayrağı tespit edilmedi.</span>
        </div>
    @endif

    {{-- Decision Details --}}
    @if(!empty($decisionSnapshot['reasons']) || !empty($decisionSnapshot['suggested_questions']))
    <div class="sec-header">Karar Detayları</div>

    <div class="decision-box">
        @if(!empty($decisionSnapshot['reasons']))
            <div class="decision-box-heading">{{ $trUpper('Gerekçe') }}</div>
            @foreach($decisionSnapshot['reasons'] as $i => $reason)
                <div class="reason-row">
                    <span class="reason-num">{{ $i + 1 }}</span>
                    {{ $reason }}
                </div>
            @endforeach
        @endif

        @if(!empty($decisionSnapshot['suggested_questions']))
            <div class="decision-box-heading" style="margin-top: 12px;">{{ $trUpper('Önerilen Takip Soruları') }}</div>
            @foreach($decisionSnapshot['suggested_questions'] as $sq)
                <div class="follow-up-row">&bull; {{ $sq }}</div>
            @endforeach
        @endif
    </div>
    @endif

    {{-- Disclaimer --}}
    <div class="disclaimer-block">
        <strong>Yasal Uyarı:</strong> Bu rapor yapay zekâ destekli analiz sonuçlarını içermektedir.
        Nihai karar işveren tarafından verilmelidir. Bu belge, oluşturulduğu andaki verilerin bir
        anlık görüntüsüdür ve bağlayıcı bir hukuki belge niteliği taşımaz.
        <br><br>
        <strong>Gizlilik:</strong> Bu rapor yalnızca yetkili personel tarafından görüntülenmelidir.
        İzinsiz dağıtım veya çoğaltma yasaktır.
        <br><br>
        <strong>KVKK Uyumu:</strong> Bu rapor, 6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında
        hazırlanmıştır. Kişisel veriler yalnızca işe alım değerlendirmesi amacıyla işlenmektedir.
    </div>

    <div style="text-align: center; margin-top: 12px; font-size: 7pt; color: #94a3b8;">
        Mülakat Değerlendirme Raporu v3 &middot; {{ $generatedAt }} UTC &middot; Octopus AI
    </div>

    <div class="page-footer">
        Mülakat Değerlendirme Raporu &middot; {{ $generatedAt }} &middot; Octopus AI &middot; Sayfa 4
    </div>
</div>

</body>
</html>
