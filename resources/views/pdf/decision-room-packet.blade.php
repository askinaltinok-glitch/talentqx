<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; margin: 30px; }
        .header { border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; color: #2563eb; margin: 0 0 4px; }
        .header p { margin: 0; color: #64748b; font-size: 10px; }
        h2 { font-size: 14px; color: #1e293b; margin: 18px 0 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        .info-grid { display: table; width: 100%; margin-bottom: 12px; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 35%; padding: 3px 8px 3px 0; color: #64748b; font-size: 10px; }
        .info-value { display: table-cell; padding: 3px 0; font-weight: 600; }
        .score-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 12px; text-align: center; margin-bottom: 12px; }
        .score-box .score { font-size: 28px; font-weight: 700; color: #16a34a; }
        .score-box .label { font-size: 11px; color: #4ade80; }
        .pillar-grid { display: table; width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .pillar-row { display: table-row; }
        .pillar-cell { display: table-cell; width: 25%; padding: 8px; border: 1px solid #e2e8f0; text-align: center; vertical-align: top; }
        .pillar-name { font-size: 9px; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
        .pillar-score { font-size: 16px; font-weight: 700; }
        .green { color: #16a34a; }
        .amber { color: #d97706; }
        .red { color: #dc2626; }
        .evidence-item { padding: 3px 0; border-bottom: 1px solid #f1f5f9; }
        .evidence-label { font-weight: 600; font-size: 10px; }
        .evidence-detail { color: #64748b; font-size: 10px; }
        .risk-item { padding: 4px 8px; margin: 2px 0; background: #fef2f2; border-left: 3px solid #ef4444; font-size: 10px; }
        .sim-grid { display: table; width: 100%; margin-bottom: 12px; }
        .sim-cell { display: table-cell; width: 25%; padding: 8px; border: 1px solid #e2e8f0; text-align: center; }
        .sim-value { font-size: 14px; font-weight: 700; }
        .sim-label { font-size: 9px; color: #64748b; }
        .footer { margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 8px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Decision Room Summary</h1>
        <p>Generated: {{ $generatedAt }} | Vessel: {{ $vessel->name }} ({{ $vessel->imo ?? 'No IMO' }})</p>
    </div>

    <h2>Candidate Information</h2>
    <div class="info-grid">
        <div class="info-row">
            <span class="info-label">Name</span>
            <span class="info-value">{{ $candidate->first_name }} {{ $candidate->last_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Rank</span>
            <span class="info-value">{{ ucfirst(str_replace('_', ' ', $candidate->rank ?? 'N/A')) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Nationality</span>
            <span class="info-value">{{ $candidate->nationality ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Vessel</span>
            <span class="info-value">{{ $vessel->name }} ({{ $vessel->vessel_type ?? 'N/A' }})</span>
        </div>
    </div>

    @if($compatibility)
        <h2>Compatibility Analysis</h2>
        <div class="score-box">
            <div class="score">{{ $compatibility['compatibility_score'] }}</div>
            <div class="label">{{ ucfirst(str_replace('_', ' ', $compatibility['label'])) }}</div>
        </div>

        <div class="pillar-grid">
            <div class="pillar-row">
                @foreach(['captain_fit' => 'Captain Fit', 'team_balance' => 'Team Balance', 'vessel_fit' => 'Vessel Fit', 'operational_risk' => 'Operational Risk'] as $key => $name)
                    @php
                        $score = $compatibility['pillars'][$key]['score'] ?? 0;
                        $colorClass = $score >= 65 ? 'green' : ($score >= 45 ? 'amber' : 'red');
                    @endphp
                    <div class="pillar-cell">
                        <div class="pillar-name">{{ $name }}</div>
                        <div class="pillar-score {{ $colorClass }}">{{ $score }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        @if(!empty($compatibility['evidence']))
            <h2>Key Evidence</h2>
            @foreach(array_slice($compatibility['evidence'], 0, 8) as $ev)
                <div class="evidence-item">
                    <span class="evidence-label">{{ $ev['label'] ?? '' }}</span>
                    @if(!empty($ev['detail']))
                        <span class="evidence-detail"> &mdash; {{ $ev['detail'] }}</span>
                    @endif
                </div>
            @endforeach
        @endif

        @if(!empty($compatibility['pillars']['operational_risk']['risk_factors']))
            <h2>Risk Factors</h2>
            @foreach($compatibility['pillars']['operational_risk']['risk_factors'] as $rf)
                <div class="risk-item">{{ $rf }}</div>
            @endforeach
        @endif
    @else
        <h2>Compatibility Analysis</h2>
        <p style="color: #94a3b8;">Compatibility analysis not available. The V2 synergy engine may be disabled or no IMO bridge exists for this vessel.</p>
    @endif

    @if($simulation)
        <h2>Team Simulation Results</h2>
        <div class="sim-grid">
            <div class="pillar-row">
                <div class="sim-cell">
                    <div class="sim-label">Team Balance</div>
                    <div class="sim-value">{{ $simulation['team_balance_index'] ?? '--' }}</div>
                </div>
                <div class="sim-cell">
                    <div class="sim-label">Conflict Probability</div>
                    <div class="sim-value">{{ isset($simulation['conflict_probability']) ? $simulation['conflict_probability'] . '%' : '--' }}</div>
                </div>
                <div class="sim-cell">
                    <div class="sim-label">Stability Projection</div>
                    <div class="sim-value">{{ $simulation['stability_projection'] ?? '--' }}</div>
                </div>
                <div class="sim-cell">
                    <div class="sim-label">Crew Size After</div>
                    <div class="sim-value">{{ count($simulation['personality_distribution'] ?? []) > 0 ? array_sum($simulation['personality_distribution']) : '--' }}</div>
                </div>
            </div>
        </div>
    @endif

    <div class="footer">
        TalentQX Decision Room &middot; Confidential &middot; {{ $generatedAt }}
    </div>
</body>
</html>
