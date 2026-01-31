<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $locale === 'tr' ? 'Aday Değerlendirme Raporu' : 'Candidate Assessment Report' }} - {{ substr($reportId, 0, 8) }}</title>
    <style>
        /* TalentQX Corporate PDF Standard v1.0 */
        :root {
            --primary: {{ $branding['primary_color'] ?? '#1E3A5F' }};
            --primary-light: {{ $branding['secondary_color'] ?? '#2E5A8F' }};
            --accent: #0EA5E9;
            --success: #059669;
            --warning: #D97706;
            --danger: #DC2626;
            --text-dark: #111827;
            --text-body: #374151;
            --text-muted: #6B7280;
            --text-light: #9CA3AF;
            --border: #E5E7EB;
            --bg-light: #F8FAFC;
            --bg-section: #F1F5F9;
            --white: #FFFFFF;
        }

        @page {
            size: A4;
            margin: 18mm 15mm 20mm 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.6;
            color: var(--text-body);
            background: var(--white);
        }

        /* Page Layout */
        .page {
            position: relative;
            min-height: 100vh;
            padding-bottom: 60px;
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        /* Confidentiality Banner */
        .confidential-banner {
            background: var(--primary);
            color: var(--white);
            text-align: center;
            padding: 6px 0;
            font-size: 8pt;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-bottom: 12px;
            border-bottom: 3px solid var(--primary);
            margin-bottom: 24px;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-brand img {
            max-height: 40px;
            max-width: 120px;
        }

        .header-brand-text {
            font-size: 14pt;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .header-meta {
            text-align: right;
            font-size: 9pt;
            color: var(--text-muted);
        }

        .header-meta strong {
            color: var(--text-dark);
            display: block;
            font-size: 11pt;
        }

        /* Cover Page */
        .cover {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 85vh;
            text-align: center;
            padding: 40px;
        }

        .cover-logo {
            max-height: 60px;
            margin-bottom: 40px;
        }

        .cover-title {
            font-size: 32pt;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
            letter-spacing: -1px;
        }

        .cover-subtitle {
            font-size: 14pt;
            color: var(--text-muted);
            font-weight: 400;
            margin-bottom: 50px;
        }

        .cover-card {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 32px 48px;
            margin-bottom: 40px;
            min-width: 380px;
        }

        .cover-card table {
            width: 100%;
            border-collapse: collapse;
        }

        .cover-card td {
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }

        .cover-card tr:last-child td {
            border-bottom: none;
        }

        .cover-card td:first-child {
            color: var(--text-muted);
            font-size: 10pt;
            width: 140px;
        }

        .cover-card td:last-child {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 11pt;
        }

        /* Decision Badge */
        .decision-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 18pt;
            font-weight: 700;
            color: var(--white);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .decision-badge.hire {
            background: linear-gradient(135deg, var(--success), #047857);
        }

        .decision-badge.hold {
            background: linear-gradient(135deg, var(--warning), #B45309);
        }

        .decision-badge.reject {
            background: linear-gradient(135deg, var(--danger), #B91C1C);
        }

        .decision-badge-icon {
            font-size: 24pt;
        }

        /* Score Display */
        .score-display {
            margin-top: 24px;
            padding: 16px 32px;
            background: var(--primary);
            color: var(--white);
            border-radius: 8px;
            display: inline-block;
        }

        .score-display-value {
            font-size: 36pt;
            font-weight: 700;
            line-height: 1;
        }

        .score-display-label {
            font-size: 10pt;
            opacity: 0.8;
            margin-top: 4px;
        }

        /* Section Styling */
        .section-title {
            font-size: 13pt;
            font-weight: 700;
            color: var(--primary);
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary);
            margin-bottom: 16px;
            margin-top: 28px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title:first-child {
            margin-top: 0;
        }

        .section-icon {
            width: 20px;
            height: 20px;
            background: var(--primary);
            color: var(--white);
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11pt;
            font-weight: 700;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }

        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: var(--bg-section);
            font-weight: 600;
            color: var(--text-dark);
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: var(--bg-light);
        }

        /* Score Bars */
        .score-bar-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .score-bar {
            flex: 1;
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
        }

        .score-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .score-bar-fill.excellent { background: var(--success); }
        .score-bar-fill.good { background: var(--accent); }
        .score-bar-fill.moderate { background: var(--warning); }
        .score-bar-fill.low { background: var(--danger); }

        .score-value {
            font-weight: 600;
            min-width: 45px;
            text-align: right;
            font-size: 10pt;
        }

        /* Insight Cards */
        .insight-grid {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
        }

        .insight-card {
            flex: 1;
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
        }

        .insight-card h4 {
            font-size: 11pt;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .insight-card ul {
            margin: 0;
            padding-left: 18px;
        }

        .insight-card li {
            margin-bottom: 6px;
            font-size: 10pt;
            color: var(--text-body);
        }

        .insight-card p {
            font-size: 10pt;
            color: var(--text-body);
            line-height: 1.6;
        }

        /* Summary Box */
        .summary-box {
            background: var(--bg-section);
            border-left: 4px solid var(--primary);
            padding: 16px 20px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }

        .summary-box p {
            font-size: 10pt;
            line-height: 1.7;
            color: var(--text-body);
        }

        /* Warning Box */
        .warning-box {
            background: #FEF3C7;
            border-left: 4px solid var(--warning);
            padding: 14px 18px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }

        .warning-box h4 {
            color: var(--warning);
            font-size: 10pt;
            margin-bottom: 6px;
        }

        .warning-box p {
            font-size: 10pt;
            color: #92400E;
        }

        /* Radar Chart */
        .radar-container {
            display: flex;
            justify-content: center;
            padding: 20px;
            background: var(--bg-light);
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .radar-chart {
            width: 320px;
            height: 320px;
        }

        /* Context Comparison */
        .context-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: 600;
        }

        .context-badge.excellent { background: #D1FAE5; color: #065F46; }
        .context-badge.good { background: #DBEAFE; color: #1E40AF; }
        .context-badge.moderate { background: #FEF3C7; color: #92400E; }
        .context-badge.low { background: #FEE2E2; color: #991B1B; }

        /* Risk Flags */
        .risk-item {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            margin-bottom: 10px;
            border-radius: 6px;
            border-left: 4px solid;
        }

        .risk-item.high {
            background: #FEF2F2;
            border-color: var(--danger);
        }

        .risk-item.medium {
            background: #FFFBEB;
            border-color: var(--warning);
        }

        .risk-item.low {
            background: #F0FDF4;
            border-color: var(--success);
        }

        .risk-type {
            font-weight: 700;
            font-size: 9pt;
            text-transform: uppercase;
            min-width: 60px;
        }

        .risk-item.high .risk-type { color: var(--danger); }
        .risk-item.medium .risk-type { color: var(--warning); }
        .risk-item.low .risk-type { color: var(--success); }

        /* Legal Section */
        .legal-box {
            background: var(--bg-section);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            margin-top: 24px;
        }

        .legal-box h4 {
            font-size: 10pt;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }

        .legal-box p {
            font-size: 9pt;
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 10px;
        }

        .legal-box p:last-child {
            margin-bottom: 0;
        }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px 0;
            border-top: 1px solid var(--border);
            font-size: 8pt;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-left {
            display: flex;
            gap: 20px;
        }

        .footer-confidential {
            color: var(--danger);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Print Optimization */
        @media print {
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .page { page-break-after: always; }
            .no-print { display: none; }
        }

        /* Status Indicator */
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }

        .status-dot.green { background: var(--success); }
        .status-dot.yellow { background: var(--warning); }
        .status-dot.red { background: var(--danger); }

        /* Quick Stats */
        .quick-stats {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .quick-stat {
            flex: 1;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px;
            text-align: center;
        }

        .quick-stat-value {
            font-size: 20pt;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
        }

        .quick-stat-label {
            font-size: 8pt;
            color: var(--text-muted);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<!-- PAGE 1: COVER -->
<div class="page">
    <div class="confidential-banner">
        {{ $locale === 'tr' ? 'Gizli - Sadece Yetkili Personel İçin' : 'Confidential - Authorized Personnel Only' }}
    </div>

    {{-- CO-BRAND HEADER (v1.0 Standard) --}}
    @if($branding['white_label'] ?? false)
        {{-- WHITE-LABEL MODE: Customer only --}}
        <div style="display: flex; justify-content: center; align-items: center; padding-bottom: 16px; border-bottom: 3px solid var(--primary); margin-bottom: 20px;">
            @if($branding['customer_logo_url'] ?? null)
                <img src="{{ $branding['customer_logo_url'] }}" alt="{{ $branding['customer_company_name'] ?? '' }}" style="max-height: 40px; max-width: 180px;">
            @elseif($branding['customer_company_name'] ?? null)
                <span class="header-brand-text" style="font-size: 18pt;">{{ $branding['customer_company_name'] }}</span>
            @endif
        </div>
    @else
        {{-- CO-BRAND MODE: Customer left (32px), TalentQX right (36-40px) --}}
        <div style="display: flex; justify-content: {{ ($branding['customer_logo_url'] ?? null) || ($branding['customer_company_name'] ?? null) ? 'space-between' : 'center' }}; align-items: center; padding-bottom: 16px; border-bottom: 3px solid var(--primary); margin-bottom: 20px;">
            @if($branding['customer_logo_url'] ?? null)
                {{-- Customer logo: LEFT, max 32px --}}
                <img src="{{ $branding['customer_logo_url'] }}" alt="{{ $branding['customer_company_name'] ?? '' }}" style="max-height: 32px; max-width: 120px;">
            @elseif($branding['customer_company_name'] ?? null)
                {{-- Customer name if no logo --}}
                <span style="font-size: 12pt; font-weight: 600; color: var(--text-body);">{{ $branding['customer_company_name'] }}</span>
            @endif
            {{-- TalentQX: RIGHT (or CENTER if no customer), 36-40px - ALWAYS DOMINANT --}}
            <span class="header-brand-text" style="font-size: {{ ($branding['customer_logo_url'] ?? null) || ($branding['customer_company_name'] ?? null) ? '16pt' : '20pt' }};">TalentQX</span>
        </div>
    @endif

    <div class="cover">
        <h1 class="cover-title">{{ $locale === 'tr' ? 'Aday Değerlendirme Raporu' : 'Candidate Assessment Report' }}</h1>
        <p class="cover-subtitle">{{ $locale === 'tr' ? 'Yapay Zeka Destekli Mülakat Analizi' : 'AI-Powered Interview Analysis' }}</p>

        <div class="cover-card">
            <table>
                <tr>
                    <td>{{ $locale === 'tr' ? 'Aday Kodu' : 'Candidate ID' }}</td>
                    <td>{{ strtoupper(substr($session->candidate_id, 0, 8)) }}</td>
                </tr>
                <tr>
                    <td>{{ $locale === 'tr' ? 'Pozisyon' : 'Position' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $session->role_key)) }}</td>
                </tr>
                @if($currentContext)
                <tr>
                    <td>{{ $locale === 'tr' ? 'Görev Bağlamı' : 'Job Context' }}</td>
                    <td>{{ $currentContext->getLabel($locale) }}</td>
                </tr>
                @endif
                <tr>
                    <td>{{ $locale === 'tr' ? 'Değerlendirme Tarihi' : 'Assessment Date' }}</td>
                    <td>{{ $session->finished_at?->format('d.m.Y') ?? now()->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td>{{ $locale === 'tr' ? 'Rapor No' : 'Report ID' }}</td>
                    <td>{{ strtoupper(substr($reportId, 0, 8)) }}</td>
                </tr>
            </table>
        </div>

        <div class="decision-badge {{ $analysis->recommendation }}">
            @if($analysis->recommendation === 'hire')
                <span class="decision-badge-icon">✓</span>
                {{ $locale === 'tr' ? 'ÖNERİLİR' : 'RECOMMENDED' }}
            @elseif($analysis->recommendation === 'hold')
                <span class="decision-badge-icon">◐</span>
                {{ $locale === 'tr' ? 'DEĞERLENDİR' : 'EVALUATE' }}
            @else
                <span class="decision-badge-icon">✗</span>
                {{ $locale === 'tr' ? 'ÖNERİLMEZ' : 'NOT RECOMMENDED' }}
            @endif
        </div>

        <div class="score-display">
            <div class="score-display-value">{{ number_format($analysis->overall_score, 0) }}</div>
            <div class="score-display-label">{{ $locale === 'tr' ? 'Genel Puan / 100' : 'Overall Score / 100' }}</div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-left">
            <span class="footer-confidential">{{ $locale === 'tr' ? 'Gizli' : 'Confidential' }}</span>
            @if($branding['white_label'] ?? false)
                {{-- WHITE-LABEL: Customer only --}}
                @if($branding['customer_company_name'] ?? null)
                    <span>{{ $branding['customer_company_name'] }}</span>
                @endif
            @else
                {{-- STANDARD: TalentQX + Customer --}}
                <span>TalentQX</span>
                @if($branding['customer_company_name'] ?? null)
                    <span style="color: var(--text-light);">| {{ $branding['customer_company_name'] }}</span>
                @endif
            @endif
        </div>
        <span>{{ $locale === 'tr' ? 'Sayfa' : 'Page' }} 1 / 4</span>
    </div>
</div>

<!-- PAGE 2: EXECUTIVE SUMMARY -->
<div class="page">
    <div class="confidential-banner">
        {{ $locale === 'tr' ? 'Gizli - Sadece Yetkili Personel İçin' : 'Confidential - Authorized Personnel Only' }}
    </div>

    <div class="header">
        <div class="header-brand">
            {{-- CO-BRAND v1.0: Inner pages show ONLY TalentQX (no customer logo) --}}
            @if($branding['white_label'] ?? false)
                @if($branding['customer_logo_url'] ?? null)
                    <img src="{{ $branding['customer_logo_url'] }}" alt="{{ $branding['customer_company_name'] ?? '' }}" style="max-height: 28px; max-width: 120px;">
                @elseif($branding['customer_company_name'] ?? null)
                    <span class="header-brand-text">{{ $branding['customer_company_name'] }}</span>
                @endif
            @else
                <span class="header-brand-text">TalentQX</span>
                <span style="font-size: 9pt; color: var(--text-muted); margin-left: 8px;">Assessment Report</span>
            @endif
        </div>
        <div class="header-meta">
            <strong>{{ $locale === 'tr' ? 'Yönetici Özeti' : 'Executive Summary' }}</strong>
            {{ $locale === 'tr' ? 'Aday' : 'Candidate' }}: {{ strtoupper(substr($session->candidate_id, 0, 8)) }}
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="quick-stat">
            <div class="quick-stat-value">{{ number_format($analysis->overall_score, 0) }}</div>
            <div class="quick-stat-label">{{ $locale === 'tr' ? 'Genel Puan' : 'Overall' }}</div>
        </div>
        <div class="quick-stat">
            <div class="quick-stat-value">{{ $analysis->confidence_percent ?? 85 }}%</div>
            <div class="quick-stat-label">{{ $locale === 'tr' ? 'Güven' : 'Confidence' }}</div>
        </div>
        <div class="quick-stat">
            <div class="quick-stat-value">{{ count($analysis->strengths ?? []) }}</div>
            <div class="quick-stat-label">{{ $locale === 'tr' ? 'Güçlü Yön' : 'Strengths' }}</div>
        </div>
        <div class="quick-stat">
            <div class="quick-stat-value">{{ count($analysis->red_flags ?? []) }}</div>
            <div class="quick-stat-label">{{ $locale === 'tr' ? 'Risk' : 'Risks' }}</div>
        </div>
    </div>

    <h3 class="section-title">
        <span class="section-icon">1</span>
        {{ $locale === 'tr' ? 'Özet Değerlendirme' : 'Summary Assessment' }}
    </h3>

    <div class="summary-box">
        <p>{{ $narratives['narratives']['executive_summary'] ?? $analysis->summary_text ?? ($locale === 'tr' ? 'Aday değerlendirmesi tamamlanmıştır.' : 'Candidate assessment has been completed.') }}</p>
    </div>

    <h3 class="section-title">
        <span class="section-icon">2</span>
        {{ $locale === 'tr' ? 'Yetkinlik Puanları' : 'Competency Scores' }}
    </h3>

    <table>
        <thead>
            <tr>
                <th>{{ $locale === 'tr' ? 'Boyut' : 'Dimension' }}</th>
                <th style="width: 220px;">{{ $locale === 'tr' ? 'Performans' : 'Performance' }}</th>
                <th style="width: 60px; text-align: right;">{{ $locale === 'tr' ? 'Puan' : 'Score' }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $dimensionLabels = $locale === 'tr' ? [
                    'communication' => 'İletişim',
                    'integrity' => 'Dürüstlük',
                    'problem_solving' => 'Problem Çözme',
                    'stress_tolerance' => 'Stres Yönetimi',
                    'teamwork' => 'Takım Çalışması',
                    'customer_focus' => 'Müşteri Odaklılık',
                    'adaptability' => 'Uyum Sağlama',
                ] : [
                    'communication' => 'Communication',
                    'integrity' => 'Integrity',
                    'problem_solving' => 'Problem Solving',
                    'stress_tolerance' => 'Stress Management',
                    'teamwork' => 'Teamwork',
                    'customer_focus' => 'Customer Focus',
                    'adaptability' => 'Adaptability',
                ];
            @endphp
            @foreach($analysis->dimension_scores ?? [] as $key => $dimension)
            @php
                $score = $dimension['score'] ?? 0;
                $level = $score >= 75 ? 'excellent' : ($score >= 60 ? 'good' : ($score >= 45 ? 'moderate' : 'low'));
            @endphp
            <tr>
                <td>{{ $dimensionLabels[$key] ?? ucwords(str_replace('_', ' ', $key)) }}</td>
                <td>
                    <div class="score-bar-container">
                        <div class="score-bar">
                            <div class="score-bar-fill {{ $level }}" style="width: {{ $score }}%;"></div>
                        </div>
                    </div>
                </td>
                <td class="score-value">{{ $score }}/100</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="insight-grid">
        <div class="insight-card">
            <h4><span class="status-dot green"></span>{{ $locale === 'tr' ? 'Güçlü Yönler' : 'Strengths' }}</h4>
            @if(!empty($narratives['narratives']['radar_strengths']))
                <p style="margin-bottom: 10px; font-style: italic;">{{ $narratives['narratives']['radar_strengths'] }}</p>
            @endif
            <ul>
                @forelse($analysis->strengths ?? [] as $strength)
                    <li>{{ $strength }}</li>
                @empty
                    <li>{{ $locale === 'tr' ? 'Veri bekleniyor' : 'Data pending' }}</li>
                @endforelse
            </ul>
        </div>

        <div class="insight-card">
            <h4><span class="status-dot yellow"></span>{{ $locale === 'tr' ? 'Gelişim Alanları' : 'Development Areas' }}</h4>
            @if(!empty($narratives['narratives']['radar_balance']))
                <p style="margin-bottom: 10px; font-style: italic;">{{ $narratives['narratives']['radar_balance'] }}</p>
            @endif
            <ul>
                @forelse($analysis->improvement_areas ?? [] as $area)
                    <li>{{ $area }}</li>
                @empty
                    <li>{{ $locale === 'tr' ? 'Belirgin gelişim alanı yok' : 'No significant areas' }}</li>
                @endforelse
            </ul>
        </div>
    </div>

    @if(!empty($narratives['narratives']['risk_comment']))
    <div class="warning-box">
        <h4>{{ $locale === 'tr' ? 'Dikkat Edilmesi Gerekenler' : 'Points of Attention' }}</h4>
        <p>{{ $narratives['narratives']['risk_comment'] }}</p>
    </div>
    @endif

    <div class="footer">
        <div class="footer-left">
            <span class="footer-confidential">{{ $locale === 'tr' ? 'Gizli' : 'Confidential' }}</span>
            @if(!($branding['white_label'] ?? false))
                <span>TalentQX</span>
            @elseif($branding['customer_company_name'] ?? null)
                <span>{{ $branding['customer_company_name'] }}</span>
            @endif
            <span style="color: var(--text-light);">{{ $generatedAt->format('d.m.Y H:i') }}</span>
        </div>
        <span>{{ $locale === 'tr' ? 'Sayfa' : 'Page' }} 2 / 4</span>
    </div>
</div>

<!-- PAGE 3: DETAILED ANALYSIS -->
<div class="page">
    <div class="confidential-banner">
        {{ $locale === 'tr' ? 'Gizli - Sadece Yetkili Personel İçin' : 'Confidential - Authorized Personnel Only' }}
    </div>

    <div class="header">
        <div class="header-brand">
            {{-- CO-BRAND v1.0: Inner pages show ONLY TalentQX (no customer logo) --}}
            @if($branding['white_label'] ?? false)
                @if($branding['customer_logo_url'] ?? null)
                    <img src="{{ $branding['customer_logo_url'] }}" alt="{{ $branding['customer_company_name'] ?? '' }}" style="max-height: 28px; max-width: 120px;">
                @elseif($branding['customer_company_name'] ?? null)
                    <span class="header-brand-text">{{ $branding['customer_company_name'] }}</span>
                @endif
            @else
                <span class="header-brand-text">TalentQX</span>
                <span style="font-size: 9pt; color: var(--text-muted); margin-left: 8px;">Assessment Report</span>
            @endif
        </div>
        <div class="header-meta">
            <strong>{{ $locale === 'tr' ? 'Detaylı Analiz' : 'Detailed Analysis' }}</strong>
            {{ $locale === 'tr' ? 'Aday' : 'Candidate' }}: {{ strtoupper(substr($session->candidate_id, 0, 8)) }}
        </div>
    </div>

    <h3 class="section-title">
        <span class="section-icon">3</span>
        {{ $locale === 'tr' ? 'Yetkinlik Radar Grafiği' : 'Competency Radar Chart' }}
    </h3>

    <div class="radar-container">
        <svg class="radar-chart" viewBox="0 0 400 400">
            <!-- Background -->
            <g fill="none" stroke="#E5E7EB" stroke-width="1">
                <circle cx="200" cy="200" r="140"/>
                <circle cx="200" cy="200" r="112"/>
                <circle cx="200" cy="200" r="84"/>
                <circle cx="200" cy="200" r="56"/>
                <circle cx="200" cy="200" r="28"/>
            </g>

            <!-- Axes -->
            <g stroke="#E5E7EB" stroke-width="1">
                @php
                    $labels = $radarData['labels'][$locale] ?? $radarData['labels']['en'];
                    $values = $radarData['values'];
                    $count = count($labels);
                    $angleStep = 360 / $count;
                @endphp
                @for($i = 0; $i < $count; $i++)
                    @php
                        $angle = ($i * $angleStep - 90) * M_PI / 180;
                        $x = 200 + 140 * cos($angle);
                        $y = 200 + 140 * sin($angle);
                    @endphp
                    <line x1="200" y1="200" x2="{{ $x }}" y2="{{ $y }}"/>
                @endfor
            </g>

            <!-- Data -->
            <polygon
                fill="{{ $branding['primary_color'] ?? '#1E3A5F' }}"
                fill-opacity="0.25"
                stroke="{{ $branding['primary_color'] ?? '#1E3A5F' }}"
                stroke-width="2.5"
                points="@php
                    $points = [];
                    for($i = 0; $i < $count; $i++) {
                        $angle = ($i * $angleStep - 90) * M_PI / 180;
                        $value = ($values[$i] ?? 50) / 100;
                        $r = 140 * $value;
                        $x = 200 + $r * cos($angle);
                        $y = 200 + $r * sin($angle);
                        $points[] = round($x, 1) . ',' . round($y, 1);
                    }
                    echo implode(' ', $points);
                @endphp"
            />

            @for($i = 0; $i < $count; $i++)
                @php
                    $angle = ($i * $angleStep - 90) * M_PI / 180;
                    $value = ($values[$i] ?? 50) / 100;
                    $r = 140 * $value;
                    $x = 200 + $r * cos($angle);
                    $y = 200 + $r * sin($angle);
                @endphp
                <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="{{ $branding['primary_color'] ?? '#1E3A5F' }}"/>
            @endfor

            <!-- Labels -->
            @for($i = 0; $i < $count; $i++)
                @php
                    $angle = ($i * $angleStep - 90) * M_PI / 180;
                    $x = 200 + 165 * cos($angle);
                    $y = 200 + 165 * sin($angle);
                    $anchor = 'middle';
                    if($x < 190) $anchor = 'end';
                    if($x > 210) $anchor = 'start';
                @endphp
                <text x="{{ $x }}" y="{{ $y }}" text-anchor="{{ $anchor }}" font-size="10" fill="#374151" font-weight="500">
                    {{ $labels[$i] }}
                </text>
                <text x="{{ $x }}" y="{{ $y + 12 }}" text-anchor="{{ $anchor }}" font-size="9" fill="#6B7280">
                    ({{ $values[$i] ?? 0 }})
                </text>
            @endfor
        </svg>
    </div>

    @if($contextComparison && count($contextComparison) > 0)
    <h3 class="section-title">
        <span class="section-icon">4</span>
        {{ $locale === 'tr' ? 'Bağlam Uygunluk Analizi' : 'Context Fit Analysis' }}
    </h3>

    <table>
        <thead>
            <tr>
                <th>{{ $locale === 'tr' ? 'Görev Bağlamı' : 'Job Context' }}</th>
                <th style="width: 180px;">{{ $locale === 'tr' ? 'Uygunluk' : 'Fit' }}</th>
                <th style="width: 70px; text-align: right;">{{ $locale === 'tr' ? 'Puan' : 'Score' }}</th>
                <th style="width: 100px; text-align: center;">{{ $locale === 'tr' ? 'Durum' : 'Status' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contextComparison as $ctx)
            @php
                $ctxScore = $ctx['score'] ?? 0;
                $ctxLevel = $ctx['level'] ?? 'moderate';
            @endphp
            <tr @if($currentContext && $currentContext->context_key === ($ctx['context_key'] ?? '')) style="background: #EFF6FF;" @endif>
                <td>
                    <strong>{{ $ctx['context'] }}</strong>
                    @if($currentContext && $currentContext->context_key === ($ctx['context_key'] ?? ''))
                        <br><span style="font-size: 8pt; color: var(--accent);">← {{ $locale === 'tr' ? 'Mevcut Başvuru' : 'Current Application' }}</span>
                    @endif
                </td>
                <td>
                    <div class="score-bar-container">
                        <div class="score-bar">
                            <div class="score-bar-fill {{ $ctxLevel }}" style="width: {{ $ctxScore }}%;"></div>
                        </div>
                    </div>
                </td>
                <td class="score-value">{{ number_format($ctxScore, 0) }}</td>
                <td style="text-align: center;">
                    <span class="context-badge {{ $ctxLevel }}">
                        {{ $ctx['status'] ?? ucfirst($ctxLevel) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($narratives['narratives']['context_comparison_comment']))
    <div class="summary-box">
        <p>{{ $narratives['narratives']['context_comparison_comment'] }}</p>
    </div>
    @endif
    @endif

    <h3 class="section-title">
        <span class="section-icon">{{ $contextComparison ? '5' : '4' }}</span>
        {{ $locale === 'tr' ? 'Sonuç ve Öneriler' : 'Conclusion & Recommendations' }}
    </h3>

    <div class="summary-box">
        <p>{{ $narratives['narratives']['closing_comment'] ?? $analysis->hr_recommendations ?? ($locale === 'tr' ? 'Değerlendirme tamamlandı. Detaylı inceleme için İK ile görüşülmesi önerilir.' : 'Assessment completed. Consultation with HR is recommended for detailed review.') }}</p>
    </div>

    <div class="footer">
        <div class="footer-left">
            <span class="footer-confidential">{{ $locale === 'tr' ? 'Gizli' : 'Confidential' }}</span>
            @if(!($branding['white_label'] ?? false))
                <span>TalentQX</span>
            @elseif($branding['customer_company_name'] ?? null)
                <span>{{ $branding['customer_company_name'] }}</span>
            @endif
            <span style="color: var(--text-light);">{{ $generatedAt->format('d.m.Y H:i') }}</span>
        </div>
        <span>{{ $locale === 'tr' ? 'Sayfa' : 'Page' }} 3 / 4</span>
    </div>
</div>

<!-- PAGE 4: RISKS & LEGAL -->
<div class="page">
    <div class="confidential-banner">
        {{ $locale === 'tr' ? 'Gizli - Sadece Yetkili Personel İçin' : 'Confidential - Authorized Personnel Only' }}
    </div>

    <div class="header">
        <div class="header-brand">
            {{-- CO-BRAND v1.0: Inner pages show ONLY TalentQX (no customer logo) --}}
            @if($branding['white_label'] ?? false)
                @if($branding['customer_logo_url'] ?? null)
                    <img src="{{ $branding['customer_logo_url'] }}" alt="{{ $branding['customer_company_name'] ?? '' }}" style="max-height: 28px; max-width: 120px;">
                @elseif($branding['customer_company_name'] ?? null)
                    <span class="header-brand-text">{{ $branding['customer_company_name'] }}</span>
                @endif
            @else
                <span class="header-brand-text">TalentQX</span>
                <span style="font-size: 9pt; color: var(--text-muted); margin-left: 8px;">Assessment Report</span>
            @endif
        </div>
        <div class="header-meta">
            <strong>{{ $locale === 'tr' ? 'Risk ve Uyum' : 'Risk & Compliance' }}</strong>
            {{ $locale === 'tr' ? 'Aday' : 'Candidate' }}: {{ strtoupper(substr($session->candidate_id, 0, 8)) }}
        </div>
    </div>

    <h3 class="section-title">
        <span class="section-icon">!</span>
        {{ $locale === 'tr' ? 'Risk Bayrakları' : 'Risk Flags' }}
    </h3>

    @forelse($analysis->red_flags ?? [] as $flag)
        <div class="risk-item {{ $flag['severity'] ?? 'medium' }}">
            <span class="risk-type">{{ strtoupper($flag['type'] ?? 'INFO') }}</span>
            <div>
                <p style="margin: 0;">{{ $flag['description'] ?? '' }}</p>
                @if(isset($flag['question_id']))
                    <p style="font-size: 8pt; color: var(--text-muted); margin-top: 4px;">
                        {{ $locale === 'tr' ? 'Kaynak: Soru' : 'Source: Question' }} #{{ $flag['question_id'] }}
                    </p>
                @endif
            </div>
        </div>
    @empty
        <div class="insight-card" style="background: #F0FDF4; border-color: var(--success);">
            <p style="color: var(--success); margin: 0;">
                <strong>✓</strong> {{ $locale === 'tr' ? 'Kritik risk bayrağı tespit edilmedi.' : 'No critical risk flags detected.' }}
            </p>
        </div>
    @endforelse

    <h3 class="section-title">
        <span class="section-icon">Q</span>
        {{ $locale === 'tr' ? 'Soru Bazlı Özet' : 'Question Summary' }}
    </h3>

    <table style="font-size: 9pt;">
        <thead>
            <tr>
                <th style="width: 35px;">#</th>
                <th style="width: 55px;">{{ $locale === 'tr' ? 'Puan' : 'Score' }}</th>
                <th>{{ $locale === 'tr' ? 'Değerlendirme Notu' : 'Assessment Note' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($analysis->question_analyses ?? [], 0, 6) as $qa)
            <tr>
                <td style="font-weight: 600;">Q{{ $qa['question_id'] ?? '-' }}</td>
                <td>{{ $qa['score'] ?? 0 }}/{{ $qa['max_score'] ?? 5 }}</td>
                <td>{{ Str::limit($qa['analysis'] ?? '-', 90) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="legal-box">
        <h4>{{ $locale === 'tr' ? 'Yasal Uyarı ve Veri Koruma' : 'Legal Notice & Data Protection' }}</h4>

        <p>
            <strong>{{ $locale === 'tr' ? 'Gizlilik:' : 'Confidentiality:' }}</strong>
            {{ $locale === 'tr'
                ? 'Bu rapor gizli bilgi içermektedir ve yalnızca yetkili personel tarafından görüntülenmelidir. İzinsiz dağıtım veya çoğaltma yasaktır.'
                : 'This report contains confidential information and should only be viewed by authorized personnel. Unauthorized distribution or reproduction is prohibited.'
            }}
        </p>

        <p>
            <strong>{{ $locale === 'tr' ? 'KVKK/GDPR Uyumu:' : 'GDPR/KVKK Compliance:' }}</strong>
            {{ $locale === 'tr'
                ? 'Bu rapor, 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) ve Genel Veri Koruma Yönetmeliği (GDPR) kapsamında hazırlanmıştır. Kişisel veriler yalnızca işe alım değerlendirmesi amacıyla işlenmektedir.'
                : 'This report has been prepared in compliance with the General Data Protection Regulation (GDPR) and applicable local data protection laws. Personal data is processed solely for recruitment evaluation purposes.'
            }}
        </p>

        <p>
            <strong>{{ $locale === 'tr' ? 'Yapay Zeka Bildirimi:' : 'AI Disclosure:' }}</strong>
            {{ $locale === 'tr'
                ? 'Bu rapor yapay zeka destekli analiz içermektedir. AI analiz sonuçları, nihai işe alım kararı için tek başına kullanılmamalı, İK profesyonellerinin değerlendirmesiyle birlikte değerlendirilmelidir.'
                : 'This report contains AI-assisted analysis. AI analysis results should not be used as the sole basis for hiring decisions and should be evaluated in conjunction with HR professional assessment.'
            }}
        </p>

        <p>
            <strong>{{ $locale === 'tr' ? 'Saklama Süresi:' : 'Retention Period:' }}</strong>
            {{ $locale === 'tr'
                ? 'Bu rapor, oluşturulma tarihinden itibaren 30 gün süreyle saklanacak ve ardından otomatik olarak silinecektir.'
                : 'This report will be retained for 30 days from the generation date and will be automatically deleted thereafter.'
            }}
        </p>
    </div>

    <div style="margin-top: 24px; text-align: center; font-size: 9pt; color: var(--text-muted);">
        <p><strong>{{ $locale === 'tr' ? 'Rapor Bilgileri' : 'Report Information' }}</strong></p>
        <p>{{ $locale === 'tr' ? 'Oluşturulma' : 'Generated' }}: {{ $generatedAt->format('d.m.Y H:i:s') }} | ID: {{ $reportId }}</p>

        {{-- CO-BRAND v1.0: Legal attribution format --}}
        @if($branding['customer_company_name'] ?? null)
            <p style="margin-top: 12px;">
                {{ $locale === 'tr' ? 'Hazırlanan:' : 'Prepared for' }} <strong>{{ $branding['customer_company_name'] }}</strong>
            </p>
        @endif
        <p style="margin-top: 4px; font-size: 8pt; color: var(--text-light);">
            {{ $locale === 'tr' ? 'Değerlendirme teknolojisi:' : 'Assessment technology by' }} <strong>TalentQX</strong>
        </p>
    </div>

    <div class="footer">
        <div class="footer-left">
            <span class="footer-confidential">{{ $locale === 'tr' ? 'Gizli' : 'Confidential' }}</span>
            @if(!($branding['white_label'] ?? false))
                <span>TalentQX</span>
            @elseif($branding['customer_company_name'] ?? null)
                <span>{{ $branding['customer_company_name'] }}</span>
            @endif
            <span style="color: var(--text-light);">{{ $generatedAt->format('d.m.Y H:i') }}</span>
        </div>
        <span>{{ $locale === 'tr' ? 'Sayfa' : 'Page' }} 4 / 4</span>
    </div>
</div>

</body>
</html>
