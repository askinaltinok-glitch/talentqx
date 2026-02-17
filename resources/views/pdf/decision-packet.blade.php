<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Decision Packet - {{ $interview->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1a1a1a;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .header .subtitle {
            color: #666;
            font-size: 10px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background: #f3f4f6;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 10px;
            border-left: 3px solid #2563eb;
        }
        .kpi-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .kpi-row {
            display: table-row;
        }
        .kpi-box {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        .kpi-label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .kpi-value {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
        }
        .kpi-value.hire { color: #16a34a; }
        .kpi-value.hold { color: #d97706; }
        .kpi-value.reject { color: #dc2626; }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th,
        table.data-table td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        table.data-table th {
            background: #f9fafb;
            font-weight: bold;
        }
        .risk-flag {
            display: inline-block;
            background: #fef2f2;
            color: #dc2626;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            margin: 2px;
        }
        .metadata {
            font-size: 9px;
            color: #6b7280;
        }
        .calibration-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            padding: 10px;
            margin-bottom: 15px;
        }
        .calibration-row {
            display: table;
            width: 100%;
        }
        .calibration-cell {
            display: table-cell;
            width: 20%;
            text-align: center;
            padding: 5px;
        }
        .calibration-label {
            font-size: 8px;
            color: #0369a1;
        }
        .calibration-value {
            font-size: 14px;
            font-weight: bold;
            color: #0c4a6e;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            font-size: 8px;
            color: #9ca3af;
        }
        .footer-row {
            display: table;
            width: 100%;
        }
        .footer-cell {
            display: table-cell;
        }
        .footer-cell.right {
            text-align: right;
        }
        .answer-box {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            padding: 8px;
            margin-bottom: 10px;
        }
        .answer-header {
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }
        .answer-text {
            color: #4b5563;
            font-style: italic;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Interview Decision Packet</h1>
    <div class="subtitle">
        TalentQX AI-Powered Interview Assessment | Confidential Document
    </div>
</div>

<!-- Summary Section -->
<div class="section">
    <div class="section-title">Decision Summary</div>
    <div class="kpi-grid">
        <div class="kpi-row">
            <div class="kpi-box">
                <div class="kpi-label">Final Score</div>
                <div class="kpi-value">{{ $interview->final_score ?? '-' }}</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">Decision</div>
                <div class="kpi-value {{ strtolower($interview->decision ?? '') }}">
                    {{ $interview->decision ?? 'Pending' }}
                </div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">Calibrated Score</div>
                <div class="kpi-value">{{ $interview->calibrated_score ?? '-' }}</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">Risk Flags</div>
                <div class="kpi-value">{{ is_array($interview->risk_flags) ? count($interview->risk_flags) : 0 }}</div>
            </div>
        </div>
    </div>
    @if($interview->decision_reason)
    <p><strong>Reason:</strong> {{ $interview->decision_reason }}</p>
    @endif
</div>

<!-- Interview Details -->
<div class="section">
    <div class="section-title">Interview Details</div>
    <table class="data-table">
        <tr>
            <th width="25%">Interview ID</th>
            <td>{{ $interview->id }}</td>
            <th width="25%">Status</th>
            <td>{{ ucfirst($interview->status) }}</td>
        </tr>
        <tr>
            <th>Position Code</th>
            <td>{{ $interview->position_code }}</td>
            <th>Template Position</th>
            <td>{{ $interview->template_position_code }}</td>
        </tr>
        <tr>
            <th>Version</th>
            <td>{{ $interview->version }}</td>
            <th>Language</th>
            <td>{{ strtoupper($interview->language) }}</td>
        </tr>
        <tr>
            <th>Industry</th>
            <td>{{ $interview->industry_code ?? 'General' }}</td>
            <th>Policy Code</th>
            <td>{{ $interview->policy_code ?? '-' }}</td>
        </tr>
        <tr>
            <th>Created At</th>
            <td>{{ $interview->created_at->format('Y-m-d H:i:s') }} UTC</td>
            <th>Completed At</th>
            <td>{{ $interview->completed_at?->format('Y-m-d H:i:s') ?? '-' }} UTC</td>
        </tr>
    </table>
</div>

<!-- Calibration Details -->
<div class="section">
    <div class="section-title">Scoring & Calibration</div>
    <div class="calibration-box">
        <div class="calibration-row">
            <div class="calibration-cell">
                <div class="calibration-label">Raw Score</div>
                <div class="calibration-value">{{ $interview->raw_final_score ?? '-' }}</div>
            </div>
            <div class="calibration-cell">
                <div class="calibration-label">Position Mean</div>
                <div class="calibration-value">{{ number_format($interview->position_mean_score ?? 0, 1) }}</div>
            </div>
            <div class="calibration-cell">
                <div class="calibration-label">Std Dev</div>
                <div class="calibration-value">{{ number_format($interview->position_std_dev_score ?? 0, 1) }}</div>
            </div>
            <div class="calibration-cell">
                <div class="calibration-label">Z-Score</div>
                <div class="calibration-value">{{ $interview->z_score !== null ? number_format($interview->z_score, 2) : '-' }}</div>
            </div>
            <div class="calibration-cell">
                <div class="calibration-label">Calibrated</div>
                <div class="calibration-value">{{ $interview->calibrated_score ?? '-' }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Competency Scores -->
@if($interview->competency_scores && count($interview->competency_scores) > 0)
<div class="section">
    <div class="section-title">Competency Scores</div>
    <table class="data-table">
        <tr>
            <th>Competency</th>
            <th>Score</th>
        </tr>
        @foreach($interview->competency_scores as $competency => $score)
        <tr>
            <td>{{ ucwords(str_replace('_', ' ', $competency)) }}</td>
            <td>{{ $score }}/100</td>
        </tr>
        @endforeach
    </table>
</div>
@endif

<!-- Risk Flags -->
@if($interview->risk_flags && count($interview->risk_flags) > 0)
<div class="section">
    <div class="section-title">Risk Flags</div>
    <table class="data-table">
        <tr>
            <th>Code</th>
            <th>Severity</th>
            <th>Evidence</th>
        </tr>
        @foreach($interview->risk_flags as $flag)
        <tr>
            <td>{{ $flag['code'] ?? 'Unknown' }}</td>
            <td>{{ $flag['severity'] ?? '-' }}</td>
            <td>{{ $flag['evidence'] ?? '-' }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endif

<div class="page-break"></div>

<!-- Answers Section -->
@if($interview->answers && $interview->answers->count() > 0)
<div class="section">
    <div class="section-title">Candidate Responses</div>
    @foreach($interview->answers as $answer)
    <div class="answer-box">
        <div class="answer-header">
            Slot {{ $answer->slot }} - {{ ucwords(str_replace('_', ' ', $answer->competency)) }}
        </div>
        <div class="answer-text">
            {{ Str::limit($answer->answer_text, 500) }}
        </div>
    </div>
    @endforeach
</div>
@endif

<!-- Outcome (if available) -->
@if($outcome)
<div class="section">
    <div class="section-title">Ground Truth Outcome</div>
    <table class="data-table">
        <tr>
            <th>Hired</th>
            <td>{{ $outcome->hired ? 'Yes' : 'No' }}</td>
            <th>Started Work</th>
            <td>{{ $outcome->started ? 'Yes' : 'No' }}</td>
        </tr>
        <tr>
            <th>Retained 90 Days</th>
            <td>{{ $outcome->retained_90d ? 'Yes' : ($outcome->retained_90d === false ? 'No' : '-') }}</td>
            <th>Performance Score</th>
            <td>{{ $outcome->performance_score ?? '-' }}</td>
        </tr>
        <tr>
            <th>Outcome Score</th>
            <td colspan="3">{{ $outcome->outcome_score ?? '-' }}/100</td>
        </tr>
    </table>
</div>
@endif

<!-- Integrity Section -->
<div class="section">
    <div class="section-title">Document Integrity</div>
    <table class="data-table">
        <tr>
            <th>Template SHA-256</th>
            <td colspan="3" style="font-family: monospace; font-size: 9px;">{{ $interview->template_json_sha256 ?? '-' }}</td>
        </tr>
        <tr>
            <th>Packet Checksum</th>
            <td colspan="3" style="font-family: monospace; font-size: 9px;">{{ $checksum }}</td>
        </tr>
        <tr>
            <th>Generated At</th>
            <td>{{ $generatedAt }}</td>
            <th>Generated By</th>
            <td>{{ $generatedBy }}</td>
        </tr>
    </table>
</div>

<div class="footer">
    <div class="footer-row">
        <div class="footer-cell">
            TalentQX Decision Packet v1.0 | Interview ID: {{ $interview->id }}
        </div>
        <div class="footer-cell right">
            Generated: {{ $generatedAt }} | Confidential
        </div>
    </div>
</div>

</body>
</html>
