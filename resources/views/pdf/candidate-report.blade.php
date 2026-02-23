<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Aday Değerlendirme Raporu - {{ $candidate->first_name }} {{ $candidate->last_name }}</title>
    <style>
        @page {
            margin: 30px 35px 55px 35px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.45;
            color: #1e293b;
            padding: 0 5px;
        }

        /* ── FOOTER (fixed on every page) ── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 5px 40px;
            border-top: 2px solid #0f4c81;
            background: #f8fafc;
            display: table;
            width: 100%;
        }
        .footer-left {
            display: table-cell;
            text-align: left;
            font-size: 7px;
            color: #64748b;
        }
        .footer-center {
            display: table-cell;
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
        }
        .footer-right {
            display: table-cell;
            text-align: right;
            font-size: 7px;
            color: #64748b;
        }

        /* ── HEADER ── */
        .header {
            display: table;
            width: 100%;
            padding-bottom: 8px;
            border-bottom: 3px solid #0f4c81;
            margin-bottom: 10px;
        }
        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 55%;
        }
        .header-right {
            display: table-cell;
            vertical-align: middle;
            width: 45%;
            text-align: right;
        }
        .brand {
            font-size: 22px;
            font-weight: bold;
            color: #0f4c81;
            letter-spacing: -0.5px;
        }
        .brand-sub {
            font-size: 7px;
            color: #94a3b8;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .header-url {
            font-size: 11px;
            color: #0f4c81;
            font-weight: bold;
        }
        .header-meta {
            font-size: 7px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── TITLE ── */
        .title-block {
            text-align: center;
            margin-bottom: 10px;
            padding: 8px 0;
            background: linear-gradient(135deg, #f0f7ff 0%, #e8f4fd 100%);
            border-radius: 4px;
        }
        .title-block h1 {
            font-size: 14px;
            color: #0f172a;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .title-block .candidate-name {
            font-size: 12px;
            color: #0f4c81;
            font-weight: bold;
            margin-top: 3px;
        }
        .title-block .report-meta {
            font-size: 7px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* ── SECTION ── */
        .section { margin-bottom: 10px; }
        .section-title {
            background: #0f4c81;
            color: white;
            padding: 4px 10px;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        /* ── KPI ROW ── */
        .kpi-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
        }
        .kpi-cell {
            display: table-cell;
            text-align: center;
            padding: 6px 3px;
            border-right: 1px solid #e2e8f0;
            background: #fafbfc;
            vertical-align: middle;
        }
        .kpi-cell:last-child { border-right: none; }
        .kpi-label {
            font-size: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }
        .kpi-value {
            font-size: 18px;
            font-weight: bold;
            line-height: 1.2;
        }
        .kpi-sub { font-size: 6px; color: #94a3b8; }
        .c-green { color: #059669; }
        .c-amber { color: #d97706; }
        .c-red { color: #dc2626; }
        .c-blue { color: #0f4c81; }
        .c-gray { color: #475569; }

        /* ── DATA TABLE ── */
        .data-tbl {
            width: 100%;
            border-collapse: collapse;
        }
        .data-tbl th {
            background: #f1f5f9;
            padding: 3px 6px;
            text-align: left;
            font-size: 7.5px;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            width: 28%;
        }
        .data-tbl td {
            padding: 3px 6px;
            font-size: 9px;
            border-bottom: 1px solid #f1f5f9;
        }

        /* ── TWO COL ── */
        .two-col { display: table; width: 100%; }
        .col-half {
            display: table-cell;
            width: 48%;
            vertical-align: top;
        }
        .col-gap { display: table-cell; width: 4%; }

        /* ── COMP BAR TABLE ── */
        .comp-tbl {
            width: 100%;
            border-collapse: collapse;
        }
        .comp-tbl th {
            background: #0f4c81;
            color: white;
            padding: 4px 6px;
            text-align: left;
            font-size: 7.5px;
            text-transform: uppercase;
        }
        .comp-tbl td {
            padding: 4px 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9px;
        }
        .comp-tbl tr:nth-child(even) td { background: #f8fafc; }
        .bar-outer {
            background: #e2e8f0;
            height: 10px;
            width: 100%;
            border-radius: 5px;
            position: relative;
        }
        .bar-inner {
            height: 10px;
            border-radius: 5px;
            position: absolute;
            top: 0;
            left: 0;
        }
        .bar-green { background: #059669; }
        .bar-amber { background: #d97706; }
        .bar-red { background: #dc2626; }
        .bar-score {
            position: absolute;
            right: 3px;
            top: 0;
            line-height: 10px;
            font-size: 6.5px;
            font-weight: bold;
            color: white;
        }

        /* ── MARITIME BOX ── */
        .maritime-box {
            background: #f0f7ff;
            border: 1px solid #bfdbfe;
            padding: 6px;
        }
        .m-grid { display: table; width: 100%; }
        .m-cell {
            display: table-cell;
            text-align: center;
            padding: 4px;
            vertical-align: middle;
        }
        .m-label { font-size: 6.5px; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        .m-value { font-size: 16px; font-weight: bold; color: #0f4c81; }

        /* ── EXPLANATION ── */
        .explanation {
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
            padding: 6px 10px;
            font-size: 8.5px;
            color: #92400e;
            font-style: italic;
            margin-top: 4px;
        }

        /* ── CERT TAGS ── */
        .cert-tag {
            display: inline-block;
            background: #e0f2fe;
            border: 1px solid #7dd3fc;
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 7px;
            color: #0369a1;
            font-weight: bold;
            margin: 1px;
        }

        /* ── ANSWER BOX ── */
        .answer-box {
            border: 1px solid #e2e8f0;
            margin-bottom: 6px;
            page-break-inside: avoid;
        }
        .answer-head {
            background: #f1f5f9;
            padding: 3px 8px;
            border-bottom: 1px solid #e2e8f0;
            display: table;
            width: 100%;
        }
        .answer-head .ah-left {
            display: table-cell;
            font-weight: bold;
            font-size: 9px;
            color: #0f4c81;
        }
        .answer-head .ah-right {
            display: table-cell;
            text-align: right;
        }
        .answer-body {
            padding: 5px 8px;
            font-size: 8.5px;
            line-height: 1.5;
            color: #334155;
        }
        .badge {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 8px;
        }
        .badge-5 { background: #d1fae5; color: #065f46; }
        .badge-4 { background: #dbeafe; color: #1e40af; }
        .badge-3 { background: #fef3c7; color: #92400e; }
        .badge-low { background: #fee2e2; color: #991b1b; }

        /* ── SIGNATURE ── */
        .sig-block {
            margin-top: 14px;
            border-top: 2px solid #e2e8f0;
            padding-top: 8px;
            page-break-inside: avoid;
        }
        .sig-grid { display: table; width: 100%; }
        .sig-cell { display: table-cell; width: 50%; vertical-align: top; }
        .sig-label {
            font-size: 7px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sig-value {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .sig-line {
            border-bottom: 1px solid #1e293b;
            width: 160px;
            margin-top: 20px;
            margin-bottom: 3px;
        }
        .confidential-bar {
            text-align: center;
            margin-top: 10px;
            font-size: 7px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>

@php
    $scores = $interview->competency_scores ?? [];
    $meta = $interview->meta ?? [];
    $sourceMeta = is_array($candidate->source_meta ?? null) ? $candidate->source_meta : [];
    $ds = $interview->decision_summary_json ?? [];
    $certs = $sourceMeta['certificates'] ?? [];

    $compLabels = [
        'communication'     => 'İletişim',
        'accountability'    => 'Sorumluluk',
        'teamwork'          => 'Takım Çalışması',
        'stress_resilience' => 'Stres Dayanıklılığı',
        'adaptability'      => 'Uyum Yeteneği',
        'learning_agility'  => 'Öğrenme Çevikliği',
        'integrity'         => 'Dürüstlük',
        'role_competence'   => 'Pozisyon Yetkinliği',
    ];

    $decisionColor = match(strtoupper($interview->decision ?? '')) {
        'HIRE' => '#059669',
        'HOLD', 'REVIEW' => '#d97706',
        'REJECT' => '#dc2626',
        default => '#475569',
    };

    $avgScore = count($scores) > 0 ? round(array_sum($scores) / count($scores)) : 0;

    // Generate radar chart as base64 PNG (larger canvas for label room)
    $radarChartUri = '';
    if (!empty($scores)) {
        $radarChartUri = \App\Helpers\RadarChartGenerator::generate($scores, $compLabels, 640);
    }
@endphp

<!-- ═══ FOOTER (fixed on every page) ═══ -->
<div class="footer">
    <div class="footer-left"><strong>Octopus AI</strong> — Maritime Talent Intelligence</div>
    <div class="footer-center">Bu rapor gizlidir. Yalnızca yetkili personel görüntüleyebilir.</div>
    <div class="footer-right">octopus-ai.net | {{ $generatedAt }}</div>
</div>

<!-- ═══════════════ PAGE 1 ═══════════════ -->

<!-- HEADER -->
<div class="header">
    <div class="header-left">
        <div class="brand">&#x1F419; Octopus AI</div>
        <div class="brand-sub">Maritime Talent Intelligence Platform</div>
    </div>
    <div class="header-right">
        <div class="header-url">octopus-ai.net</div>
        <div class="header-meta">Confidential Assessment Report</div>
        <div style="font-size: 8px; color: #64748b;">{{ $generatedAt }}</div>
    </div>
</div>

<!-- TITLE -->
<div class="title-block">
    <h1>ADAY DEĞERLENDİRME RAPORU</h1>
    <div class="candidate-name">{{ $candidate->first_name }} {{ $candidate->last_name }} — {{ ucfirst($meta['department'] ?? 'N/A') }} / {{ ucfirst(str_replace('_', ' ', $meta['role_code'] ?? 'N/A')) }}</div>
    <div class="report-meta">Rapor ID: {{ substr($interview->id, 0, 8) }} | Mülakat: {{ $interview->completed_at?->format('d.m.Y H:i') }} | Dil: {{ strtoupper($interview->language) }} | Endüstri: {{ strtoupper($interview->industry_code) }}</div>
</div>

<!-- KPI ROW -->
<div class="kpi-row">
    <div class="kpi-cell">
        <div class="kpi-label">Final Skor</div>
        <div class="kpi-value c-green">{{ $interview->final_score }}</div>
        <div class="kpi-sub">/ 100</div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-label">Karar</div>
        <div class="kpi-value" style="color: {{ $decisionColor }};">{{ $interview->decision }}</div>
        <div class="kpi-sub">{{ $interview->policy_code ?? '' }}</div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-label">Maritime Skor</div>
        <div class="kpi-value c-blue">{{ $ds['final_score'] ?? '—' }}</div>
        <div class="kpi-sub">/ 100</div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-label">Güven Oranı</div>
        <div class="kpi-value c-gray">%{{ $ds['confidence_pct'] ?? '—' }}</div>
        <div class="kpi-sub">confidence</div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-label">Risk Bayrak</div>
        <div class="kpi-value c-green">{{ count($interview->risk_flags ?? []) }}</div>
        <div class="kpi-sub">bayrak</div>
    </div>
</div>

<!-- BAŞVURU BİLGİLERİ -->
<div class="section">
    <div class="section-title">Başvuru Bilgileri</div>
    <div class="two-col">
        <div class="col-half">
            <table class="data-tbl">
                <tr><th>Ad Soyad</th><td><strong>{{ $candidate->first_name }} {{ $candidate->last_name }}</strong></td></tr>
                <tr><th>E-posta</th><td>{{ $candidate->email }}</td></tr>
                <tr><th>Telefon</th><td>{{ $candidate->phone }}</td></tr>
                <tr><th>Ülke</th><td>{{ $candidate->country_code }}</td></tr>
                <tr><th>Kaynak</th><td>{{ $candidate->source_label ?: $candidate->source_channel }}</td></tr>
                @if($candidate->source_type === 'company_invite' && $candidate->source_label)
                <tr><th>Şirket Daveti</th><td style="color: #2563eb; font-weight: bold;">{{ $candidate->source_label }}</td></tr>
                @endif
            </table>
        </div>
        <div class="col-gap"></div>
        <div class="col-half">
            <table class="data-tbl">
                <tr><th>Pozisyon</th><td><strong>{{ ucfirst(str_replace('_', ' ', $meta['role_code'] ?? '—')) }}</strong></td></tr>
                <tr><th>Departman</th><td>{{ ucfirst($meta['department'] ?? '—') }}</td></tr>
                <tr><th>İngilizce</th><td>{{ $candidate->english_level_self ?? '—' }}</td></tr>
                <tr><th>Deneyim</th><td>{{ $sourceMeta['experience_years'] ?? '—' }} yıl</td></tr>
                <tr><th>Durum</th><td style="color: #059669; font-weight: bold;">{{ strtoupper($candidate->status) }}</td></tr>
            </table>
        </div>
    </div>
    @if(!empty($certs))
    <div style="margin-top: 4px;">
        <span style="font-size: 7px; color: #64748b; font-weight: bold;">Sertifikalar: </span>
        @foreach($certs as $cert)
            <span class="cert-tag">{{ strtoupper($cert) }}</span>
        @endforeach
    </div>
    @endif
</div>

<!-- YETKİNLİK ANALİZİ — Radar Chart -->
<div class="section">
    <div class="section-title">Yetkinlik Analizi — Örümcek Grafik</div>

    @if($radarChartUri)
    <div style="text-align: center; margin-bottom: 6px;">
        <img src="{{ $radarChartUri }}" width="260" height="260" style="display: inline-block;" />
    </div>
    @endif

    <!-- Competency Bar Table -->
    <table class="comp-tbl">
        <thead>
            <tr>
                <th style="width: 28%;">Yetkinlik</th>
                <th style="width: 10%; text-align: center;">Skor</th>
                <th style="width: 62%;">Performans</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scores as $comp => $score)
            <tr>
                <td style="font-weight: bold; font-size: 8.5px;">{{ $compLabels[$comp] ?? $comp }}</td>
                <td style="text-align: center; font-weight: bold; color: {{ $score >= 85 ? '#059669' : ($score >= 70 ? '#d97706' : '#dc2626') }};">
                    {{ $score }}
                </td>
                <td>
                    <div class="bar-outer">
                        <div class="bar-inner {{ $score >= 85 ? 'bar-green' : ($score >= 70 ? 'bar-amber' : 'bar-red') }}" style="width: {{ $score }}%;">
                            @if($score >= 30)
                            <span class="bar-score">{{ $score }}%</span>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
            @endforeach
            <tr style="background: #f0f7ff;">
                <td style="font-weight: bold; color: #0f4c81; font-size: 8.5px;">ORTALAMA</td>
                <td style="text-align: center; font-weight: bold; color: #0f4c81; font-size: 11px;">{{ $avgScore }}</td>
                <td>
                    <div class="bar-outer" style="height: 12px;">
                        <div class="bar-inner bar-green" style="width: {{ $avgScore }}%; height: 12px; background: #0f4c81;">
                            <span class="bar-score" style="line-height: 12px;">{{ $avgScore }}%</span>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <div style="margin-top: 4px; font-size: 7px; color: #94a3b8;">
        <span style="display: inline-block; width: 7px; height: 7px; background: #059669; border-radius: 2px;"></span> 85-100: Mükemmel &nbsp;&nbsp;
        <span style="display: inline-block; width: 7px; height: 7px; background: #d97706; border-radius: 2px;"></span> 70-84: İyi &nbsp;&nbsp;
        <span style="display: inline-block; width: 7px; height: 7px; background: #dc2626; border-radius: 2px;"></span> 0-69: Geliştirilmeli
    </div>
</div>

<!-- ═══ PAGE BREAK — Maritime + Answers on new page ═══ -->
<div class="page-break"></div>

<!-- ═══════════════ PAGE 2: MARİTİME + CEVAPLAR ═══════════════ -->

<!-- HEADER (Repeated) -->
<div class="header">
    <div class="header-left">
        <div class="brand">&#x1F419; Octopus AI</div>
        <div class="brand-sub">Maritime Talent Intelligence Platform</div>
    </div>
    <div class="header-right">
        <div class="header-url">octopus-ai.net</div>
        <div class="header-meta">{{ $candidate->first_name }} {{ $candidate->last_name }} — Detaylı Değerlendirme</div>
    </div>
</div>

<!-- MARİTİME KARAR MOTORU -->
@if(!empty($ds))
<div class="section">
    <div class="section-title">Maritime Karar Motoru Sonuçları</div>
    <div class="maritime-box">
        <div class="m-grid">
            @php
                $catLabels = [
                    'core_duty' => 'Ana Görev',
                    'risk_safety' => 'Risk & Güvenlik',
                    'procedure_discipline' => 'Prosedür Disiplini',
                    'communication_judgment' => 'İletişim & Yargı',
                ];
                $catScores = $ds['category_scores'] ?? [];
            @endphp
            @foreach($catScores as $cat => $catScore)
                <div class="m-cell">
                    <div class="m-label">{{ $catLabels[$cat] ?? $cat }}</div>
                    <div class="m-value" style="color: {{ $catScore >= 90 ? '#059669' : ($catScore >= 70 ? '#d97706' : '#dc2626') }};">{{ $catScore }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @if(!empty($ds['explanation']))
        <div class="explanation">{{ $ds['explanation'] }}</div>
    @endif
</div>
@endif

<!-- DAVRANIŞSAL PROFİL (Advisory) -->
@if(!empty($behavioralSnapshot))
<div class="section">
    <div class="section-title">Davranışsal Profil (Danışma Amaçlı)</div>
    <div style="font-size: 8px; color: #6b7280; margin-bottom: 8px;">
        Teknik yeterlilik skorundan bağımsızdır; destekleyici sinyal olarak sunulur.
    </div>
    <div class="maritime-box" style="background: #faf5ff; border-color: #c4b5fd;">
        <div class="m-grid">
            <div class="m-cell">
                <div class="m-label">Durum</div>
                <div class="m-value" style="color: #6d28d9;">{{ ucfirst($behavioralSnapshot['status']) }}</div>
            </div>
            <div class="m-cell">
                <div class="m-label">Güven</div>
                <div class="m-value" style="color: #6d28d9;">{{ number_format($behavioralSnapshot['confidence'] * 100, 0) }}%</div>
            </div>
            @if(!empty($behavioralSnapshot['fit_top3']))
                @foreach($behavioralSnapshot['fit_top3'] as $fit)
                <div class="m-cell">
                    <div class="m-label">{{ $fit['class'] }}</div>
                    <div class="m-value" style="color: {{ $fit['risk_flag'] ?? false ? '#dc2626' : ($fit['fit'] >= 67 ? '#059669' : '#d97706') }};">{{ $fit['fit'] }}%</div>
                </div>
                @endforeach
            @endif
        </div>
    </div>
    @if(!empty($behavioralSnapshot['flags']))
        <div style="margin-top: 5px;">
            @foreach($behavioralSnapshot['flags'] as $flag)
                <span style="display: inline-block; background: #fffbeb; color: #d97706; padding: 2px 6px; border-radius: 3px; font-size: 8px; margin: 1px;">
                    {{ $flag['type'] }}: {{ $flag['detail'] }}
                </span>
            @endforeach
        </div>
    @endif
    @if(!empty($behavioralSnapshot['dimensions']))
        <table style="width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 9px;">
            <tr style="background: #f9fafb;">
                <th style="border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left;">Boyut</th>
                <th style="border: 1px solid #e5e7eb; padding: 4px 6px; text-align: center;">Skor</th>
                <th style="border: 1px solid #e5e7eb; padding: 4px 6px; text-align: center;">Seviye</th>
            </tr>
            @php
                $dimLabels = [
                    'DISCIPLINE_COMPLIANCE' => 'Disiplin & Uyum',
                    'TEAM_COOPERATION' => 'Takım İşbirliği',
                    'COMM_CLARITY' => 'İletişim Netliği',
                    'STRESS_CONTROL' => 'Stres Kontrolü',
                    'CONFLICT_RISK' => 'Çatışma Riski',
                    'LEARNING_GROWTH' => 'Öğrenme & Gelişim',
                    'RELIABILITY_STABILITY' => 'Güvenilirlik & Kararlılık',
                ];
                $levelLabels = ['high' => 'Yüksek', 'mid' => 'Orta', 'low' => 'Düşük'];
            @endphp
            @foreach($behavioralSnapshot['dimensions'] as $dim => $data)
            <tr>
                <td style="border: 1px solid #e5e7eb; padding: 4px 6px;">{{ $dimLabels[$dim] ?? $dim }}</td>
                <td style="border: 1px solid #e5e7eb; padding: 4px 6px; text-align: center; font-weight: bold; color: {{ $data['score'] >= 67 ? '#059669' : ($data['score'] >= 34 ? '#d97706' : '#dc2626') }};">{{ $data['score'] }}</td>
                <td style="border: 1px solid #e5e7eb; padding: 4px 6px; text-align: center;">{{ $levelLabels[$data['level']] ?? $data['level'] }}</td>
            </tr>
            @endforeach
        </table>
    @endif
</div>
@endif

<!-- YETKİNLİK NARRATIVE ÖZETİ -->
<div class="section">
    <div class="section-title">Yetkinlik Değerlendirme Özeti</div>
    @php
        // Group competencies by performance tier
        $excellent = collect($scores)->filter(fn($s) => $s >= 85)->keys();
        $good = collect($scores)->filter(fn($s) => $s >= 70 && $s < 85)->keys();
        $developing = collect($scores)->filter(fn($s) => $s < 70)->keys();
    @endphp
    <div style="padding: 6px; background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 6px;">
        @if($excellent->isNotEmpty())
        <div style="margin-bottom: 6px;">
            <span style="font-size: 8px; font-weight: bold; color: #059669;">● Güçlü Alanlar:</span>
            <span style="font-size: 8.5px; color: #334155;">
                {{ $excellent->map(fn($k) => ($compLabels[$k] ?? $k) . ' (' . $scores[$k] . ')')->implode(', ') }}
            </span>
        </div>
        @endif
        @if($good->isNotEmpty())
        <div style="margin-bottom: 6px;">
            <span style="font-size: 8px; font-weight: bold; color: #d97706;">● Yeterli Alanlar:</span>
            <span style="font-size: 8.5px; color: #334155;">
                {{ $good->map(fn($k) => ($compLabels[$k] ?? $k) . ' (' . $scores[$k] . ')')->implode(', ') }}
            </span>
        </div>
        @endif
        @if($developing->isNotEmpty())
        <div style="margin-bottom: 6px;">
            <span style="font-size: 8px; font-weight: bold; color: #dc2626;">● Gelişim Alanları:</span>
            <span style="font-size: 8.5px; color: #334155;">
                {{ $developing->map(fn($k) => ($compLabels[$k] ?? $k) . ' (' . $scores[$k] . ')')->implode(', ') }}
            </span>
        </div>
        @endif
        <div style="margin-top: 6px; font-size: 8.5px; color: #334155; line-height: 1.6;">
            {{ $candidate->first_name }} {{ $candidate->last_name }},
            {{ count($scores) }} yetkinlik alanında değerlendirilmiştir. Ortalama skor <strong>{{ $avgScore }}/100</strong> olup,
            @if($avgScore >= 85) performans mükemmel seviyededir.
            @elseif($avgScore >= 70) performans yeterli seviyededir.
            @else performansında gelişim alanları tespit edilmiştir.
            @endif
            @if(!empty($ds['explanation'])) {{ $ds['explanation'] }} @endif
        </div>
    </div>
</div>

<!-- DEĞERLENDİRME KANITI (question_code + competency + evidence extract) -->
@if($interview->answers && $interview->answers->count() > 0)
<div class="section">
    <div class="section-title">Değerlendirme Kanıtları</div>
    <table class="comp-tbl">
        <thead>
            <tr>
                <th style="width: 8%;">Kod</th>
                <th style="width: 20%;">Yetkinlik</th>
                <th style="width: 10%; text-align: center;">Skor</th>
                <th style="width: 62%;">Kanıt Özeti</th>
            </tr>
        </thead>
        <tbody>
            @foreach($interview->answers->sortBy('slot') as $answer)
            @php
                $evidenceWords = array_slice(explode(' ', trim($answer->answer_text)), 0, 25);
                $evidence = implode(' ', $evidenceWords);
                if (count(explode(' ', trim($answer->answer_text))) > 25) $evidence .= '…';
                $ansScore = $scores[$answer->competency] ?? $answer->score ?? null;
            @endphp
            <tr>
                <td style="font-weight: bold; color: #0f4c81; font-size: 8px;">Q{{ $answer->slot }}</td>
                <td style="font-size: 8.5px;">{{ $compLabels[$answer->competency] ?? ucfirst(str_replace('_', ' ', $answer->competency)) }}</td>
                <td style="text-align: center; font-weight: bold; color: {{ ($ansScore ?? 0) >= 85 ? '#059669' : (($ansScore ?? 0) >= 70 ? '#d97706' : '#dc2626') }};">
                    {{ $ansScore ?? '—' }}
                </td>
                <td style="font-size: 8px; color: #475569; line-height: 1.4;">{{ $evidence }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top: 3px; font-size: 6.5px; color: #94a3b8;">
        Kanıt özeti: aday cevabının ilk 25 kelimesi. Tam metin gizlidir. Soru metinleri gösterilmez.
    </div>
</div>
@endif

<!-- KARAR GEREKÇESİ -->
@if(!empty($ds))
<div class="section">
    <div class="section-title">Karar Gerekçesi</div>
    <div style="padding: 6px; background: #f8fafc; border: 1px solid #e2e8f0;">
        <table class="data-tbl" style="margin-bottom: 6px;">
            <tr>
                <th>Karar</th>
                <td><strong style="color: {{ $decisionColor }};">{{ strtoupper($interview->decision ?? '—') }}</strong></td>
            </tr>
            <tr>
                <th>Güven Oranı</th>
                <td>%{{ $ds['confidence_pct'] ?? '—' }}</td>
            </tr>
            @if(!empty($ds['reason']))
            <tr>
                <th>Gerekçe</th>
                <td>{{ $ds['reason'] }}</td>
            </tr>
            @endif
            @if(!empty($ds['action_line']))
            <tr>
                <th>Önerilen Aksiyon</th>
                <td>{{ $ds['action_line'] }}</td>
            </tr>
            @endif
        </table>
        @if(!empty($ds['strengths']))
        <div style="margin-bottom: 4px;">
            <span style="font-size: 7.5px; font-weight: bold; color: #059669; text-transform: uppercase;">Güçlü Yönler:</span>
            <span style="font-size: 8.5px; color: #334155;">
                @foreach($ds['strengths'] as $s) {{ $s }}@if(!$loop->last); @endif @endforeach
            </span>
        </div>
        @endif
        @if(!empty($ds['risks']))
        <div>
            <span style="font-size: 7.5px; font-weight: bold; color: #dc2626; text-transform: uppercase;">Risk Faktörleri:</span>
            <span style="font-size: 8.5px; color: #334155;">
                @foreach($ds['risks'] as $r) {{ $r }}@if(!$loop->last); @endif @endforeach
            </span>
        </div>
        @endif
    </div>
</div>
@endif

<!-- RİSK NOTLARI -->
@if(!empty($interview->risk_flags) && count($interview->risk_flags) > 0)
<div class="section">
    <div class="section-title">Risk Bayrakları ({{ count($interview->risk_flags) }})</div>
    <div style="padding: 6px; border: 1px solid #fecaca; background: #fef2f2;">
        @foreach($interview->risk_flags as $flag)
        <div style="margin-bottom: 3px; font-size: 8.5px; color: #991b1b;">
            <span style="font-weight: bold;">⚠</span>
            @if(is_array($flag))
                {{ $flag['code'] ?? '' }}: {{ $flag['detail'] ?? $flag['message'] ?? '' }}
            @else
                {{ $flag }}
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- SERTİFİKA DURUMU -->
@if(!empty($certificateRisks) && count($certificateRisks) > 0)
<div class="section">
    <div class="section-title">Sertifika Geçerlilik Durumu</div>
    <table class="comp-tbl">
        <thead>
            <tr>
                <th style="width: 25%;">Sertifika</th>
                <th style="width: 15%;">No</th>
                <th style="width: 15%;">Veriliş</th>
                <th style="width: 15%;">Bitiş</th>
                <th style="width: 15%;">Durum</th>
                <th style="width: 15%;">Kalan Gün</th>
            </tr>
        </thead>
        <tbody>
            @foreach($certificateRisks as $cert)
            <tr>
                <td style="font-weight: bold;">{{ strtoupper($cert['certificate_type']) }}</td>
                <td>{{ $cert['certificate_code'] ?? '—' }}</td>
                <td>{{ $cert['issued_at'] ?? '—' }}</td>
                <td>{{ $cert['expires_at'] ?? '—' }}</td>
                <td>
                    <span style="display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 7px; font-weight: bold;
                        background: {{ $cert['risk_color'] === 'green' ? '#d1fae5' : ($cert['risk_color'] === 'yellow' ? '#fef3c7' : ($cert['risk_color'] === 'red' ? '#fee2e2' : '#f1f5f9')) }};
                        color: {{ $cert['risk_color'] === 'green' ? '#065f46' : ($cert['risk_color'] === 'yellow' ? '#92400e' : ($cert['risk_color'] === 'red' ? '#991b1b' : '#475569')) }};">
                        {{ strtoupper($cert['risk_level']) }}
                    </span>
                </td>
                <td style="text-align: center; font-weight: bold; color: {{ $cert['risk_color'] === 'green' ? '#059669' : ($cert['risk_color'] === 'red' ? '#dc2626' : '#d97706') }};">
                    {{ $cert['days_remaining'] !== null ? $cert['days_remaining'] : '—' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- İMZA BLOĞU (stays on same page as answers) -->
<div class="sig-block">
    <div class="sig-grid">
        <div class="sig-cell">
            <div class="sig-label">Raporu Oluşturan</div>
            <div class="sig-value">Octopus AI Assessment Engine</div>
            <div style="font-size: 7.5px; color: #64748b;">Maritime Decision Engine v1 + Calibration v2</div>
            <div class="sig-line"></div>
            <div style="font-size: 7px; color: #94a3b8;">Dijital İmza — Octopus AI Platform</div>
        </div>
        <div class="sig-cell" style="text-align: right;">
            <div class="sig-label">Doğrulama Bilgileri</div>
            <div style="font-size: 7.5px; color: #475569; margin-top: 4px;">
                Template SHA256:<br>
                <span style="font-size: 6px; font-family: monospace; color: #94a3b8;">{{ $interview->template_json_sha256 }}</span>
            </div>
            <div style="font-size: 7.5px; color: #475569; margin-top: 4px;">
                Oluşturulma: {{ $generatedAt }}<br>
                Kullanıcı: {{ $generatedBy }}
            </div>
        </div>
    </div>
    <div class="confidential-bar">
        Bu rapor Octopus AI tarafından otomatik olarak üretilmiştir. İçerdiği değerlendirmeler yapay zeka destekli mülakat analizi sonuçlarına dayanmaktadır.<br>
        <strong>GİZLİLİK NOTU:</strong> Bu doküman yalnızca yetkili personel tarafından görüntülenmelidir. | octopus-ai.net
    </div>
</div>

</body>
</html>
