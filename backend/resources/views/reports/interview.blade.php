<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Report - {{ substr($session->id, 0, 8) }}</title>
    <style>
        /* CSS Variables for White-Label */
        :root {
            --primary-color: {{ $branding['primary_color'] ?? '#3B82F6' }};
            --secondary-color: {{ $branding['secondary_color'] ?? '#1E40AF' }};
            --text-color: #1F2937;
            --text-muted: #6B7280;
            --border-color: #E5E7EB;
            --bg-light: #F9FAFB;
            --success-color: #10B981;
            --warning-color: #F59E0B;
            --danger-color: #EF4444;
        }

        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: var(--text-color);
            background: white;
        }

        /* Page Setup */
        @page {
            size: A4;
            margin: 15mm;
        }

        .page {
            page-break-after: always;
            min-height: 100vh;
            position: relative;
        }

        .page:last-child {
            page-break-after: auto;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            margin-bottom: 20px;
        }

        .logo {
            max-height: 50px;
            max-width: 150px;
        }

        .header-text {
            text-align: right;
        }

        .header-text h1 {
            font-size: 18pt;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .header-text p {
            font-size: 10pt;
            color: var(--text-muted);
        }

        /* Cover Page */
        .cover {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
            text-align: center;
        }

        .cover h1 {
            font-size: 28pt;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .cover h2 {
            font-size: 14pt;
            color: var(--text-muted);
            font-weight: normal;
            margin-bottom: 40px;
        }

        .cover-info {
            background: var(--bg-light);
            padding: 30px 50px;
            border-radius: 10px;
            margin-bottom: 40px;
        }

        .cover-info table {
            text-align: left;
        }

        .cover-info td {
            padding: 8px 20px;
        }

        .cover-info td:first-child {
            color: var(--text-muted);
        }

        .score-badge {
            display: inline-block;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 24pt;
            font-weight: bold;
            color: white;
        }

        .score-badge.hire { background: var(--success-color); }
        .score-badge.hold { background: var(--warning-color); }
        .score-badge.reject { background: var(--danger-color); }

        /* Section Titles */
        .section-title {
            font-size: 14pt;
            color: var(--primary-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
            margin-bottom: 15px;
            margin-top: 25px;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: var(--bg-light);
            font-weight: 600;
            color: var(--text-color);
        }

        /* Score Bars */
        .score-bar {
            width: 100%;
            height: 8px;
            background: var(--border-color);
            border-radius: 4px;
            overflow: hidden;
        }

        .score-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: var(--primary-color);
        }

        /* Radar Chart Container */
        .radar-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .radar-chart {
            width: 350px;
            height: 350px;
        }

        /* Risk Flags */
        .risk-flag {
            display: flex;
            align-items: flex-start;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 6px;
            border-left: 4px solid;
        }

        .risk-flag.high {
            background: #FEF2F2;
            border-color: var(--danger-color);
        }

        .risk-flag.medium {
            background: #FFFBEB;
            border-color: var(--warning-color);
        }

        .risk-flag.low {
            background: #F0FDF4;
            border-color: var(--success-color);
        }

        .risk-flag-type {
            font-weight: 600;
            margin-right: 10px;
        }

        /* Insights */
        .insight-card {
            background: var(--bg-light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .insight-card h4 {
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .insight-card ul {
            margin-left: 20px;
        }

        .insight-card li {
            margin-bottom: 5px;
        }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px 0;
            border-top: 1px solid var(--border-color);
            font-size: 9pt;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
        }

        /* Disclaimer */
        .disclaimer {
            font-size: 9pt;
            color: var(--text-muted);
            background: var(--bg-light);
            padding: 15px;
            border-radius: 6px;
            margin-top: 30px;
        }

        /* Recommendation Badge */
        .recommendation {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 10pt;
            font-weight: 600;
        }

        .recommendation.hire {
            background: #D1FAE5;
            color: #065F46;
        }

        .recommendation.hold {
            background: #FEF3C7;
            color: #92400E;
        }

        .recommendation.reject {
            background: #FEE2E2;
            color: #991B1B;
        }

        /* Print optimizations */
        @media print {
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .page { page-break-after: always; }
        }
    </style>
</head>
<body>

<!-- PAGE 1: COVER -->
<div class="page">
    <div class="cover">
        @if($branding['logo_url'])
            <img src="{{ $branding['logo_url'] }}" alt="Logo" class="logo" style="margin-bottom: 30px;">
        @endif

        <h1>{{ $locale === 'tr' ? 'Aday Değerlendirme Raporu' : 'Candidate Assessment Report' }}</h1>
        <h2>{{ $locale === 'tr' ? 'Mülakat ve Yetkinlik Analizi' : 'Interview & Competency Analysis' }}</h2>

        <div class="cover-info">
            <table>
                <tr>
                    <td>{{ $locale === 'tr' ? 'Aday Kodu' : 'Candidate Code' }}:</td>
                    <td><strong>{{ strtoupper(substr($session->candidate_id, 0, 8)) }}</strong></td>
                </tr>
                <tr>
                    <td>{{ $locale === 'tr' ? 'Değerlendirme Tarihi' : 'Assessment Date' }}:</td>
                    <td><strong>{{ $session->finished_at?->format('d.m.Y') ?? now()->format('d.m.Y') }}</strong></td>
                </tr>
                <tr>
                    <td>{{ $locale === 'tr' ? 'Pozisyon' : 'Position' }}:</td>
                    <td><strong>{{ ucwords(str_replace('_', ' ', $session->role_key)) }}</strong></td>
                </tr>
                <tr>
                    <td>{{ $locale === 'tr' ? 'Genel Puan' : 'Overall Score' }}:</td>
                    <td><strong>{{ number_format($analysis->overall_score, 1) }} / 100</strong></td>
                </tr>
            </table>
        </div>

        <div class="score-badge {{ $analysis->recommendation }}">
            @if($analysis->recommendation === 'hire')
                {{ $locale === 'tr' ? 'ÖNERİLİR' : 'RECOMMENDED' }}
            @elseif($analysis->recommendation === 'hold')
                {{ $locale === 'tr' ? 'BEKLET' : 'HOLD' }}
            @else
                {{ $locale === 'tr' ? 'ÖNERİLMEZ' : 'NOT RECOMMENDED' }}
            @endif
        </div>

        <div class="disclaimer" style="margin-top: 50px; max-width: 500px;">
            {{ $locale === 'tr'
                ? 'Bu rapor yapay zeka destekli analiz içermektedir. Nihai işe alım kararı insan kaynakları uzmanlarının değerlendirmesiyle birlikte verilmelidir.'
                : 'This report contains AI-assisted analysis. Final hiring decisions should be made in conjunction with HR professional evaluation.'
            }}
        </div>
    </div>

    <div class="footer">
        <span>{{ $locale === 'tr' ? 'Rapor No' : 'Report ID' }}: {{ substr($reportId, 0, 8) }}</span>
        <span>{{ $locale === 'tr' ? 'Sayfa' : 'Page' }} 1 / 4</span>
    </div>
</div>

<!-- PAGE 2: EXECUTIVE SUMMARY -->
<div class="page">
    <div class="header">
        @if($branding['logo_url'])
            <img src="{{ $branding['logo_url'] }}" alt="Logo" class="logo">
        @else
            <div></div>
        @endif
        <div class="header-text">
            <h1>{{ $locale === 'tr' ? 'Yönetici Özeti' : 'Executive Summary' }}</h1>
            <p>{{ $locale === 'tr' ? 'Aday Kodu' : 'Candidate' }}: {{ strtoupper(substr($session->candidate_id, 0, 8)) }}</p>
        </div>
    </div>

    <h3 class="section-title">{{ $locale === 'tr' ? 'Değerlendirme Sonuçları' : 'Assessment Results' }}</h3>

    <table>
        <thead>
            <tr>
                <th>{{ $locale === 'tr' ? 'Boyut' : 'Dimension' }}</th>
                <th style="width: 80px;">{{ $locale === 'tr' ? 'Puan' : 'Score' }}</th>
                <th style="width: 200px;">{{ $locale === 'tr' ? 'Görsel' : 'Visual' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($analysis->dimension_scores ?? [] as $key => $dimension)
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                <td>{{ $dimension['score'] ?? 0 }}/100</td>
                <td>
                    <div class="score-bar">
                        <div class="score-bar-fill" style="width: {{ $dimension['score'] ?? 0 }}%;"></div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3 class="section-title">{{ $locale === 'tr' ? 'Özet Değerlendirme' : 'Summary Assessment' }}</h3>

    <div class="insight-card">
        <p>{{ $narratives['narratives']['executive_summary'] ?? $analysis->summary_text }}</p>
    </div>

    <div style="display: flex; gap: 20px;">
        <div class="insight-card" style="flex: 1;">
            <h4>{{ $locale === 'tr' ? 'Güçlü Yönler' : 'Strengths' }}</h4>
            <p style="font-size: 10pt; margin-bottom: 8px;">{{ $narratives['narratives']['radar_strengths'] ?? '' }}</p>
            <ul>
                @foreach($analysis->strengths ?? [] as $strength)
                    <li>{{ $strength }}</li>
                @endforeach
            </ul>
        </div>

        <div class="insight-card" style="flex: 1;">
            <h4>{{ $locale === 'tr' ? 'Gelişim Alanları' : 'Areas for Improvement' }}</h4>
            <p style="font-size: 10pt; margin-bottom: 8px;">{{ $narratives['narratives']['radar_balance'] ?? '' }}</p>
            <ul>
                @foreach($analysis->improvement_areas ?? [] as $area)
                    <li>{{ $area }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    @if(!empty($narratives['narratives']['risk_comment']))
    <div class="insight-card" style="border-left: 3px solid var(--warning-color); background: #FFFBEB;">
        <h4 style="color: var(--warning-color);">{{ $locale === 'tr' ? 'Dikkat Edilmesi Gerekenler' : 'Points of Attention' }}</h4>
        <p>{{ $narratives['narratives']['risk_comment'] }}</p>
    </div>
    @endif

    <h3 class="section-title">{{ $locale === 'tr' ? 'Sonuç ve Öneriler' : 'Conclusion & Recommendations' }}</h3>
    <div class="insight-card">
        <p>{{ $narratives['narratives']['closing_comment'] ?? $analysis->hr_recommendations }}</p>
    </div>

    @if($contextComparison && count($contextComparison) > 0)
    <h3 class="section-title">{{ $locale === 'tr' ? 'Bağlam Karşılaştırması' : 'Context Comparison' }}</h3>
    <table>
        <thead>
            <tr>
                <th>{{ $locale === 'tr' ? 'Görev Bağlamı' : 'Job Context' }}</th>
                <th style="width: 80px;">{{ $locale === 'tr' ? 'Puan' : 'Score' }}</th>
                <th style="width: 80px;">{{ $locale === 'tr' ? 'Durum' : 'Status' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contextComparison as $ctx)
            <tr @if($currentContext && $currentContext->context_key === $ctx['context_key']) style="background: var(--bg-light); font-weight: 600;" @endif>
                <td>
                    {{ $ctx['context'] }}
                    @if($currentContext && $currentContext->context_key === $ctx['context_key'])
                        <span style="font-size: 9pt; color: var(--primary-color);">← {{ $locale === 'tr' ? 'Mevcut' : 'Current' }}</span>
                    @endif
                </td>
                <td>{{ number_format($ctx['score'], 0) }}/100</td>
                <td style="text-align: center;">
                    <span class="recommendation {{ $ctx['level'] === 'excellent' ? 'hire' : ($ctx['level'] === 'good' ? 'hire' : ($ctx['level'] === 'moderate' ? 'hold' : 'reject')) }}">
                        {{ $ctx['indicator'] }} {{ $ctx['status'] }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="insight-card" style="margin-top: 15px;">
        <p>{{ $narratives['narratives']['context_comparison_comment'] ?? '' }}</p>
    </div>
    @endif

    <div class="footer">
        <span>{{ $branding['company_name'] ?? 'TalentQX' }}</span>
        <span>{{ $locale === 'tr' ? 'Sayfa' : 'Page' }} 2 / 4</span>
    </div>
</div>

<!-- PAGE 3: RADAR CHART & BEHAVIOR ANALYSIS -->
<div class="page">
    <div class="header">
        @if($branding['logo_url'])
            <img src="{{ $branding['logo_url'] }}" alt="Logo" class="logo">
        @else
            <div></div>
        @endif
        <div class="header-text">
            <h1>{{ $locale === 'tr' ? 'Yetkinlik Analizi' : 'Competency Analysis' }}</h1>
            <p>{{ $locale === 'tr' ? 'Radar Grafiği ve Davranış Değerlendirmesi' : 'Radar Chart & Behavioral Assessment' }}</p>
        </div>
    </div>

    <div class="radar-container">
        <!-- SVG Radar Chart -->
        <svg class="radar-chart" viewBox="0 0 400 400">
            <!-- Background circles -->
            <g fill="none" stroke="#E5E7EB" stroke-width="1">
                <circle cx="200" cy="200" r="150"/>
                <circle cx="200" cy="200" r="120"/>
                <circle cx="200" cy="200" r="90"/>
                <circle cx="200" cy="200" r="60"/>
                <circle cx="200" cy="200" r="30"/>
            </g>

            <!-- Axis lines -->
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
                        $x = 200 + 150 * cos($angle);
                        $y = 200 + 150 * sin($angle);
                    @endphp
                    <line x1="200" y1="200" x2="{{ $x }}" y2="{{ $y }}"/>
                @endfor
            </g>

            <!-- Data polygon -->
            <polygon
                fill="{{ $branding['primary_color'] ?? '#3B82F6' }}"
                fill-opacity="0.3"
                stroke="{{ $branding['primary_color'] ?? '#3B82F6' }}"
                stroke-width="2"
                points="@php
                    $points = [];
                    for($i = 0; $i < $count; $i++) {
                        $angle = ($i * $angleStep - 90) * M_PI / 180;
                        $value = ($values[$i] ?? 50) / 100;
                        $r = 150 * $value;
                        $x = 200 + $r * cos($angle);
                        $y = 200 + $r * sin($angle);
                        $points[] = round($x, 1) . ',' . round($y, 1);
                    }
                    echo implode(' ', $points);
                @endphp"
            />

            <!-- Data points -->
            @for($i = 0; $i < $count; $i++)
                @php
                    $angle = ($i * $angleStep - 90) * M_PI / 180;
                    $value = ($values[$i] ?? 50) / 100;
                    $r = 150 * $value;
                    $x = 200 + $r * cos($angle);
                    $y = 200 + $r * sin($angle);
                @endphp
                <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="{{ $branding['primary_color'] ?? '#3B82F6' }}"/>
            @endfor

            <!-- Labels -->
            @for($i = 0; $i < $count; $i++)
                @php
                    $angle = ($i * $angleStep - 90) * M_PI / 180;
                    $x = 200 + 175 * cos($angle);
                    $y = 200 + 175 * sin($angle);
                    $anchor = 'middle';
                    if($x < 190) $anchor = 'end';
                    if($x > 210) $anchor = 'start';
                @endphp
                <text x="{{ $x }}" y="{{ $y }}" text-anchor="{{ $anchor }}" font-size="11" fill="#374151">
                    {{ $labels[$i] }} ({{ $values[$i] ?? 0 }})
                </text>
            @endfor
        </svg>
    </div>

    <h3 class="section-title">{{ $locale === 'tr' ? 'Davranış Analizi' : 'Behavior Analysis' }}</h3>

    <table>
        <tr>
            <td><strong>{{ $locale === 'tr' ? 'Yanıt Tarzı' : 'Response Style' }}:</strong></td>
            <td>{{ ucfirst($analysis->behavior_analysis['response_style'] ?? 'N/A') }}</td>
            <td><strong>{{ $locale === 'tr' ? 'Tutarlılık Puanı' : 'Consistency Score' }}:</strong></td>
            <td>{{ $analysis->behavior_analysis['consistency_score'] ?? 'N/A' }}%</td>
        </tr>
        <tr>
            <td><strong>{{ $locale === 'tr' ? 'Netlik Puanı' : 'Clarity Score' }}:</strong></td>
            <td>{{ $analysis->behavior_analysis['clarity_score'] ?? 'N/A' }}%</td>
            <td><strong>{{ $locale === 'tr' ? 'Güven Seviyesi' : 'Confidence Level' }}:</strong></td>
            <td>{{ ucfirst($analysis->behavior_analysis['confidence_level'] ?? 'N/A') }}</td>
        </tr>
    </table>

    <h3 class="section-title">{{ $locale === 'tr' ? 'Boyut Detayları' : 'Dimension Details' }}</h3>

    @foreach(array_slice($analysis->dimension_scores ?? [], 0, 4) as $key => $dimension)
    <div class="insight-card">
        <h4 style="display: flex; justify-content: space-between;">
            <span>{{ ucwords(str_replace('_', ' ', $key)) }}</span>
            <span>{{ $dimension['score'] ?? 0 }}/100</span>
        </h4>
        <p style="font-size: 10pt; color: var(--text-muted);">{{ $dimension['notes'] ?? '' }}</p>
        @if(!empty($dimension['evidence']))
            <ul style="margin-top: 8px; font-size: 10pt;">
                @foreach(array_slice($dimension['evidence'], 0, 2) as $evidence)
                    <li>{{ $evidence }}</li>
                @endforeach
            </ul>
        @endif
    </div>
    @endforeach

    <div class="footer">
        <span>{{ $branding['company_name'] ?? 'TalentQX' }}</span>
        <span>{{ $locale === 'tr' ? 'Sayfa' : 'Page' }} 3 / 4</span>
    </div>
</div>

<!-- PAGE 4: RISKS & NOTES -->
<div class="page">
    <div class="header">
        @if($branding['logo_url'])
            <img src="{{ $branding['logo_url'] }}" alt="Logo" class="logo">
        @else
            <div></div>
        @endif
        <div class="header-text">
            <h1>{{ $locale === 'tr' ? 'Risk Analizi ve Notlar' : 'Risk Analysis & Notes' }}</h1>
            <p>{{ $locale === 'tr' ? 'Uyarı Bayrakları ve Yasal Bildirimler' : 'Warning Flags & Legal Notices' }}</p>
        </div>
    </div>

    <h3 class="section-title">{{ $locale === 'tr' ? 'Risk Bayrakları' : 'Risk Flags' }}</h3>

    @forelse($analysis->red_flags ?? [] as $flag)
        <div class="risk-flag {{ $flag['severity'] ?? 'medium' }}">
            <span class="risk-flag-type">{{ strtoupper($flag['type'] ?? 'INFO') }}</span>
            <div>
                <p>{{ $flag['description'] ?? '' }}</p>
                @if(isset($flag['question_id']))
                    <p style="font-size: 9pt; color: var(--text-muted); margin-top: 5px;">
                        {{ $locale === 'tr' ? 'Soru' : 'Question' }} #{{ $flag['question_id'] }}
                    </p>
                @endif
            </div>
        </div>
    @empty
        <div class="insight-card">
            <p style="color: var(--success-color);">
                {{ $locale === 'tr' ? 'Önemli bir risk bayrağı tespit edilmedi.' : 'No significant risk flags detected.' }}
            </p>
        </div>
    @endforelse

    <h3 class="section-title">{{ $locale === 'tr' ? 'Soru Bazlı Analiz' : 'Question-Level Analysis' }}</h3>

    <table style="font-size: 10pt;">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th style="width: 60px;">{{ $locale === 'tr' ? 'Puan' : 'Score' }}</th>
                <th>{{ $locale === 'tr' ? 'Analiz' : 'Analysis' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($analysis->question_analyses ?? [], 0, 8) as $qa)
            <tr>
                <td>Q{{ $qa['question_id'] ?? '-' }}</td>
                <td>{{ $qa['score'] ?? 0 }}/{{ $qa['max_score'] ?? 5 }}</td>
                <td>{{ Str::limit($qa['analysis'] ?? '', 100) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3 class="section-title">{{ $locale === 'tr' ? 'Yasal Bildirimler' : 'Legal Notices' }}</h3>

    <div class="disclaimer">
        <p><strong>{{ $locale === 'tr' ? 'Veri Koruma (KVKK/GDPR)' : 'Data Protection (GDPR/KVKK)' }}:</strong></p>
        <p style="margin-top: 10px;">
            {{ $locale === 'tr'
                ? 'Bu rapor, 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) ve ilgili mevzuat kapsamında hazırlanmıştır. Raporda yer alan kişisel veriler, yalnızca işe alım sürecinin değerlendirilmesi amacıyla işlenmektedir. Bu rapor gizlidir ve yalnızca yetkili kişiler tarafından görüntülenmelidir.'
                : 'This report has been prepared in accordance with GDPR and applicable data protection regulations. Personal data contained in this report is processed solely for the purpose of employment evaluation. This report is confidential and should only be viewed by authorized personnel.'
            }}
        </p>
        <p style="margin-top: 10px;">
            {{ $locale === 'tr'
                ? 'Yapay zeka destekli analiz sonuçları, nihai karar için tek başına kullanılmamalıdır. İnsan kaynakları uzmanlarının değerlendirmesi ile birlikte değerlendirilmelidir.'
                : 'AI-assisted analysis results should not be used as the sole basis for final decisions. They should be evaluated in conjunction with HR professional assessment.'
            }}
        </p>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 9pt; color: var(--text-muted);">
        <p>{{ $locale === 'tr' ? 'Rapor Oluşturulma Tarihi' : 'Report Generated' }}: {{ $generatedAt->format('d.m.Y H:i') }}</p>
        <p>{{ $locale === 'tr' ? 'Rapor No' : 'Report ID' }}: {{ $reportId }}</p>
        @if(!$branding['company_name'])
            <p style="margin-top: 10px;">Powered by TalentQX</p>
        @endif
    </div>

    <div class="footer">
        <span>{{ $branding['company_name'] ?? 'TalentQX' }}</span>
        <span>{{ $locale === 'tr' ? 'Sayfa' : 'Page' }} 4 / 4</span>
    </div>
</div>

</body>
</html>
