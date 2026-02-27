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
    <div class="footer-left">Octopus AI &mdash; Decision Packet v2</div>
    <div class="footer-center">CONFIDENTIAL</div>
    <div class="footer-right">Generated {{ now()->format('d M Y H:i') }} UTC &middot; System: Octopus AI</div>
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
        <tr><td style="font-weight:bold;">Nationality</td><td>{{ strtoupper($candidate->nationality ?? 'N/A') }}</td><td style="font-weight:bold;">License Country</td><td>{{ strtoupper($candidate->license_country ?? 'N/A') }}</td></tr>
        <tr><td style="font-weight:bold;">Flag Endors.</td><td>{{ $candidate->flag_endorsement ?? 'N/A' }}</td><td style="font-weight:bold;">Passport Exp.</td><td>@if($candidate->passport_expiry){{ \Carbon\Carbon::parse($candidate->passport_expiry)->format('d M Y') }}@else N/A @endif</td></tr>
        <tr><td style="font-weight:bold;">Status</td><td>{{ ucfirst(str_replace('_',' ',$candidate->status ?? 'N/A')) }}</td><td style="font-weight:bold;">Seafarer</td><td>{{ $candidate->seafarer ? 'Yes' : 'No' }}</td></tr>
        <tr><td style="font-weight:bold;">Source</td><td>{{ $candidate->source_label ?: ($candidate->source_channel ?? 'N/A') }}</td><td style="font-weight:bold;">Source Type</td><td>@if($candidate->source_type === 'company_invite')<span style="color:#2563eb;font-weight:bold;">Company Invite</span>@else{{ ucfirst(str_replace('_',' ',$candidate->source_type ?? 'N/A')) }}@endif</td></tr>
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

{{-- Assessment Evidence: question_code + competency + evidence extract --}}
@if(isset($interview) && $interview->answers && $interview->answers->count() > 0)
<div class="section-title">Assessment Evidence</div>
<table>
    <thead><tr><th style="width:8%;">Code</th><th style="width:22%;">Competency</th><th style="width:10%;text-align:center;">Score</th><th>Evidence Extract</th></tr></thead>
    <tbody>
        @foreach($interview->answers->sortBy('slot') as $answer)
        @php
            $evidenceWords = array_slice(explode(' ', trim($answer->answer_text)), 0, 25);
            $evidence = implode(' ', $evidenceWords);
            if (count(explode(' ', trim($answer->answer_text))) > 25) $evidence .= '...';
            $compScores = $interview->competency_scores ?? [];
            $aScore = $compScores[$answer->competency] ?? $answer->score ?? null;
        @endphp
        <tr>
            <td style="font-weight:bold;color:#0f4c81;">Q{{ $answer->slot }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $answer->competency)) }}</td>
            <td style="text-align:center;font-weight:bold;color:{{ ($aScore ?? 0) >= 85 ? '#15803d' : (($aScore ?? 0) >= 70 ? '#b45309' : '#dc2626') }};">{{ $aScore ?? '—' }}</td>
            <td style="font-size:8px;color:#475569;">{{ $evidence }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<p style="font-size:7px;color:#94a3b8;margin-top:2px;">Evidence: first 25 words of candidate response. Full text redacted. Question text not shown for IP protection.</p>
@endif

@if(!empty($certificateRisks) && count($certificateRisks) > 0)
<div class="section-title">Certificate Lifecycle Status</div>
<table>
    <thead><tr><th>Certificate</th><th>Code</th><th>Issued</th><th>Expires</th><th>Source</th><th>Risk</th><th>Days</th></tr></thead>
    <tbody>
        @foreach($certificateRisks as $cert)
        @php
            $sourceLabel = match($cert['expiry_source'] ?? 'unknown') {
                'uploaded' => 'Uploaded',
                'estimated_company' => 'Est. (company)',
                'estimated_country' => 'Est. (country)',
                'estimated_default' => 'Est. (default)',
                default => '—',
            };
        @endphp
        <tr>
            <td style="font-weight:bold;">{{ strtoupper($cert['certificate_type']) }}</td>
            <td>{{ $cert['certificate_code'] ?? '—' }}</td>
            <td>{{ $cert['issued_at'] ?? '—' }}</td>
            <td>{{ $cert['expires_at'] ?? '—' }}</td>
            <td style="font-size:8px;color:{{ str_starts_with($cert['expiry_source'] ?? '', 'estimated') ? '#b45309' : '#64748b' }};">{{ $sourceLabel }}</td>
            <td><span style="font-weight:bold;color:{{ $cert['risk_color'] === 'green' ? '#15803d' : ($cert['risk_color'] === 'yellow' ? '#b45309' : ($cert['risk_color'] === 'red' ? '#dc2626' : '#64748b')) }};">{{ strtoupper($cert['risk_level']) }}</span></td>
            <td style="text-align:center;">{{ $cert['days_remaining'] !== null ? $cert['days_remaining'] : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if(!empty($vesselFitEvidence))
@php
    $renderedFit = array_filter($vesselFitEvidence, fn($e) => $e['guards']['rendered'] ?? false);
    usort($renderedFit, fn($a, $b) => $b['fit_pct'] <=> $a['fit_pct']);
@endphp
@if(count($renderedFit) > 0)
<div class="section-title">Vessel Type Fit</div>
<table>
    <thead><tr><th>Vessel Type</th><th>Fit %</th><th>Confidence</th><th>Source</th><th>Evidence</th></tr></thead>
    <tbody>
        @foreach($renderedFit as $entry)
        @php
            $fitColor = $entry['fit_pct'] >= 70 ? '#15803d' : ($entry['fit_pct'] >= 50 ? '#b45309' : '#dc2626');
            $confColor = match($entry['confidence']) {
                'high' => '#15803d',
                'medium' => '#b45309',
                default => '#64748b',
            };
            $sourceLabel = match($entry['primary_source']) {
                'contract_history' => 'Contract',
                'certificates' => 'Certificate',
                'experience_form' => 'Form',
                'interview_keywords' => 'Behavioral',
                'demo_seed' => 'Demo',
                default => '—',
            };
            $evidenceText = '';
            foreach (array_slice($entry['evidence'] ?? [], 0, 2) as $ev) {
                $evidenceText .= ($evidenceText ? '; ' : '') . mb_substr($ev['label'] . ': ' . $ev['detail'], 0, 80);
            }
        @endphp
        <tr>
            <td style="font-weight:bold;">{{ $entry['vessel_type'] }}</td>
            <td style="font-weight:bold;color:{{ $fitColor }};text-align:center;">{{ $entry['fit_pct'] }}%</td>
            <td style="color:{{ $confColor }};text-align:center;">{{ strtoupper($entry['confidence']) }}</td>
            <td style="font-size:8px;">{{ $sourceLabel }}</td>
            <td style="font-size:7px;color:#64748b;">{{ $evidenceText ?: '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
@endif

@if(!empty($compatibilityData))
<div class="section-title">Crew Compatibility Analysis</div>
<div style="display:flex; gap:10px; margin-bottom:10px;">
    <div class="tile" style="flex:1; text-align:center; padding:8px; border:1px solid #ddd; border-radius:6px;">
        <div style="font-size:22px; font-weight:bold; color:{{ $compatibilityData['compatibility_score'] >= 65 ? '#16a34a' : ($compatibilityData['compatibility_score'] >= 45 ? '#d97706' : '#dc2626') }};">
            {{ $compatibilityData['compatibility_score'] }}
        </div>
        <div style="font-size:10px; color:#666;">Compatibility Score</div>
    </div>
    <div class="tile" style="flex:1; text-align:center; padding:8px; border:1px solid #ddd; border-radius:6px;">
        <div style="font-size:16px; font-weight:bold;">{{ $compatibilityData['pillars']['captain_fit']['score'] }}</div>
        <div style="font-size:10px; color:#666;">Captain Fit</div>
        <div style="font-size:9px; color:#888;">Style: {{ ucfirst($compatibilityData['pillars']['captain_fit']['captain_style'] ?? 'unknown') }}</div>
    </div>
    <div class="tile" style="flex:1; text-align:center; padding:8px; border:1px solid #ddd; border-radius:6px;">
        <div style="font-size:16px; font-weight:bold;">{{ $compatibilityData['pillars']['team_balance']['score'] }}</div>
        <div style="font-size:10px; color:#666;">Team Balance</div>
    </div>
    <div class="tile" style="flex:1; text-align:center; padding:8px; border:1px solid #ddd; border-radius:6px;">
        <div style="font-size:16px; font-weight:bold;">{{ $compatibilityData['pillars']['operational_risk']['score'] }}</div>
        <div style="font-size:10px; color:#666;">Op. Risk</div>
        <div style="font-size:9px; color:#888;">{{ ucfirst($compatibilityData['pillars']['operational_risk']['risk_level'] ?? 'unknown') }}</div>
    </div>
</div>
@if(!empty($compatibilityData['evidence']))
<div style="margin-top:6px;">
    @foreach(array_slice($compatibilityData['evidence'], 0, 6) as $ev)
    <div style="font-size:10px; color:#555; padding:2px 0;">• <strong>{{ $ev['label'] }}</strong>{{ !empty($ev['detail']) ? ' — '.$ev['detail'] : '' }}</div>
    @endforeach
</div>
@endif
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

{{-- PAGE 3: DECISION PACKET V2 — English Gate, Question Blocks, Admin Decision, Marketplace, Consent --}}
@if(!empty($englishGateData) || !empty($questionBlockSummary) || !empty($adminDecisionData['phase_reviews']) || !empty($adminDecisionData['override']) || !empty($marketplaceData) || !empty($consentSnapshot))
<div class="page-break"></div>
<div class="header">
    <div class="header-left"><div class="brand">OCTOPUS AI</div><div class="brand-sub">Maritime Intelligence Platform</div></div>
    <div class="header-right"><div class="report-title">Extended Assessment</div><div class="report-meta">{{ $candidate->first_name }} {{ $candidate->last_name }}<br>Generated {{ now()->format('d M Y') }}</div></div>
</div>

{{-- SECTION C: English Gate --}}
@if(!empty($englishGateData))
<div class="section-title">C. English Gate Assessment</div>
<div class="tile-row">
    <div class="tile"><div class="tile-inner">
        <div class="tile-label">CEFR Level</div>
        <div class="tile-value">{{ strtoupper($englishGateData['cefr_level'] ?? 'N/A') }}</div>
    </div></div>
    <div class="tile"><div class="tile-inner">
        <div class="tile-label">Confidence</div>
        <div class="tile-value">{{ $englishGateData['confidence'] !== null ? round($englishGateData['confidence'] * 100) . '%' : 'N/A' }}</div>
    </div></div>
    <div class="tile"><div class="tile-inner">
        <div class="tile-label">Transcript Length</div>
        <div class="tile-value">{{ number_format($englishGateData['transcript_length'] ?? 0) }}</div>
        <div class="tile-sub">characters ({{ $englishGateData['voice_count'] ?? 0 }} recordings)</div>
    </div></div>
    <div class="tile"><div class="tile-inner">
        <div class="tile-label">Voice Duration</div>
        <div class="tile-value">{{ $englishGateData['voice_duration_ms'] ? round($englishGateData['voice_duration_ms'] / 1000, 1) . 's' : 'N/A' }}</div>
        <div class="tile-sub">{{ $englishGateData['provider'] ?? '' }} {{ $englishGateData['model'] ?? '' }}</div>
    </div></div>
</div>
<table>
    <thead><tr><th>Metric</th><th>Value</th></tr></thead>
    <tbody>
        <tr><td style="font-weight:bold;">Declared Level</td><td>{{ strtoupper($englishGateData['declared_level'] ?? 'N/A') }}</td></tr>
        <tr><td style="font-weight:bold;">Estimated Level</td><td>{{ strtoupper($englishGateData['estimated_level'] ?? 'N/A') }}</td></tr>
        <tr><td style="font-weight:bold;">Locked Level</td><td>@if($englishGateData['locked_level'])<span style="font-weight:bold;color:#15803d;">{{ strtoupper($englishGateData['locked_level']) }}</span>@else<span style="color:#94a3b8;">Not locked</span>@endif</td></tr>
    </tbody>
</table>
@endif

{{-- SECTION D: Question Block Summary --}}
@if(!empty($questionBlockSummary))
<div class="section-title">D. Question Block Summary</div>
<table>
    <thead><tr><th>Block</th><th>Slot Range</th><th>Questions</th></tr></thead>
    <tbody>
        <tr><td style="font-weight:bold;">CORE</td><td>1–12</td><td style="text-align:center;font-weight:bold;">{{ $questionBlockSummary['core'] }}</td></tr>
        <tr><td style="font-weight:bold;">ROLE</td><td>13–18</td><td style="text-align:center;font-weight:bold;">{{ $questionBlockSummary['role'] }}</td></tr>
        <tr><td style="font-weight:bold;">SAFETY</td><td>19–22</td><td style="text-align:center;font-weight:bold;">{{ $questionBlockSummary['safety'] }}</td></tr>
        <tr><td style="font-weight:bold;">ENGLISH</td><td>23–25</td><td style="text-align:center;font-weight:bold;">{{ $questionBlockSummary['english'] }}</td></tr>
        <tr style="background:#f1f5f9;"><td style="font-weight:bold;">TOTAL</td><td></td><td style="text-align:center;font-weight:bold;">{{ $questionBlockSummary['total'] }}</td></tr>
    </tbody>
</table>
@if($questionBlockSummary['workflow'])
<p style="font-size:8px;color:#64748b;margin-top:2px;">Workflow: {{ $questionBlockSummary['workflow'] }}</p>
@endif
@endif

{{-- SECTION E: Admin Decision / Override --}}
@if(!empty($adminDecisionData['override']) || (!empty($adminDecisionData['phase_reviews']) && count($adminDecisionData['phase_reviews']) > 0))
<div class="section-title">E. Admin Decision & Override</div>
@if(!empty($adminDecisionData['override']))
@php
    $ovr = $adminDecisionData['override'];
    $ovrBadge = match($ovr['decision']) { 'approve'=>'badge-approve','reject'=>'badge-reject',default=>'badge-review' };
@endphp
<div style="border:1px solid {{ $ovr['active'] ? '#bfdbfe' : '#e2e8f0' }};background:{{ $ovr['active'] ? '#eff6ff' : '#f8fafc' }};border-radius:4px;padding:8px 12px;margin-bottom:8px;">
    <div style="display:table;width:100%;">
        <div style="display:table-cell;width:30%;"><span class="badge {{ $ovrBadge }}">{{ strtoupper($ovr['decision']) }}</span> @if($ovr['active'])<span style="font-size:7px;color:#2563eb;font-weight:bold;margin-left:4px;">ACTIVE</span>@else<span style="font-size:7px;color:#94a3b8;margin-left:4px;">EXPIRED</span>@endif</div>
        <div style="display:table-cell;font-size:8px;color:#64748b;">Created: {{ $ovr['created_at'] ?? 'N/A' }} @if($ovr['expires_at'])&middot; Expires: {{ $ovr['expires_at'] }}@endif</div>
    </div>
    <div style="font-size:9px;color:#475569;margin-top:4px;">{{ $ovr['reason'] }}</div>
</div>
@endif

@if(!empty($adminDecisionData['phase_reviews']) && count($adminDecisionData['phase_reviews']) > 0)
<table>
    <thead><tr><th>Phase</th><th>Status</th><th>Notes</th><th>Reviewed At</th></tr></thead>
    <tbody>
        @foreach($adminDecisionData['phase_reviews'] as $pr)
        <tr>
            <td style="font-weight:bold;">{{ strtoupper(str_replace('_', ' ', $pr['phase_key'])) }}</td>
            <td><span style="font-weight:bold;color:{{ $pr['status'] === 'approved' ? '#15803d' : ($pr['status'] === 'rejected' ? '#dc2626' : '#b45309') }};">{{ strtoupper($pr['status']) }}</span></td>
            <td style="font-size:8px;color:#475569;">{{ \Illuminate\Support\Str::limit($pr['review_notes'] ?? '', 80) }}</td>
            <td style="font-size:8px;">{{ $pr['reviewed_at'] ?? 'N/A' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
@endif

{{-- SECTION F: Marketplace --}}
@if(!empty($marketplaceData))
<div class="section-title">F. Marketplace Interest</div>
<table>
    <thead><tr><th>Requesting Company</th><th>Status</th><th>Requested</th><th>Responded</th></tr></thead>
    <tbody>
        @foreach($marketplaceData as $mar)
        <tr>
            <td style="font-weight:bold;">{{ $mar['requesting_company'] }}</td>
            <td><span style="font-weight:bold;color:{{ $mar['status'] === 'approved' ? '#15803d' : ($mar['status'] === 'rejected' ? '#dc2626' : ($mar['status'] === 'pending' ? '#b45309' : '#64748b')) }};">{{ strtoupper($mar['status']) }}</span></td>
            <td>{{ $mar['requested_at'] ?? 'N/A' }}</td>
            <td>{{ $mar['responded_at'] ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- SECTION G: Consent Snapshot --}}
@if(!empty($consentSnapshot))
<div class="section-title">G. Consent Record</div>
<table>
    <thead><tr><th>Type</th><th>Regulation</th><th>Version</th><th>Granted</th><th>Valid</th><th>Date</th></tr></thead>
    <tbody>
        @foreach($consentSnapshot as $consent)
        <tr>
            <td style="font-weight:bold;">{{ strtoupper(str_replace('_', ' ', $consent['type'])) }}</td>
            <td>{{ $consent['regulation'] ?? '—' }}</td>
            <td>{{ $consent['version'] ?? '—' }}</td>
            <td style="color:{{ $consent['granted'] ? '#15803d' : '#dc2626' }};font-weight:bold;">{{ $consent['granted'] ? 'YES' : 'NO' }}</td>
            <td style="color:{{ $consent['valid'] ? '#15803d' : '#dc2626' }};font-weight:bold;">{{ $consent['valid'] ? 'YES' : 'NO' }}</td>
            <td style="font-size:8px;">{{ $consent['consented_at'] ?? 'N/A' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<p style="font-size:7px;color:#94a3b8;margin-top:2px;">IP addresses redacted from PDF output for privacy.</p>
@endif

@endif
{{-- END PAGE 3 --}}

<div class="confidentiality">
    <strong>AUDIT TRAIL</strong><br>
    Decision Packet v2 &middot; Generated: {{ now()->format('d M Y H:i:s') }} UTC &middot; System: Octopus AI<br>
    Trust Profile: {{ $trustProfile?->id ?? 'N/A' }} &middot; Computed: {{ $trustProfile?->computed_at?->format('d M Y H:i') ?? 'N/A' }} UTC<br>
    @if($execSummary['calibration_context']['fleet_type'] ?? null)
    Fleet: {{ $execSummary['calibration_context']['fleet_type'] }} &middot; Review Threshold: {{ $execSummary['calibration_context']['review_threshold'] }}
    @endif
    <br>
    <strong>DISCLAIMER:</strong> This document is a point-in-time snapshot. Data reflects the state at generation time. Subsequent changes to candidate data are not retroactively applied. Generated automatically by Octopus AI. Independent verification recommended.
    <br>Octopus AI &copy; {{ date('Y') }}. CONFIDENTIAL. Report ID: {{ strtoupper(substr(md5($candidate->id . now()->toDateString()), 0, 12)) }}
</div>

</body>
</html>
