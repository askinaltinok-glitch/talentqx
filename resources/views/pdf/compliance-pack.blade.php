<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Compliance Pack - {{ $candidate->first_name }} {{ $candidate->last_name }}</title>
    <style>
        @page { margin: 30px 35px 55px 35px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; color: #1e293b; padding: 0 5px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 5px 40px; border-top: 2px solid #0f4c81; background: #f8fafc; display: table; width: 100%; }
        .footer-left { display: table-cell; text-align: left; font-size: 7px; color: #64748b; }
        .footer-center { display: table-cell; text-align: center; font-size: 7px; color: #94a3b8; }
        .footer-right { display: table-cell; text-align: right; font-size: 7px; color: #64748b; }
        .header { display: table; width: 100%; padding-bottom: 8px; border-bottom: 3px solid #0f4c81; margin-bottom: 12px; }
        .header-left { display: table-cell; vertical-align: middle; width: 55%; }
        .header-right { display: table-cell; vertical-align: middle; width: 45%; text-align: right; }
        .brand { font-size: 22px; font-weight: bold; color: #0f4c81; letter-spacing: -0.5px; }
        .brand-sub { font-size: 7px; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; }
        .report-title { font-size: 14px; font-weight: bold; color: #0f4c81; }
        .report-meta { font-size: 9px; color: #64748b; margin-top: 2px; }
        .kpi-row { display: table; width: 100%; margin-bottom: 14px; }
        .kpi-box { display: table-cell; width: 25%; text-align: center; padding: 8px 4px; vertical-align: top; }
        .kpi-value { font-size: 24px; font-weight: bold; }
        .kpi-label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; letter-spacing: 0.5px; }
        .badge-approve { background: #dcfce7; color: #15803d; }
        .badge-review { background: #fef3c7; color: #b45309; }
        .badge-reject { background: #fee2e2; color: #dc2626; }
        .badge-compliant { background: #dcfce7; color: #15803d; }
        .badge-needs-review { background: #fef3c7; color: #b45309; }
        .badge-not-compliant { background: #fee2e2; color: #dc2626; }
        .status-compliant { color: #15803d; }
        .status-needs-review { color: #b45309; }
        .status-not-compliant { color: #dc2626; }
        .section-title { font-size: 12px; font-weight: bold; color: #0f4c81; margin-top: 14px; margin-bottom: 6px; padding-bottom: 3px; border-bottom: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #f1f5f9; color: #475569; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; padding: 5px 8px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-size: 9px; }
        .bar-bg { background: #e2e8f0; border-radius: 3px; height: 10px; width: 100%; position: relative; }
        .bar-fill { border-radius: 3px; height: 10px; position: absolute; top: 0; left: 0; }
        .bar-green { background: #22c55e; }
        .bar-yellow { background: #eab308; }
        .bar-red { background: #ef4444; }
        .bar-gray { background: #94a3b8; }
        .radar-container { text-align: center; margin: 8px 0; }
        .radar-container img { width: 320px; height: 320px; }
        .flag-critical { background: #fee2e2; color: #dc2626; padding: 3px 8px; border-radius: 3px; font-size: 9px; margin-bottom: 3px; display: block; }
        .flag-warning { background: #fef3c7; color: #b45309; padding: 3px 8px; border-radius: 3px; font-size: 9px; margin-bottom: 3px; display: block; }
        .rec-item { padding: 5px 8px; background: #f8fafc; border-left: 3px solid #0f4c81; margin-bottom: 5px; font-size: 9px; }
        .rec-priority { display: inline-block; width: 18px; height: 18px; border-radius: 50%; background: #0f4c81; color: white; text-align: center; line-height: 18px; font-size: 8px; font-weight: bold; margin-right: 4px; }
        .rec-section { font-size: 8px; color: #64748b; text-transform: uppercase; }
        .rec-action { font-size: 8px; color: #475569; margin-top: 2px; padding-left: 22px; }
        .confidentiality { margin-top: 20px; padding: 8px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 7px; color: #94a3b8; text-align: center; }
        .page-break { page-break-before: always; }
        .tile-row { display: table; width: 100%; margin-bottom: 10px; }
        .tile { display: table-cell; width: 25%; padding: 6px; vertical-align: top; }
        .tile-inner { border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px; height: 100%; }
        .tile-label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .tile-value { font-size: 16px; font-weight: bold; color: #0f4c81; }
        .tile-sub { font-size: 8px; color: #475569; margin-top: 2px; }
        .list-item { padding: 3px 0; font-size: 9px; border-bottom: 1px solid #f1f5f9; }
        .list-item:last-child { border-bottom: none; }
        .strength-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #22c55e; margin-right: 4px; }
        .risk-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #ef4444; margin-right: 4px; }
        .action-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; padding: 8px 12px; font-size: 10px; color: #1e40af; margin: 10px 0; }
    </style>
</head>
<body>

<div class="footer">
    <div class="footer-left">Octopus AI &mdash; Maritime Intelligence Platform</div>
    <div class="footer-center">CONFIDENTIAL</div>
    <div class="footer-right">Generated {{ now()->format('d M Y H:i') }} UTC</div>
</div>

<!-- ═══════════ PAGE 1: EXECUTIVE SNAPSHOT ═══════════ -->
<div class="header">
    <div class="header-left">
        <div class="brand">OCTOPUS AI</div>
        <div class="brand-sub">Maritime Intelligence Platform</div>
    </div>
    <div class="header-right">
        <div class="report-title">Compliance Pack &mdash; Executive Summary</div>
        <div class="report-meta">
            {{ $candidate->first_name }} {{ $candidate->last_name }}<br>
            Generated {{ now()->format('d M Y') }}
        </div>
    </div>
</div>

@php
    $score = $compliancePack['score'] ?? 0;
    $status = $compliancePack['status'] ?? 'unknown';
    $scoreColor = $score >= 70 ? 'status-compliant' : ($score >= 50 ? 'status-needs-review' : 'status-not-compliant');
    $badgeClass = match($status) {
        'compliant' => 'badge-compliant',
        'needs_review' => 'badge-needs-review',
        default => 'badge-not-compliant',
    };
    $statusLabel = match($status) {
        'compliant' => 'COMPLIANT',
        'needs_review' => 'NEEDS REVIEW',
        'not_compliant' => 'NOT COMPLIANT',
        default => strtoupper($status),
    };
    $decision = $execSummary['decision'] ?? null;
    $confidence = $execSummary['confidence_level'] ?? null;
    $decisionBadge = match($decision) {
        'approve' => 'badge-approve',
        'review' => 'badge-review',
        'reject' => 'badge-reject',
        default => 'badge-review',
    };
    $decisionLabel = strtoupper($decision ?? 'N/A');
@endphp

<!-- Decision + Compliance KPI Row -->
<div class="kpi-row">
    <div class="kpi-box">
        <div style="padding-top: 4px;"><span class="badge {{ $decisionBadge }}">{{ $decisionLabel }}</span></div>
        <div class="kpi-label" style="margin-top: 4px;">Decision</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-value {{ $scoreColor }}">{{ $score }}</div>
        <div class="kpi-label">Compliance Score</div>
    </div>
    <div class="kpi-box">
        <div style="padding-top: 4px;"><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></div>
        <div class="kpi-label" style="margin-top: 4px;">Compliance Status</div>
    </div>
    <div class="kpi-box">
        <div style="padding-top: 4px;"><span class="badge" style="background: #f1f5f9; color: #475569;">{{ strtoupper($confidence ?? 'N/A') }}</span></div>
        <div class="kpi-label" style="margin-top: 4px;">Confidence</div>
    </div>
</div>

<!-- 4 Engine Tiles -->
@if($execSummary)
@php $sc = $execSummary['scores'] ?? []; @endphp
<div class="tile-row">
    <div class="tile">
        <div class="tile-inner">
            <div class="tile-label">Verification (AIS)</div>
            <div class="tile-value">{{ $sc['verification']['confidence_score'] !== null ? round($sc['verification']['confidence_score'] * 100) . '%' : 'N/A' }}</div>
            <div class="tile-sub">Provider: {{ $sc['verification']['provider'] ?? 'N/A' }}</div>
            <div class="tile-sub">Anomalies: {{ $sc['verification']['anomaly_count'] ?? 0 }}</div>
        </div>
    </div>
    <div class="tile">
        <div class="tile-inner">
            <div class="tile-label">Technical</div>
            <div class="tile-value">{{ $sc['technical']['technical_score'] !== null ? round($sc['technical']['technical_score'] * 100) . '%' : 'N/A' }}</div>
            <div class="tile-sub">STCW: {{ ucfirst($sc['technical']['stcw_status'] ?? 'N/A') }}</div>
            <div class="tile-sub">Missing: {{ $sc['technical']['missing_cert_count'] ?? 0 }}</div>
        </div>
    </div>
    <div class="tile">
        <div class="tile-inner">
            <div class="tile-label">Stability / Risk</div>
            <div class="tile-value">{{ ucfirst($sc['stability_risk']['risk_tier'] ?? 'N/A') }}</div>
            <div class="tile-sub">Risk: {{ $sc['stability_risk']['risk_score'] !== null ? round($sc['stability_risk']['risk_score'], 3) : 'N/A' }}</div>
            <div class="tile-sub">Stability: {{ $sc['stability_risk']['stability_index'] !== null ? round($sc['stability_risk']['stability_index'], 2) : 'N/A' }}</div>
        </div>
    </div>
    <div class="tile">
        <div class="tile-inner">
            <div class="tile-label">Compliance</div>
            <div class="tile-value">{{ $sc['compliance']['compliance_score'] ?? 'N/A' }}</div>
            <div class="tile-sub">Status: {{ ucfirst(str_replace('_', ' ', $sc['compliance']['compliance_status'] ?? 'N/A')) }}</div>
            <div class="tile-sub">Critical: {{ $sc['compliance']['critical_flag_count'] ?? 0 }}</div>
        </div>
    </div>
</div>

<!-- Strengths + Risks -->
<div style="display: table; width: 100%; margin-bottom: 10px;">
    <div style="display: table-cell; width: 50%; padding-right: 6px; vertical-align: top;">
        <div class="section-title" style="margin-top: 0;">Strengths</div>
        @forelse($execSummary['top_strengths'] ?? [] as $s)
            <div class="list-item"><span class="strength-dot"></span> {{ $s }}</div>
        @empty
            <div class="list-item" style="color: #94a3b8;">No strengths identified</div>
        @endforelse
    </div>
    <div style="display: table-cell; width: 50%; padding-left: 6px; vertical-align: top;">
        <div class="section-title" style="margin-top: 0;">Risks</div>
        @forelse($execSummary['top_risks'] ?? [] as $r)
            <div class="list-item"><span class="risk-dot"></span> {{ $r }}</div>
        @empty
            <div class="list-item" style="color: #94a3b8;">No significant risks identified</div>
        @endforelse
    </div>
</div>

<!-- Action Line -->
<div class="action-box">
    {{ $execSummary['action_line'] ?? 'Executive summary not available.' }}
</div>
@endif

<!-- Recommendations (from compliance pack) -->
@php $recommendations = $compliancePack['recommendations'] ?? []; @endphp
@if(count($recommendations) > 0)
<div class="section-title">Recommendations</div>
@foreach($recommendations as $rec)
<div class="rec-item">
    <span class="rec-priority">{{ $rec['priority'] }}</span>
    <span class="rec-section">{{ $rec['section'] }}</span>
    &mdash; {{ $rec['recommendation'] }}
    <div class="rec-action">Action: {{ $rec['action'] }}</div>
</div>
@endforeach
@endif

@if($execSummary && ($execSummary['override']['is_active'] ?? false))
<div style="margin-top: 8px; padding: 6px 10px; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 4px; font-size: 9px;">
    <strong>OVERRIDE ACTIVE:</strong> Decision manually set to <strong>{{ strtoupper($execSummary['override']['decision']) }}</strong>
    by {{ $execSummary['override']['created_by']['name'] ?? 'admin' }}
    on {{ $execSummary['override']['created_at'] ? \Carbon\Carbon::parse($execSummary['override']['created_at'])->format('d M Y') : '-' }}.
    @if($execSummary['override']['expires_at'])
        Expires {{ \Carbon\Carbon::parse($execSummary['override']['expires_at'])->format('d M Y') }}.
    @endif
</div>
@endif

<div class="confidentiality">
    Report ID: {{ strtoupper(substr(md5($candidate->id . now()->toDateString()), 0, 12)) }}
    &bull; Computed {{ $execSummary['computed_at'] ?? now()->toIso8601String() }}
</div>

<!-- ═══════════ PAGE 2: DETAILED REPORT ═══════════ -->
<div class="page-break"></div>

<div class="header">
    <div class="header-left">
        <div class="brand">OCTOPUS AI</div>
        <div class="brand-sub">Maritime Intelligence Platform</div>
    </div>
    <div class="header-right">
        <div class="report-title">Compliance Pack &mdash; Detail</div>
        <div class="report-meta">{{ $candidate->first_name }} {{ $candidate->last_name }}</div>
    </div>
</div>

<!-- Radar Chart -->
@if($radarChart)
<div class="radar-container">
    <img src="{{ $radarChart }}" alt="Compliance Radar Chart">
</div>
@endif

<!-- Section Scores Table -->
<div class="section-title">Section Breakdown</div>
<table>
    <thead>
        <tr>
            <th>Section</th>
            <th>Raw Score</th>
            <th>Weight</th>
            <th>Weighted Score</th>
            <th style="width: 35%;">Progress</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sectionScores as $section)
        @php
            $sectionNames = ['cri' => 'CRI (Reliability)', 'technical' => 'Technical (Rank/STCW)', 'stability' => 'Stability & Risk', 'stcw' => 'STCW Compliance', 'ais' => 'AIS Verification'];
            $rawScore = $section['raw_score'] ?? 0;
            $barClass = !$section['available'] ? 'bar-gray' : ($rawScore >= 70 ? 'bar-green' : ($rawScore >= 50 ? 'bar-yellow' : 'bar-red'));
        @endphp
        <tr>
            <td style="font-weight: bold;">{{ $sectionNames[$section['section']] ?? $section['section'] }}</td>
            <td>{{ $section['available'] ? round($rawScore, 1) : 'N/A' }}</td>
            <td>{{ round($section['weight'] * 100) }}%</td>
            <td>{{ round($section['weighted_score'], 1) }}</td>
            <td>
                <div class="bar-bg">
                    <div class="bar-fill {{ $barClass }}" style="width: {{ $section['available'] ? min(100, $rawScore) : 0 }}%;"></div>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- Flags -->
@php $flags = $compliancePack['flags'] ?? []; @endphp
@if(count($flags) > 0)
<div class="section-title">Flags</div>
@foreach($flags as $flag)
<div class="{{ $flag['severity'] === 'critical' ? 'flag-critical' : 'flag-warning' }}">
    <strong>{{ strtoupper($flag['severity']) }}:</strong> {{ $flag['detail'] }}
</div>
@endforeach
@endif

<!-- Per-section breakdown -->
<div class="section-title">Section Details</div>

<table>
    <thead><tr><th colspan="2">CRI (Crew Reliability Index)</th></tr></thead>
    <tbody>
        <tr><td style="width: 40%;">Score</td><td>{{ round($trustProfile->cri_score ?? 0) }} / 100</td></tr>
        <tr><td>Confidence</td><td>{{ ucfirst($trustProfile->confidence_level ?? 'N/A') }}</td></tr>
    </tbody>
</table>

@if($rankStcw)
<table>
    <thead><tr><th colspan="2">Technical (Rank & STCW)</th></tr></thead>
    <tbody>
        <tr><td style="width: 40%;">Technical Score</td><td>{{ round(($rankStcw['technical_score'] ?? 0) * 100, 1) }} / 100</td></tr>
        @if(isset($rankStcw['promotion_gap']))
        <tr><td>Promotion Gap</td><td>{{ $rankStcw['promotion_gap']['gap_months'] ?? 'N/A' }} months</td></tr>
        @endif
    </tbody>
</table>
@if(isset($rankStcw['stcw_compliance']))
<table>
    <thead><tr><th colspan="2">STCW Certificate Compliance</th></tr></thead>
    <tbody>
        <tr><td style="width: 40%;">Compliance Ratio</td><td>{{ round(($rankStcw['stcw_compliance']['compliance_ratio'] ?? 0) * 100) }}%</td></tr>
        <tr><td>Missing Certificates</td><td>{{ $rankStcw['stcw_compliance']['missing_count'] ?? 0 }}</td></tr>
    </tbody>
</table>
@endif
@endif

@if($stabilityRisk)
<table>
    <thead><tr><th colspan="2">Stability & Risk</th></tr></thead>
    <tbody>
        <tr><td style="width: 40%;">Stability Index</td><td>{{ round($stabilityRisk['stability_index'] ?? 0, 2) }}</td></tr>
        <tr><td>Risk Score</td><td>{{ round(($stabilityRisk['risk_score'] ?? 0), 3) }}</td></tr>
        <tr><td>Risk Tier</td><td>{{ ucfirst($stabilityRisk['risk_tier'] ?? 'N/A') }}</td></tr>
    </tbody>
</table>
@endif

@php
    $aisContracts = $candidate->contracts->filter(fn($c) => $c->latestAisVerification && $c->latestAisVerification->confidence_score !== null);
@endphp
<table>
    <thead><tr><th colspan="2">AIS Verification</th></tr></thead>
    <tbody>
        <tr><td style="width: 40%;">Verified Contracts</td><td>{{ $aisContracts->count() }} / {{ $candidate->contracts->count() }}</td></tr>
        @if($aisContracts->count() > 0)
        <tr><td>Average Confidence</td><td>{{ round($aisContracts->avg(fn($c) => $c->latestAisVerification->confidence_score) * 100, 1) }}%</td></tr>
        @endif
    </tbody>
</table>

<div class="confidentiality">
    This report is confidential and intended for authorized personnel only.
    Octopus AI Maritime Intelligence Platform &copy; {{ date('Y') }}. All rights reserved.
</div>

</body>
</html>
