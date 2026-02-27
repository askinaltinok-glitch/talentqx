<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Decision Packet - {{ $candidate->first_name }} {{ $candidate->last_name }}</title>
    <style>
        @page { margin: 30px 35px 55px 35px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; color: #1e293b; padding: 0 5px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 5px 40px; border-top: 2px solid #6366f1; background: #f8fafc; display: table; width: 100%; }
        .footer-left { display: table-cell; text-align: left; font-size: 7px; color: #64748b; }
        .footer-center { display: table-cell; text-align: center; font-size: 7px; color: #94a3b8; }
        .footer-right { display: table-cell; text-align: right; font-size: 7px; color: #64748b; }
        .header { display: table; width: 100%; padding-bottom: 8px; border-bottom: 3px solid #6366f1; margin-bottom: 12px; }
        .header-left { display: table-cell; vertical-align: middle; width: 55%; }
        .header-right { display: table-cell; vertical-align: middle; width: 45%; text-align: right; }
        .brand { font-size: 22px; font-weight: bold; color: #6366f1; letter-spacing: -0.5px; }
        .brand-sub { font-size: 7px; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; }
        .report-title { font-size: 14px; font-weight: bold; color: #6366f1; }
        .report-meta { font-size: 9px; color: #64748b; margin-top: 2px; }
        .kpi-row { display: table; width: 100%; margin-bottom: 14px; }
        .kpi-box { display: table-cell; width: 25%; text-align: center; padding: 8px 4px; vertical-align: top; }
        .kpi-value { font-size: 24px; font-weight: bold; }
        .kpi-label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; letter-spacing: 0.5px; }
        .badge-approve { background: #dcfce7; color: #15803d; }
        .badge-review { background: #fef3c7; color: #b45309; }
        .badge-reject { background: #fee2e2; color: #dc2626; }
        .section-title { font-size: 12px; font-weight: bold; color: #6366f1; margin-top: 14px; margin-bottom: 6px; padding-bottom: 3px; border-bottom: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #f1f5f9; color: #475569; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; padding: 5px 8px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-size: 9px; }
        .tile-row { display: table; width: 100%; margin-bottom: 10px; }
        .tile { display: table-cell; width: 25%; padding: 6px; vertical-align: top; }
        .tile-inner { border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px; }
        .tile-label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .tile-value { font-size: 16px; font-weight: bold; color: #6366f1; }
        .tile-sub { font-size: 8px; color: #475569; margin-top: 2px; }
        .strength-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #22c55e; margin-right: 4px; }
        .risk-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #ef4444; margin-right: 4px; }
        .action-box { background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 4px; padding: 8px 12px; font-size: 10px; color: #4338ca; margin: 10px 0; }
        .confidentiality { margin-top: 20px; padding: 8px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 7px; color: #94a3b8; text-align: center; }
        .page-break { page-break-before: always; }
        .score-bar { display: inline-block; height: 8px; border-radius: 4px; vertical-align: middle; }
    </style>
</head>
<body>

<div class="footer">
    <div class="footer-left">TalentQX &mdash; Decision Packet</div>
    <div class="footer-center">CONFIDENTIAL</div>
    <div class="footer-right">Generated {{ now()->format('d M Y H:i') }} UTC &middot; System: TalentQX</div>
</div>

<!-- PAGE 1: ASSESSMENT OVERVIEW -->
<div class="header">
    <div class="header-left"><div class="brand">TalentQX</div><div class="brand-sub">HR Intelligence Platform</div></div>
    <div class="header-right"><div class="report-title">Decision Packet</div><div class="report-meta">{{ $candidate->first_name }} {{ $candidate->last_name }}<br>Generated {{ now()->format('d M Y') }}</div></div>
</div>

<table>
    <thead><tr><th colspan="4">Candidate Information</th></tr></thead>
    <tbody>
        <tr>
            <td style="width:15%;font-weight:bold;">Name</td>
            <td style="width:35%;">{{ $candidate->first_name }} {{ $candidate->last_name }}</td>
            <td style="width:15%;font-weight:bold;">Email</td>
            <td>{{ $candidate->email ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold;">Phone</td>
            <td>{{ $candidate->phone ?? 'N/A' }}</td>
            <td style="font-weight:bold;">Source</td>
            <td>{{ ucfirst(str_replace('_', ' ', $candidate->source ?? 'N/A')) }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold;">Status</td>
            <td>{{ ucfirst(str_replace('_', ' ', $candidate->status ?? 'N/A')) }}</td>
            <td style="font-weight:bold;">Position</td>
            <td>{{ $jobTitle ?? 'N/A' }}</td>
        </tr>
    </tbody>
</table>

@php
    $recommendation = $analysis->getRecommendation() ?? 'review';
    $confidence = $analysis->getConfidencePercent();
    $recBadge = match(strtolower($recommendation)) {
        'hire', 'approve' => 'badge-approve',
        'reject' => 'badge-reject',
        default => 'badge-review',
    };
    $overallScore = $analysis->overall_score ?? 0;
    $cultureFit = $analysis->getCultureFitScore();
    $cheatingLevel = $analysis->cheating_level ?? 'low';
@endphp

<div class="kpi-row">
    <div class="kpi-box">
        <div style="padding-top:4px;"><span class="badge {{ $recBadge }}">{{ strtoupper($recommendation) }}</span></div>
        <div class="kpi-label" style="margin-top:4px;">Recommendation</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-value" style="color:{{ $overallScore >= 75 ? '#15803d' : ($overallScore >= 50 ? '#b45309' : '#dc2626') }};">{{ round($overallScore) }}</div>
        <div class="kpi-label">Overall Score</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-value" style="color:#6366f1;">{{ $confidence !== null ? $confidence . '%' : 'N/A' }}</div>
        <div class="kpi-label">Confidence</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-value" style="color:{{ $cultureFit !== null && $cultureFit >= 70 ? '#15803d' : ($cultureFit !== null && $cultureFit >= 50 ? '#b45309' : '#dc2626') }};">{{ $cultureFit !== null ? round($cultureFit) : 'N/A' }}</div>
        <div class="kpi-label">Culture Fit</div>
    </div>
</div>

<div class="tile-row">
    <div class="tile"><div class="tile-inner">
        <div class="tile-label">Interview Duration</div>
        <div class="tile-value">{{ $interview->getDurationInMinutes() ?? 'N/A' }}<span style="font-size:10px;color:#64748b;"> min</span></div>
    </div></div>
    <div class="tile"><div class="tile-inner">
        <div class="tile-label">Completed</div>
        <div class="tile-value" style="font-size:12px;">{{ $interview->completed_at?->format('d M Y') ?? 'N/A' }}</div>
        <div class="tile-sub">{{ $interview->completed_at?->format('H:i') ?? '' }} UTC</div>
    </div></div>
    <div class="tile"><div class="tile-inner">
        <div class="tile-label">Red Flags</div>
        <div class="tile-value" style="color:{{ $analysis->hasRedFlags() ? '#dc2626' : '#15803d' }};">{{ $analysis->getRedFlagsCount() }}</div>
        <div class="tile-sub">{{ $analysis->hasRedFlags() ? 'Flags detected' : 'No flags' }}</div>
    </div></div>
    <div class="tile"><div class="tile-inner">
        <div class="tile-label">Cheating Risk</div>
        <div class="tile-value" style="color:{{ $cheatingLevel === 'high' ? '#dc2626' : ($cheatingLevel === 'medium' ? '#b45309' : '#15803d') }};">{{ strtoupper($cheatingLevel) }}</div>
        <div class="tile-sub">{{ $analysis->cheating_risk_score ? round($analysis->cheating_risk_score) . '/100' : '' }}</div>
    </div></div>
</div>

{{-- Decision reasons --}}
@if(!empty($analysis->getReasons()))
<div class="action-box">
    <strong>Decision Rationale:</strong>
    @foreach($analysis->getReasons() as $reason)
        &bull; {{ $reason }}
    @endforeach
</div>
@endif

{{-- Competency Scores --}}
@if(!empty($analysis->competency_scores))
<div class="section-title">Competency Scores</div>
<table>
    <thead><tr><th style="width:35%;">Competency</th><th style="width:15%;text-align:center;">Score</th><th>Visual</th></tr></thead>
    <tbody>
        @foreach($analysis->competency_scores as $code => $scoreData)
        @php
            $score = is_array($scoreData) ? ($scoreData['score'] ?? 0) : $scoreData;
            $scoreColor = $score >= 75 ? '#15803d' : ($score >= 50 ? '#b45309' : '#dc2626');
            $barWidth = min(max($score, 0), 100);
        @endphp
        <tr>
            <td style="font-weight:bold;">{{ ucfirst(str_replace('_', ' ', $code)) }}</td>
            <td style="text-align:center;font-weight:bold;color:{{ $scoreColor }};">{{ round($score) }}</td>
            <td>
                <span class="score-bar" style="width:{{ $barWidth }}px;background:{{ $scoreColor }};"></span>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Behavioral Analysis --}}
@if(!empty($analysis->behavior_analysis))
<div class="section-title">Behavioral Analysis</div>
<table>
    <thead><tr><th style="width:35%;">Trait</th><th>Assessment</th></tr></thead>
    <tbody>
        @foreach($analysis->behavior_analysis as $key => $value)
        <tr>
            <td style="font-weight:bold;">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
            <td>@if(is_array($value)){{ json_encode($value) }}@else{{ $value }}@endif</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Red Flags Detail --}}
@if($analysis->hasRedFlags() && !empty($analysis->red_flag_analysis['flags']))
<div class="section-title">Red Flags</div>
<table>
    <thead><tr><th style="width:30%;">Flag</th><th>Detail</th></tr></thead>
    <tbody>
        @foreach($analysis->red_flag_analysis['flags'] as $flag)
        <tr>
            <td style="font-weight:bold;color:#dc2626;">
                <span class="risk-dot"></span>
                {{ is_array($flag) ? ($flag['type'] ?? $flag['flag'] ?? 'Unknown') : $flag }}
            </td>
            <td style="font-size:8px;">{{ is_array($flag) ? ($flag['detail'] ?? $flag['description'] ?? '') : '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<!-- PAGE 2: QUESTION ANALYSIS -->
@if($interview->responses && $interview->responses->count() > 0)
<div class="page-break"></div>
<div class="header">
    <div class="header-left"><div class="brand">TalentQX</div><div class="brand-sub">HR Intelligence Platform</div></div>
    <div class="header-right"><div class="report-title">Question Analysis</div><div class="report-meta">{{ $candidate->first_name }} {{ $candidate->last_name }}<br>Generated {{ now()->format('d M Y') }}</div></div>
</div>

@php $questionAnalyses = $analysis->question_analyses ?? []; @endphp

<table>
    <thead><tr>
        <th style="width:6%;">#</th>
        <th style="width:30%;">Question</th>
        <th style="width:8%;text-align:center;">Score</th>
        <th>Evidence Extract</th>
    </tr></thead>
    <tbody>
        @foreach($interview->responses->sortBy('response_order') as $i => $response)
        @php
            $qAnalysis = $questionAnalyses[$i] ?? $questionAnalyses[$response->response_order] ?? null;
            $qScore = null;
            if (is_array($qAnalysis)) {
                $qScore = $qAnalysis['score'] ?? $qAnalysis['question_score'] ?? null;
            }
            $transcript = $response->transcript ?? '';
            $evidenceWords = array_slice(explode(' ', trim($transcript)), 0, 30);
            $evidence = implode(' ', $evidenceWords);
            if (count(explode(' ', trim($transcript))) > 30) $evidence .= '...';
            $questionText = $response->question?->question_text ?? '';
        @endphp
        <tr>
            <td style="font-weight:bold;color:#6366f1;">Q{{ $response->response_order }}</td>
            <td style="font-size:8px;color:#475569;">{{ \Illuminate\Support\Str::limit($questionText, 80) }}</td>
            <td style="text-align:center;font-weight:bold;color:{{ ($qScore ?? 0) >= 75 ? '#15803d' : (($qScore ?? 0) >= 50 ? '#b45309' : '#dc2626') }};">{{ $qScore !== null ? round($qScore) : '—' }}</td>
            <td style="font-size:8px;color:#475569;">{{ $evidence }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<p style="font-size:7px;color:#94a3b8;margin-top:2px;">Evidence: first 30 words of candidate response. Full text redacted.</p>
@endif

{{-- Suggested Follow-up Questions --}}
@if(!empty($analysis->getSuggestedQuestions()))
<div class="section-title">Suggested Follow-up Questions</div>
@foreach($analysis->getSuggestedQuestions() as $i => $sq)
<div style="padding:3px 0;font-size:9px;">
    <strong>{{ $i + 1 }}.</strong> {{ is_array($sq) ? ($sq['question'] ?? $sq) : $sq }}
</div>
@endforeach
@endif

<div class="confidentiality">
    <strong>AUDIT TRAIL</strong><br>
    Decision Packet &middot; Generated: {{ now()->format('d M Y H:i:s') }} UTC &middot; System: TalentQX<br>
    Interview ID: {{ $interview->id }} &middot; Checksum: {{ $checksum }}<br>
    Generated by: {{ $generatedBy }}<br>
    <br>
    <strong>DISCLAIMER:</strong> This document is a point-in-time snapshot. Data reflects the state at generation time. Independent verification recommended.
    <br>TalentQX &copy; {{ date('Y') }}. CONFIDENTIAL. Report ID: {{ strtoupper(substr(md5($interview->id . now()->toDateString()), 0, 12)) }}
</div>

</body>
</html>
