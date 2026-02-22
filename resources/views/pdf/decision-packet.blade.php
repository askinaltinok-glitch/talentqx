<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Decision Packet - {{ $candidate->first_name }} {{ $candidate->last_name }}</title>
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
        .section-title { font-size: 12px; font-weight: bold; color: #0f4c81; margin-top: 14px; margin-bottom: 6px; padding-bottom: 3px; border-bottom: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #f1f5f9; color: #475569; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; padding: 5px 8px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-size: 9px; }
        .tile-row { display: table; width: 100%; margin-bottom: 10px; }
        .tile { display: table-cell; width: 25%; padding: 6px; vertical-align: top; }
        .tile-inner { border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px; }
        .tile-label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .tile-value { font-size: 16px; font-weight: bold; color: #0f4c81; }
        .tile-sub { font-size: 8px; color: #475569; margin-top: 2px; }
        .list-item { padding: 3px 0; font-size: 9px; border-bottom: 1px solid #f1f5f9; }
        .list-item:last-child { border-bottom: none; }
        .strength-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #22c55e; margin-right: 4px; }
        .risk-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #ef4444; margin-right: 4px; }
        .action-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; padding: 8px 12px; font-size: 10px; color: #1e40af; margin: 10px 0; }
        .confidentiality { margin-top: 20px; padding: 8px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 7px; color: #94a3b8; text-align: center; }
        .page-break { page-break-before: always; }
        .radar-container { text-align: center; margin: 8px 0; }
        .radar-container img { width: 280px; height: 280px; }
    </style>
</head>
<body>

<div class="footer">
    <div class="footer-left">Octopus AI &mdash; Decision Packet</div>
    <div class="footer-center">CONFIDENTIAL</div>
    <div class="footer-right">Generated {{ now()->format('d M Y H:i') }} UTC</div>
</div>

<!-- PAGE 1: EXECUTIVE SNAPSHOT -->
<div class="header">
    <div class="header-left"><div class="brand">OCTOPUS AI</div><div class="brand-sub">Maritime Intelligence Platform</div></div>
    <div class="header-right"><div class="report-title">Decision Packet</div><div class="report-meta">{{ $candidate->first_name }} {{ $candidate->last_name }}<br>Generated {{ now()->format('d M Y') }}</div></div>
</div>

<table>
    <thead><tr><th colspan="4">Candidate Identity</th></tr></thead>
    <tbody>
        <tr><td style="width:15%;font-weight:bold;">Name</td><td style="width:35%;">{{ $candidate->first_name }} {{ $candidate->last_name }}</td><td style="width:15%;font-weight:bold;">Country</td><td>{{ $candidate->country_code ?? 'N/A' }}</td></tr>
        <tr><td style="font-weight:bold;">Status</td><td>{{ ucfirst(str_replace('_',' ',$candidate->status ?? 'N/A')) }}</td><td style="font-weight:bold;">Seafarer</td><td>{{ $candidate->seafarer ? 'Yes' : 'No' }}</td></tr>
    </tbody>
</table>

@php
    $decision = $execSummary['decision'] ?? 'N/A';
    $confidence = $execSummary['confidence_level'] ?? 'N/A';
    $decisionBadge = match($decision) { 'approve'=>'badge-approve','review'=>'badge-review','reject'=>'badge-reject',default=>'badge-review' };
@endphp

<div class="kpi-row">
    <div class="kpi-box"><div style="padding-top:4px;"><span class="badge {{ $decisionBadge }}">{{ strtoupper($decision) }}</span></div><div class="kpi-label" style="margin-top:4px;">Decision</div></div>
    <div class="kpi-box"><div style="padding-top:4px;"><span class="badge" style="background:#f1f5f9;color:#475569;">{{ strtoupper($confidence) }}</span></div><div class="kpi-label" style="margin-top:4px;">Confidence</div></div>
    <div class="kpi-box"><div class="kpi-value" style="color:#0f4c81;">{{ $execSummary['scores']['compliance']['compliance_score'] ?? 'N/A' }}</div><div class="kpi-label">Compliance Score</div></div>
    <div class="kpi-box"><div class="kpi-value" style="color:#0f4c81;">{{ ucfirst($execSummary['scores']['stability_risk']['risk_tier'] ?? 'N/A') }}</div><div class="kpi-label">Risk Tier</div></div>
</div>

@if($execSummary)
@php $sc = $execSummary['scores'] ?? []; @endphp
<div class="tile-row">
    <div class="tile"><div class="tile-inner"><div class="tile-label">Verification</div><div class="tile-value">{{ $sc['verification']['confidence_score'] !== null ? round($sc['verification']['confidence_score']*100).'%' : 'N/A' }}</div><div class="tile-sub">Anomalies: {{ $sc['verification']['anomaly_count'] ?? 0 }}</div></div></div>
    <div class="tile"><div class="tile-inner"><div class="tile-label">Technical</div><div class="tile-value">{{ $sc['technical']['technical_score'] !== null ? round($sc['technical']['technical_score']*100).'%' : 'N/A' }}</div><div class="tile-sub">STCW: {{ ucfirst($sc['technical']['stcw_status'] ?? 'N/A') }}</div></div></div>
    <div class="tile"><div class="tile-inner"><div class="tile-label">Stability</div><div class="tile-value">{{ $sc['stability_risk']['stability_index'] !== null ? round($sc['stability_risk']['stability_index'],2) : 'N/A' }}</div><div class="tile-sub">Risk: {{ $sc['stability_risk']['risk_score'] !== null ? round($sc['stability_risk']['risk_score'],3) : 'N/A' }}</div></div></div>
    <div class="tile"><div class="tile-inner"><div class="tile-label">Compliance</div><div class="tile-value">{{ $sc['compliance']['compliance_score'] ?? 'N/A' }}</div><div class="tile-sub">Critical: {{ $sc['compliance']['critical_flag_count'] ?? 0 }}</div></div></div>
</div>

@if(($sc['competency']['technical_depth_index'] ?? null) !== null)
<div class="tile-row">
    <div class="tile"><div class="tile-inner">
        <div class="tile-label">Technical Depth</div>
        <div class="tile-value">{{ round($sc['competency']['technical_depth_index']) }}</div>
        <div class="tile-sub">Domain expertise index</div>
    </div></div>
    <div class="tile"><div class="tile-inner">
        <div class="tile-label">Competency</div>
        <div class="tile-value">{{ $sc['competency']['competency_score'] ?? 'N/A' }}</div>
        <div class="tile-sub">{{ ucfirst($sc['competency']['competency_status'] ?? 'N/A') }}</div>
    </div></div>
    @if(isset($execSummary['correlation']))
    <div class="tile"><div class="tile-inner">
        <div class="tile-label">Behavioral Intel</div>
        <div class="tile-value">{{ count($execSummary['correlation']['correlation_flags'] ?? []) }}</div>
        <div class="tile-sub">Correlation flags</div>
    </div></div>
    @else
    <div class="tile"></div>
    @endif
    <div class="tile"></div>
</div>
@endif

@if(!empty($execSummary['correlation']['correlation_flags'] ?? []))
<div class="section-title" style="margin-top:6px;">Behavioral Intelligence</div>
@foreach($execSummary['correlation']['correlation_flags'] as $cf)
<div class="list-item"><span class="risk-dot"></span> <strong>{{ strtoupper(str_replace('_', ' ', $cf['flag'])) }}</strong>: {{ $cf['detail'] }}</div>
@endforeach
<p style="font-size:8px;color:#64748b;margin-top:4px;">{{ $execSummary['correlation']['correlation_summary'] ?? '' }}</p>
@endif

<div style="display:table;width:100%;margin-bottom:10px;">
    <div style="display:table-cell;width:50%;padding-right:6px;vertical-align:top;">
        <div class="section-title" style="margin-top:0;">Strengths</div>
        @forelse($execSummary['top_strengths'] ?? [] as $s)<div class="list-item"><span class="strength-dot"></span> {{ $s }}</div>@empty<div class="list-item" style="color:#94a3b8;">No strengths identified</div>@endforelse
    </div>
    <div style="display:table-cell;width:50%;padding-left:6px;vertical-align:top;">
        <div class="section-title" style="margin-top:0;">Risks</div>
        @forelse($execSummary['top_risks'] ?? [] as $r)<div class="list-item"><span class="risk-dot"></span> {{ $r }}</div>@empty<div class="list-item" style="color:#94a3b8;">No significant risks</div>@endforelse
    </div>
</div>

<div class="action-box">{{ $execSummary['action_line'] ?? 'Executive summary not available.' }}</div>
@if($radarChart)<div class="radar-container"><img src="{{ $radarChart }}" alt="Radar Chart"></div>@endif
@endif

<!-- PAGE 1.5: REASON MAP + ACTION PLAN -->
<div class="page-break"></div>
<div class="header">
    <div class="header-left"><div class="brand">OCTOPUS AI</div><div class="brand-sub">Maritime Intelligence Platform</div></div>
    <div class="header-right"><div class="report-title">Decision Rationale</div><div class="report-meta">{{ $candidate->first_name }} {{ $candidate->last_name }}<br>Generated {{ now()->format('d M Y') }}</div></div>
</div>

{{-- Reason Map: engine tiles with 1-line reason --}}
<div class="section-title">Score Rationale</div>
@if(!empty($execSummary['rationale']))
    @foreach($execSummary['rationale'] as $r)
    <div style="border:1px solid #e2e8f0; border-radius:4px; padding:6px 10px; margin-bottom:6px;">
        <div style="display:table;width:100%;">
            <div style="display:table-cell;width:30%;font-size:9px;font-weight:bold;color:#0f4c81;">{{ $r['label'] }}</div>
            <div style="display:table-cell;width:70%;font-size:9px;">{{ $r['top_reason'] }}</div>
        </div>
        @if(!empty($r['evidence']))
        <div style="font-size:8px;color:#475569;margin-top:3px;">
            @foreach($r['evidence'] as $e)
            <span>&bull; {{ $e }} </span>
            @endforeach
        </div>
        @endif
    </div>
    @endforeach
@else
<p style="font-size:9px;color:#94a3b8;">No rationale data available.</p>
@endif

{{-- Predictive Risk box (if present) --}}
@if(($execSummary['predictive_risk']['predictive_risk_index'] ?? null) !== null)
<div style="border:1px solid #fed7aa;background:#fff7ed;border-radius:4px;padding:8px 10px;margin:8px 0;">
    <div style="font-size:9px;font-weight:bold;color:#c2410c;">Predictive Risk: {{ round($execSummary['predictive_risk']['predictive_risk_index']) }}/100 ({{ $execSummary['predictive_risk']['predictive_tier'] }})</div>
    <div style="font-size:8px;color:#9a3412;">{{ $execSummary['predictive_risk']['reason_chain'][0] ?? '' }}</div>
</div>
@endif

{{-- Action Plan: WhatIfSimulator recommendations --}}
<div class="section-title" style="margin-top:12px;">Recommended Actions</div>
@forelse($execSummary['what_if'] ?? [] as $i => $action)
<div style="display:table;width:100%;margin-bottom:6px;">
    <div style="display:table-cell;width:6%;vertical-align:top;font-weight:bold;font-size:12px;color:#0f4c81;">{{ $i + 1 }}.</div>
    <div style="display:table-cell;padding-left:4px;">
        <div style="font-size:9px;font-weight:bold;">{{ $action['action'] }}</div>
        <div style="font-size:8px;color:#64748b;">Impact: {{ strtoupper($action['estimated_impact']) }} &middot; {{ $action['current_state'] }} &rarr; {{ $action['projected_state'] }}</div>
    </div>
</div>
@empty
<p style="font-size:9px;color:#94a3b8;">No improvement actions identified &mdash; candidate meets all thresholds.</p>
@endforelse

<!-- PAGE 2: ASSESSMENT NARRATIVE -->
<div class="page-break"></div>
<div class="header">
    <div class="header-left"><div class="brand">OCTOPUS AI</div><div class="brand-sub">Maritime Intelligence Platform</div></div>
    <div class="header-right"><div class="report-title">Assessment Narrative</div><div class="report-meta">{{ $candidate->first_name }} {{ $candidate->last_name }}</div></div>
</div>

<div class="section-title">Vessel Verification (AIS)</div>
@php $aisC = $candidate->contracts->filter(fn($c) => $c->latestAisVerification && $c->latestAisVerification->confidence_score !== null); @endphp
@if($aisC->isEmpty())
<p style="font-size:9px;color:#94a3b8;padding:4px 0;">No AIS verification data available.</p>
@else
<p style="font-size:9px;padding:4px 0;">{{ $aisC->count() }}/{{ $candidate->contracts->count() }} contracts verified. Average confidence: <strong>{{ round($aisC->avg(fn($c) => $c->latestAisVerification->confidence_score)*100,1) }}%</strong>. Anomalies: {{ $execSummary['scores']['verification']['anomaly_count'] ?? 0 }}.</p>
@endif

<div class="section-title">Technical Readiness</div>
@if($rankStcw)
<p style="font-size:9px;padding:4px 0;">Technical: <strong>{{ round(($rankStcw['technical_score'] ?? 0)*100,1) }}%</strong>. STCW: <strong>{{ round(($rankStcw['stcw_compliance']['compliance_ratio'] ?? 0)*100) }}%</strong>. Missing: {{ $rankStcw['stcw_compliance']['missing_count'] ?? 0 }}.</p>
@else
<p style="font-size:9px;color:#94a3b8;padding:4px 0;">Not available.</p>
@endif

<div class="section-title">Stability & Risk</div>
@if($stabilityRisk)
<p style="font-size:9px;padding:4px 0;">Tier: <strong>{{ ucfirst($stabilityRisk['risk_tier'] ?? 'N/A') }}</strong> ({{ round($stabilityRisk['risk_score'] ?? 0,3) }}). Stability: <strong>{{ round($stabilityRisk['stability_index'] ?? 0,2) }}</strong>. Contracts: {{ $stabilityRisk['contract_summary']['total_contracts'] ?? 0 }}, avg {{ $stabilityRisk['contract_summary']['avg_duration_months'] ?? 'N/A' }}m.</p>
@else
<p style="font-size:9px;color:#94a3b8;padding:4px 0;">Not available.</p>
@endif

<div class="section-title">Compliance</div>
@if($compliancePack)
<p style="font-size:9px;padding:4px 0;">Score: <strong>{{ $compliancePack['score'] ?? 0 }}/100</strong> ({{ ucfirst(str_replace('_',' ',$compliancePack['status'] ?? 'N/A')) }}). Based on {{ $compliancePack['available_sections'] ?? 0 }}/5 sections.</p>
@else
<p style="font-size:9px;color:#94a3b8;padding:4px 0;">Not available.</p>
@endif

@if($candidate->credentials && $candidate->credentials->count() > 0)
<div class="section-title">Certificate Wallet</div>
<table>
    <thead><tr><th>Certificate</th><th>Issuer</th><th>Expires</th><th>Status</th></tr></thead>
    <tbody>
        @foreach($candidate->credentials->take(10) as $cred)
        <tr>
            <td>{{ $cred->credential_type }}</td><td>{{ $cred->issuer ?? 'N/A' }}</td><td>{{ $cred->expires_at?->format('d M Y') ?? 'N/A' }}</td>
            <td>@if($cred->days_until_expiry !== null && $cred->days_until_expiry < 0)<span style="color:#dc2626;font-weight:bold;">EXPIRED</span>@elseif($cred->days_until_expiry !== null && $cred->days_until_expiry < 90)<span style="color:#b45309;">Expiring ({{ $cred->days_until_expiry }}d)</span>@else<span style="color:#15803d;">Valid</span>@endif</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="confidentiality">
    <strong>AUDIT TRAIL</strong><br>
    Trust Profile: {{ $trustProfile?->id ?? 'N/A' }} &middot; Computed: {{ $trustProfile?->computed_at?->format('d M Y H:i') ?? 'N/A' }} UTC<br>
    @if($execSummary['calibration_context']['fleet_type'] ?? null)
    Fleet: {{ $execSummary['calibration_context']['fleet_type'] }} &middot; Review Threshold: {{ $execSummary['calibration_context']['review_threshold'] }}
    @endif
    <br>
    <strong>DISCLAIMER:</strong> Generated automatically by Octopus AI. Independent verification recommended.
    <br>Octopus AI &copy; {{ date('Y') }}. CONFIDENTIAL. Report ID: {{ strtoupper(substr(md5($candidate->id . now()->toDateString()), 0, 12)) }}
</div>

</body>
</html>
