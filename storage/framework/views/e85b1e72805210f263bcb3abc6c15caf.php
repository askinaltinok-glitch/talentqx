<!DOCTYPE html>
<html lang="<?php echo e($locale); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($locale === 'tr' ? 'Aday Değerlendirme Raporu' : 'Candidate Assessment Report'); ?> - <?php echo e(substr($reportId, 0, 8)); ?></title>
    <style>
        /* TalentQX Corporate PDF Standard v1.0 */
        :root {
            --primary: <?php echo e($branding['primary_color'] ?? '#1E3A5F'); ?>;
            --primary-light: <?php echo e($branding['secondary_color'] ?? '#2E5A8F'); ?>;
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
        <?php echo e($locale === 'tr' ? 'Gizli - Sadece Yetkili Personel İçin' : 'Confidential - Authorized Personnel Only'); ?>

    </div>

    <div class="cover">
        <?php if($branding['logo_url']): ?>
            <img src="<?php echo e($branding['logo_url']); ?>" alt="Logo" class="cover-logo">
        <?php else: ?>
            <div class="header-brand-text" style="font-size: 24pt; margin-bottom: 40px;"><?php echo e($branding['company_name'] ?? 'TalentQX'); ?></div>
        <?php endif; ?>

        <h1 class="cover-title"><?php echo e($locale === 'tr' ? 'Aday Değerlendirme Raporu' : 'Candidate Assessment Report'); ?></h1>
        <p class="cover-subtitle"><?php echo e($locale === 'tr' ? 'Yapay Zeka Destekli Mülakat Analizi' : 'AI-Powered Interview Analysis'); ?></p>

        <div class="cover-card">
            <table>
                <tr>
                    <td><?php echo e($locale === 'tr' ? 'Aday Kodu' : 'Candidate ID'); ?></td>
                    <td><?php echo e(strtoupper(substr($session->candidate_id, 0, 8))); ?></td>
                </tr>
                <tr>
                    <td><?php echo e($locale === 'tr' ? 'Pozisyon' : 'Position'); ?></td>
                    <td><?php echo e(ucwords(str_replace('_', ' ', $session->role_key))); ?></td>
                </tr>
                <?php if($currentContext): ?>
                <tr>
                    <td><?php echo e($locale === 'tr' ? 'Görev Bağlamı' : 'Job Context'); ?></td>
                    <td><?php echo e($currentContext->getLabel($locale)); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><?php echo e($locale === 'tr' ? 'Değerlendirme Tarihi' : 'Assessment Date'); ?></td>
                    <td><?php echo e($session->finished_at?->format('d.m.Y') ?? now()->format('d.m.Y')); ?></td>
                </tr>
                <tr>
                    <td><?php echo e($locale === 'tr' ? 'Rapor No' : 'Report ID'); ?></td>
                    <td><?php echo e(strtoupper(substr($reportId, 0, 8))); ?></td>
                </tr>
            </table>
        </div>

        <div class="decision-badge <?php echo e($analysis->recommendation); ?>">
            <?php if($analysis->recommendation === 'hire'): ?>
                <span class="decision-badge-icon">✓</span>
                <?php echo e($locale === 'tr' ? 'ÖNERİLİR' : 'RECOMMENDED'); ?>

            <?php elseif($analysis->recommendation === 'hold'): ?>
                <span class="decision-badge-icon">◐</span>
                <?php echo e($locale === 'tr' ? 'DEĞERLENDİR' : 'EVALUATE'); ?>

            <?php else: ?>
                <span class="decision-badge-icon">✗</span>
                <?php echo e($locale === 'tr' ? 'ÖNERİLMEZ' : 'NOT RECOMMENDED'); ?>

            <?php endif; ?>
        </div>

        <div class="score-display">
            <div class="score-display-value"><?php echo e(number_format($analysis->overall_score, 0)); ?></div>
            <div class="score-display-label"><?php echo e($locale === 'tr' ? 'Genel Puan / 100' : 'Overall Score / 100'); ?></div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-left">
            <span class="footer-confidential"><?php echo e($locale === 'tr' ? 'Gizli' : 'Confidential'); ?></span>
            <span><?php echo e($branding['company_name'] ?? 'TalentQX'); ?></span>
        </div>
        <span><?php echo e($locale === 'tr' ? 'Sayfa' : 'Page'); ?> 1 / 4</span>
    </div>
</div>

<!-- PAGE 2: EXECUTIVE SUMMARY -->
<div class="page">
    <div class="confidential-banner">
        <?php echo e($locale === 'tr' ? 'Gizli - Sadece Yetkili Personel İçin' : 'Confidential - Authorized Personnel Only'); ?>

    </div>

    <div class="header">
        <div class="header-brand">
            <?php if($branding['logo_url']): ?>
                <img src="<?php echo e($branding['logo_url']); ?>" alt="Logo">
            <?php else: ?>
                <span class="header-brand-text"><?php echo e($branding['company_name'] ?? 'TalentQX'); ?></span>
            <?php endif; ?>
        </div>
        <div class="header-meta">
            <strong><?php echo e($locale === 'tr' ? 'Yönetici Özeti' : 'Executive Summary'); ?></strong>
            <?php echo e($locale === 'tr' ? 'Aday' : 'Candidate'); ?>: <?php echo e(strtoupper(substr($session->candidate_id, 0, 8))); ?>

        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="quick-stat">
            <div class="quick-stat-value"><?php echo e(number_format($analysis->overall_score, 0)); ?></div>
            <div class="quick-stat-label"><?php echo e($locale === 'tr' ? 'Genel Puan' : 'Overall'); ?></div>
        </div>
        <div class="quick-stat">
            <div class="quick-stat-value"><?php echo e($analysis->confidence_percent ?? 85); ?>%</div>
            <div class="quick-stat-label"><?php echo e($locale === 'tr' ? 'Güven' : 'Confidence'); ?></div>
        </div>
        <div class="quick-stat">
            <div class="quick-stat-value"><?php echo e(count($analysis->strengths ?? [])); ?></div>
            <div class="quick-stat-label"><?php echo e($locale === 'tr' ? 'Güçlü Yön' : 'Strengths'); ?></div>
        </div>
        <div class="quick-stat">
            <div class="quick-stat-value"><?php echo e(count($analysis->red_flags ?? [])); ?></div>
            <div class="quick-stat-label"><?php echo e($locale === 'tr' ? 'Risk' : 'Risks'); ?></div>
        </div>
    </div>

    <h3 class="section-title">
        <span class="section-icon">1</span>
        <?php echo e($locale === 'tr' ? 'Özet Değerlendirme' : 'Summary Assessment'); ?>

    </h3>

    <div class="summary-box">
        <p><?php echo e($narratives['narratives']['executive_summary'] ?? $analysis->summary_text ?? ($locale === 'tr' ? 'Aday değerlendirmesi tamamlanmıştır.' : 'Candidate assessment has been completed.')); ?></p>
    </div>

    <h3 class="section-title">
        <span class="section-icon">2</span>
        <?php echo e($locale === 'tr' ? 'Yetkinlik Puanları' : 'Competency Scores'); ?>

    </h3>

    <table>
        <thead>
            <tr>
                <th><?php echo e($locale === 'tr' ? 'Boyut' : 'Dimension'); ?></th>
                <th style="width: 220px;"><?php echo e($locale === 'tr' ? 'Performans' : 'Performance'); ?></th>
                <th style="width: 60px; text-align: right;"><?php echo e($locale === 'tr' ? 'Puan' : 'Score'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
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
            ?>
            <?php $__currentLoopData = $analysis->dimension_scores ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $dimension): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $score = $dimension['score'] ?? 0;
                $level = $score >= 75 ? 'excellent' : ($score >= 60 ? 'good' : ($score >= 45 ? 'moderate' : 'low'));
            ?>
            <tr>
                <td><?php echo e($dimensionLabels[$key] ?? ucwords(str_replace('_', ' ', $key))); ?></td>
                <td>
                    <div class="score-bar-container">
                        <div class="score-bar">
                            <div class="score-bar-fill <?php echo e($level); ?>" style="width: <?php echo e($score); ?>%;"></div>
                        </div>
                    </div>
                </td>
                <td class="score-value"><?php echo e($score); ?>/100</td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <div class="insight-grid">
        <div class="insight-card">
            <h4><span class="status-dot green"></span><?php echo e($locale === 'tr' ? 'Güçlü Yönler' : 'Strengths'); ?></h4>
            <?php if(!empty($narratives['narratives']['radar_strengths'])): ?>
                <p style="margin-bottom: 10px; font-style: italic;"><?php echo e($narratives['narratives']['radar_strengths']); ?></p>
            <?php endif; ?>
            <ul>
                <?php $__empty_1 = true; $__currentLoopData = $analysis->strengths ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $strength): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li><?php echo e($strength); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li><?php echo e($locale === 'tr' ? 'Veri bekleniyor' : 'Data pending'); ?></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="insight-card">
            <h4><span class="status-dot yellow"></span><?php echo e($locale === 'tr' ? 'Gelişim Alanları' : 'Development Areas'); ?></h4>
            <?php if(!empty($narratives['narratives']['radar_balance'])): ?>
                <p style="margin-bottom: 10px; font-style: italic;"><?php echo e($narratives['narratives']['radar_balance']); ?></p>
            <?php endif; ?>
            <ul>
                <?php $__empty_1 = true; $__currentLoopData = $analysis->improvement_areas ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $area): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li><?php echo e($area); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li><?php echo e($locale === 'tr' ? 'Belirgin gelişim alanı yok' : 'No significant areas'); ?></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <?php if(!empty($narratives['narratives']['risk_comment'])): ?>
    <div class="warning-box">
        <h4><?php echo e($locale === 'tr' ? 'Dikkat Edilmesi Gerekenler' : 'Points of Attention'); ?></h4>
        <p><?php echo e($narratives['narratives']['risk_comment']); ?></p>
    </div>
    <?php endif; ?>

    <div class="footer">
        <div class="footer-left">
            <span class="footer-confidential"><?php echo e($locale === 'tr' ? 'Gizli' : 'Confidential'); ?></span>
            <span><?php echo e($generatedAt->format('d.m.Y H:i')); ?></span>
        </div>
        <span><?php echo e($locale === 'tr' ? 'Sayfa' : 'Page'); ?> 2 / 4</span>
    </div>
</div>

<!-- PAGE 3: DETAILED ANALYSIS -->
<div class="page">
    <div class="confidential-banner">
        <?php echo e($locale === 'tr' ? 'Gizli - Sadece Yetkili Personel İçin' : 'Confidential - Authorized Personnel Only'); ?>

    </div>

    <div class="header">
        <div class="header-brand">
            <?php if($branding['logo_url']): ?>
                <img src="<?php echo e($branding['logo_url']); ?>" alt="Logo">
            <?php else: ?>
                <span class="header-brand-text"><?php echo e($branding['company_name'] ?? 'TalentQX'); ?></span>
            <?php endif; ?>
        </div>
        <div class="header-meta">
            <strong><?php echo e($locale === 'tr' ? 'Detaylı Analiz' : 'Detailed Analysis'); ?></strong>
            <?php echo e($locale === 'tr' ? 'Aday' : 'Candidate'); ?>: <?php echo e(strtoupper(substr($session->candidate_id, 0, 8))); ?>

        </div>
    </div>

    <h3 class="section-title">
        <span class="section-icon">3</span>
        <?php echo e($locale === 'tr' ? 'Yetkinlik Radar Grafiği' : 'Competency Radar Chart'); ?>

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
                <?php
                    $labels = $radarData['labels'][$locale] ?? $radarData['labels']['en'];
                    $values = $radarData['values'];
                    $count = count($labels);
                    $angleStep = 360 / $count;
                ?>
                <?php for($i = 0; $i < $count; $i++): ?>
                    <?php
                        $angle = ($i * $angleStep - 90) * M_PI / 180;
                        $x = 200 + 140 * cos($angle);
                        $y = 200 + 140 * sin($angle);
                    ?>
                    <line x1="200" y1="200" x2="<?php echo e($x); ?>" y2="<?php echo e($y); ?>"/>
                <?php endfor; ?>
            </g>

            <!-- Data -->
            <polygon
                fill="<?php echo e($branding['primary_color'] ?? '#1E3A5F'); ?>"
                fill-opacity="0.25"
                stroke="<?php echo e($branding['primary_color'] ?? '#1E3A5F'); ?>"
                stroke-width="2.5"
                points="<?php
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
                ?>"
            />

            <?php for($i = 0; $i < $count; $i++): ?>
                <?php
                    $angle = ($i * $angleStep - 90) * M_PI / 180;
                    $value = ($values[$i] ?? 50) / 100;
                    $r = 140 * $value;
                    $x = 200 + $r * cos($angle);
                    $y = 200 + $r * sin($angle);
                ?>
                <circle cx="<?php echo e($x); ?>" cy="<?php echo e($y); ?>" r="5" fill="<?php echo e($branding['primary_color'] ?? '#1E3A5F'); ?>"/>
            <?php endfor; ?>

            <!-- Labels -->
            <?php for($i = 0; $i < $count; $i++): ?>
                <?php
                    $angle = ($i * $angleStep - 90) * M_PI / 180;
                    $x = 200 + 165 * cos($angle);
                    $y = 200 + 165 * sin($angle);
                    $anchor = 'middle';
                    if($x < 190) $anchor = 'end';
                    if($x > 210) $anchor = 'start';
                ?>
                <text x="<?php echo e($x); ?>" y="<?php echo e($y); ?>" text-anchor="<?php echo e($anchor); ?>" font-size="10" fill="#374151" font-weight="500">
                    <?php echo e($labels[$i]); ?>

                </text>
                <text x="<?php echo e($x); ?>" y="<?php echo e($y + 12); ?>" text-anchor="<?php echo e($anchor); ?>" font-size="9" fill="#6B7280">
                    (<?php echo e($values[$i] ?? 0); ?>)
                </text>
            <?php endfor; ?>
        </svg>
    </div>

    <?php if($contextComparison && count($contextComparison) > 0): ?>
    <h3 class="section-title">
        <span class="section-icon">4</span>
        <?php echo e($locale === 'tr' ? 'Bağlam Uygunluk Analizi' : 'Context Fit Analysis'); ?>

    </h3>

    <table>
        <thead>
            <tr>
                <th><?php echo e($locale === 'tr' ? 'Görev Bağlamı' : 'Job Context'); ?></th>
                <th style="width: 180px;"><?php echo e($locale === 'tr' ? 'Uygunluk' : 'Fit'); ?></th>
                <th style="width: 70px; text-align: right;"><?php echo e($locale === 'tr' ? 'Puan' : 'Score'); ?></th>
                <th style="width: 100px; text-align: center;"><?php echo e($locale === 'tr' ? 'Durum' : 'Status'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $contextComparison; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ctx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $ctxScore = $ctx['score'] ?? 0;
                $ctxLevel = $ctx['level'] ?? 'moderate';
            ?>
            <tr <?php if($currentContext && $currentContext->context_key === ($ctx['context_key'] ?? '')): ?> style="background: #EFF6FF;" <?php endif; ?>>
                <td>
                    <strong><?php echo e($ctx['context']); ?></strong>
                    <?php if($currentContext && $currentContext->context_key === ($ctx['context_key'] ?? '')): ?>
                        <br><span style="font-size: 8pt; color: var(--accent);">← <?php echo e($locale === 'tr' ? 'Mevcut Başvuru' : 'Current Application'); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="score-bar-container">
                        <div class="score-bar">
                            <div class="score-bar-fill <?php echo e($ctxLevel); ?>" style="width: <?php echo e($ctxScore); ?>%;"></div>
                        </div>
                    </div>
                </td>
                <td class="score-value"><?php echo e(number_format($ctxScore, 0)); ?></td>
                <td style="text-align: center;">
                    <span class="context-badge <?php echo e($ctxLevel); ?>">
                        <?php echo e($ctx['status'] ?? ucfirst($ctxLevel)); ?>

                    </span>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <?php if(!empty($narratives['narratives']['context_comparison_comment'])): ?>
    <div class="summary-box">
        <p><?php echo e($narratives['narratives']['context_comparison_comment']); ?></p>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <h3 class="section-title">
        <span class="section-icon"><?php echo e($contextComparison ? '5' : '4'); ?></span>
        <?php echo e($locale === 'tr' ? 'Sonuç ve Öneriler' : 'Conclusion & Recommendations'); ?>

    </h3>

    <div class="summary-box">
        <p><?php echo e($narratives['narratives']['closing_comment'] ?? $analysis->hr_recommendations ?? ($locale === 'tr' ? 'Değerlendirme tamamlandı. Detaylı inceleme için İK ile görüşülmesi önerilir.' : 'Assessment completed. Consultation with HR is recommended for detailed review.')); ?></p>
    </div>

    <div class="footer">
        <div class="footer-left">
            <span class="footer-confidential"><?php echo e($locale === 'tr' ? 'Gizli' : 'Confidential'); ?></span>
            <span><?php echo e($generatedAt->format('d.m.Y H:i')); ?></span>
        </div>
        <span><?php echo e($locale === 'tr' ? 'Sayfa' : 'Page'); ?> 3 / 4</span>
    </div>
</div>

<!-- PAGE 4: RISKS & LEGAL -->
<div class="page">
    <div class="confidential-banner">
        <?php echo e($locale === 'tr' ? 'Gizli - Sadece Yetkili Personel İçin' : 'Confidential - Authorized Personnel Only'); ?>

    </div>

    <div class="header">
        <div class="header-brand">
            <?php if($branding['logo_url']): ?>
                <img src="<?php echo e($branding['logo_url']); ?>" alt="Logo">
            <?php else: ?>
                <span class="header-brand-text"><?php echo e($branding['company_name'] ?? 'TalentQX'); ?></span>
            <?php endif; ?>
        </div>
        <div class="header-meta">
            <strong><?php echo e($locale === 'tr' ? 'Risk ve Uyum' : 'Risk & Compliance'); ?></strong>
            <?php echo e($locale === 'tr' ? 'Aday' : 'Candidate'); ?>: <?php echo e(strtoupper(substr($session->candidate_id, 0, 8))); ?>

        </div>
    </div>

    <h3 class="section-title">
        <span class="section-icon">!</span>
        <?php echo e($locale === 'tr' ? 'Risk Bayrakları' : 'Risk Flags'); ?>

    </h3>

    <?php $__empty_1 = true; $__currentLoopData = $analysis->red_flags ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $flag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="risk-item <?php echo e($flag['severity'] ?? 'medium'); ?>">
            <span class="risk-type"><?php echo e(strtoupper($flag['type'] ?? 'INFO')); ?></span>
            <div>
                <p style="margin: 0;"><?php echo e($flag['description'] ?? ''); ?></p>
                <?php if(isset($flag['question_id'])): ?>
                    <p style="font-size: 8pt; color: var(--text-muted); margin-top: 4px;">
                        <?php echo e($locale === 'tr' ? 'Kaynak: Soru' : 'Source: Question'); ?> #<?php echo e($flag['question_id']); ?>

                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="insight-card" style="background: #F0FDF4; border-color: var(--success);">
            <p style="color: var(--success); margin: 0;">
                <strong>✓</strong> <?php echo e($locale === 'tr' ? 'Kritik risk bayrağı tespit edilmedi.' : 'No critical risk flags detected.'); ?>

            </p>
        </div>
    <?php endif; ?>

    <h3 class="section-title">
        <span class="section-icon">Q</span>
        <?php echo e($locale === 'tr' ? 'Soru Bazlı Özet' : 'Question Summary'); ?>

    </h3>

    <table style="font-size: 9pt;">
        <thead>
            <tr>
                <th style="width: 35px;">#</th>
                <th style="width: 55px;"><?php echo e($locale === 'tr' ? 'Puan' : 'Score'); ?></th>
                <th><?php echo e($locale === 'tr' ? 'Değerlendirme Notu' : 'Assessment Note'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = array_slice($analysis->question_analyses ?? [], 0, 6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $qa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td style="font-weight: 600;">Q<?php echo e($qa['question_id'] ?? '-'); ?></td>
                <td><?php echo e($qa['score'] ?? 0); ?>/<?php echo e($qa['max_score'] ?? 5); ?></td>
                <td><?php echo e(Str::limit($qa['analysis'] ?? '-', 90)); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <div class="legal-box">
        <h4><?php echo e($locale === 'tr' ? 'Yasal Uyarı ve Veri Koruma' : 'Legal Notice & Data Protection'); ?></h4>

        <p>
            <strong><?php echo e($locale === 'tr' ? 'Gizlilik:' : 'Confidentiality:'); ?></strong>
            <?php echo e($locale === 'tr'
                ? 'Bu rapor gizli bilgi içermektedir ve yalnızca yetkili personel tarafından görüntülenmelidir. İzinsiz dağıtım veya çoğaltma yasaktır.'
                : 'This report contains confidential information and should only be viewed by authorized personnel. Unauthorized distribution or reproduction is prohibited.'); ?>

        </p>

        <p>
            <strong><?php echo e($locale === 'tr' ? 'KVKK/GDPR Uyumu:' : 'GDPR/KVKK Compliance:'); ?></strong>
            <?php echo e($locale === 'tr'
                ? 'Bu rapor, 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) ve Genel Veri Koruma Yönetmeliği (GDPR) kapsamında hazırlanmıştır. Kişisel veriler yalnızca işe alım değerlendirmesi amacıyla işlenmektedir.'
                : 'This report has been prepared in compliance with the General Data Protection Regulation (GDPR) and applicable local data protection laws. Personal data is processed solely for recruitment evaluation purposes.'); ?>

        </p>

        <p>
            <strong><?php echo e($locale === 'tr' ? 'Yapay Zeka Bildirimi:' : 'AI Disclosure:'); ?></strong>
            <?php echo e($locale === 'tr'
                ? 'Bu rapor yapay zeka destekli analiz içermektedir. AI analiz sonuçları, nihai işe alım kararı için tek başına kullanılmamalı, İK profesyonellerinin değerlendirmesiyle birlikte değerlendirilmelidir.'
                : 'This report contains AI-assisted analysis. AI analysis results should not be used as the sole basis for hiring decisions and should be evaluated in conjunction with HR professional assessment.'); ?>

        </p>

        <p>
            <strong><?php echo e($locale === 'tr' ? 'Saklama Süresi:' : 'Retention Period:'); ?></strong>
            <?php echo e($locale === 'tr'
                ? 'Bu rapor, oluşturulma tarihinden itibaren 30 gün süreyle saklanacak ve ardından otomatik olarak silinecektir.'
                : 'This report will be retained for 30 days from the generation date and will be automatically deleted thereafter.'); ?>

        </p>
    </div>

    <div style="margin-top: 24px; text-align: center; font-size: 9pt; color: var(--text-muted);">
        <p><strong><?php echo e($locale === 'tr' ? 'Rapor Bilgileri' : 'Report Information'); ?></strong></p>
        <p><?php echo e($locale === 'tr' ? 'Oluşturulma' : 'Generated'); ?>: <?php echo e($generatedAt->format('d.m.Y H:i:s')); ?> | ID: <?php echo e($reportId); ?></p>
        <p style="margin-top: 8px;">
            <?php if($branding['company_name']): ?>
                <?php echo e($branding['company_name']); ?> |
            <?php endif; ?>
            Powered by TalentQX
        </p>
    </div>

    <div class="footer">
        <div class="footer-left">
            <span class="footer-confidential"><?php echo e($locale === 'tr' ? 'Gizli' : 'Confidential'); ?></span>
            <span><?php echo e($generatedAt->format('d.m.Y H:i')); ?></span>
        </div>
        <span><?php echo e($locale === 'tr' ? 'Sayfa' : 'Page'); ?> 4 / 4</span>
    </div>
</div>

</body>
</html>
<?php /**PATH /www/wwwroot/talentqx.com/api/resources/views/reports/interview.blade.php ENDPATH**/ ?>