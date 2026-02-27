<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body { margin:0; padding:0; background:#f1f5f9; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#1e293b; }
    .wrapper { max-width:700px; margin:0 auto; background:#fff; }
    .header { background:linear-gradient(135deg,#0f4c81 0%,#1a6bb5 100%); padding:28px 36px; }
    .header h1 { color:#fff; font-size:22px; margin:0; font-weight:700; letter-spacing:0.5px; }
    .header p { color:rgba(255,255,255,0.8); font-size:13px; margin:6px 0 0; }
    .section { padding:24px 36px 16px; }
    .section-title { font-size:15px; font-weight:700; color:#0f4c81; text-transform:uppercase; letter-spacing:1px; border-bottom:2px solid #e2e8f0; padding-bottom:8px; margin:0 0 16px; }
    .kpi-row { display:flex; gap:12px; margin-bottom:20px; }
    .kpi { flex:1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px 16px; text-align:center; }
    .kpi .value { font-size:28px; font-weight:700; color:#0f4c81; margin:0; }
    .kpi .label { font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin:4px 0 0; }
    .kpi .change { font-size:11px; margin:2px 0 0; }
    .kpi .up { color:#16a34a; }
    .kpi .down { color:#dc2626; }
    .kpi .flat { color:#64748b; }
    table.data { width:100%; border-collapse:collapse; font-size:13px; margin-bottom:16px; }
    table.data th { background:#f1f5f9; color:#475569; font-weight:600; text-align:left; padding:8px 12px; border-bottom:1px solid #e2e8f0; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; }
    table.data td { padding:7px 12px; border-bottom:1px solid #f1f5f9; }
    table.data td.num { text-align:right; font-weight:600; color:#0f4c81; }
    .badge { display:inline-block; padding:2px 10px; border-radius:12px; font-size:11px; font-weight:600; }
    .badge-hire { background:#dcfce7; color:#166534; }
    .badge-hold { background:#fef9c3; color:#854d0e; }
    .badge-reject { background:#fee2e2; color:#991b1b; }
    .chart-box { text-align:center; margin:16px 0; background:#f8fafc; border-radius:8px; padding:12px; }
    .chart-box img { max-width:100%; height:auto; }
    .mini-table { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
    .mini-card { flex:1; min-width:120px; background:#f8fafc; border-radius:6px; padding:10px 14px; }
    .mini-card .mc-label { font-size:10px; color:#64748b; text-transform:uppercase; }
    .mini-card .mc-value { font-size:18px; font-weight:700; color:#0f4c81; margin:2px 0 0; }
    .divider { border:0; border-top:1px solid #e2e8f0; margin:0; }
    .footer { background:#f8fafc; padding:20px 36px; text-align:center; border-top:1px solid #e2e8f0; }
    .footer p { color:#94a3b8; font-size:11px; margin:4px 0; }
    .footer a { color:#0f4c81; text-decoration:none; }
    .trend-badge { display:inline-block; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:700; margin-left:8px; }
    .trend-up { background:#dcfce7; color:#166534; }
    .trend-down { background:#fee2e2; color:#991b1b; }
    .trend-flat { background:#f1f5f9; color:#64748b; }
</style>
</head>
<body>
<div class="wrapper">
    {{-- ===== HEADER ===== --}}
    <div class="header">
        <img src="https://talentqx.com/assets/octopus-logo-email.png" alt="Octopus AI" style="max-height: 48px; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;">
        <h1>Octopus AI — Günlük Operasyon Raporu</h1>
        <p>{{ $metrics['date_label'] }} &bull; Maritime Talent Intelligence Platform</p>
    </div>

    {{-- ===== SUMMARY KPIs ===== --}}
    <div class="section">
        <div class="kpi-row">
            <div class="kpi">
                <p class="value">{{ $metrics['candidates']['today_new'] }}</p>
                <p class="label">Yeni Başvuru</p>
                @php $ch = $metrics['summary']['change_vs_yesterday']; @endphp
                <p class="change {{ $ch > 0 ? 'up' : ($ch < 0 ? 'down' : 'flat') }}">
                    {{ $ch > 0 ? '↑' : ($ch < 0 ? '↓' : '→') }} {{ abs($ch) }}% dün'e göre
                </p>
            </div>
            <div class="kpi">
                <p class="value">{{ $metrics['interviews']['today_completed'] }}</p>
                <p class="label">Tamamlanan Mülakat</p>
            </div>
            <div class="kpi">
                <p class="value">{{ $metrics['interviews']['avg_score'] ?? '—' }}</p>
                <p class="label">Ort. Skor</p>
            </div>
            <div class="kpi">
                <p class="value">{{ $metrics['company']['credits_used_today'] }}</p>
                <p class="label">Kredi Kullanımı</p>
            </div>
        </div>
    </div>

    <hr class="divider">

    {{-- ===== BAŞVURULAR ===== --}}
    <div class="section">
        <h2 class="section-title">Başvurular (Pool Candidates)</h2>

        <div class="mini-table">
            @foreach($metrics['candidates']['by_language'] as $lang => $cnt)
            <div class="mini-card">
                <div class="mc-label">{{ strtoupper($lang) }}</div>
                <div class="mc-value">{{ $cnt }}</div>
            </div>
            @endforeach
        </div>

        @if(!empty($metrics['candidates']['by_position']))
        <table class="data">
            <tr><th>Pozisyon</th><th style="text-align:right">Adet</th></tr>
            @foreach($metrics['candidates']['by_position'] as $pos => $cnt)
            <tr><td>{{ ucwords(str_replace('_', ' ', $pos)) }}</td><td class="num">{{ $cnt }}</td></tr>
            @endforeach
        </table>
        @endif

        @if(!empty($metrics['candidates']['by_source']))
        <table class="data">
            <tr><th>Kaynak</th><th style="text-align:right">Adet</th></tr>
            @foreach($metrics['candidates']['by_source'] as $src => $cnt)
            <tr><td>{{ $src }}</td><td class="num">{{ $cnt }}</td></tr>
            @endforeach
        </table>
        @endif

        @if(!empty($metrics['candidates']['by_country']))
        <table class="data">
            <tr><th>Ülke</th><th style="text-align:right">Adet</th></tr>
            @foreach($metrics['candidates']['by_country'] as $cc => $cnt)
            <tr><td>{{ $cc }}</td><td class="num">{{ $cnt }}</td></tr>
            @endforeach
        </table>
        @endif
    </div>

    <hr class="divider">

    {{-- ===== MÜLAKATLAR ===== --}}
    <div class="section">
        <h2 class="section-title">Mülakatlar (Form Interviews)</h2>

        <div class="mini-table">
            <div class="mini-card">
                <div class="mc-label">Başlatılan</div>
                <div class="mc-value">{{ $metrics['interviews']['today_started'] }}</div>
            </div>
            <div class="mini-card">
                <div class="mc-label">Tamamlanan</div>
                <div class="mc-value">{{ $metrics['interviews']['today_completed'] }}</div>
            </div>
            <div class="mini-card">
                <div class="mc-label">Devam Eden</div>
                <div class="mc-value">{{ $metrics['interviews']['today_in_progress'] }}</div>
            </div>
            <div class="mini-card">
                <div class="mc-label">Medyan Skor</div>
                <div class="mc-value">{{ $metrics['interviews']['median_score'] ?? '—' }}</div>
            </div>
        </div>

        @if(!empty($metrics['interviews']['decisions']))
        <table class="data">
            <tr><th>Karar</th><th style="text-align:right">Adet</th></tr>
            @foreach($metrics['interviews']['decisions'] as $d => $cnt)
            <tr>
                <td>
                    <span class="badge {{ $d === 'HIRE' ? 'badge-hire' : ($d === 'REJECT' ? 'badge-reject' : 'badge-hold') }}">{{ $d }}</span>
                </td>
                <td class="num">{{ $cnt }}</td>
            </tr>
            @endforeach
        </table>
        @endif

        @if(!empty($metrics['interviews']['top_roles']))
        <table class="data">
            <tr><th>Top Roller</th><th style="text-align:right">Adet</th></tr>
            @foreach($metrics['interviews']['top_roles'] as $role => $cnt)
            <tr><td>{{ ucwords(str_replace('_', ' ', $role)) }}</td><td class="num">{{ $cnt }}</td></tr>
            @endforeach
        </table>
        @endif
    </div>

    <hr class="divider">

    {{-- ===== ŞİRKET / TÜKETİM ===== --}}
    <div class="section">
        <h2 class="section-title">Şirket & Tüketim</h2>
        <div class="mini-table">
            <div class="mini-card">
                <div class="mc-label">Aktif Şirket</div>
                <div class="mc-value">{{ $metrics['company']['active_companies'] }}</div>
            </div>
            <div class="mini-card">
                <div class="mc-label">Sunum Yapılan</div>
                <div class="mc-value">{{ $metrics['company']['presentations_today'] }}</div>
            </div>
            <div class="mini-card">
                <div class="mc-label">Açık Talep</div>
                <div class="mc-value">{{ $metrics['company']['talent_requests_open'] }}</div>
            </div>
            <div class="mini-card">
                <div class="mc-label">Kredi Kullanımı</div>
                <div class="mc-value">{{ $metrics['company']['credits_used_today'] }}</div>
            </div>
        </div>
    </div>

    <hr class="divider">

    {{-- ===== ENGAGEMENT ===== --}}
    <div class="section">
        <h2 class="section-title">Etkileşim & Funnel</h2>
        <div class="mini-table">
            <div class="mini-card">
                <div class="mc-label">Profil Görüntüleme</div>
                <div class="mc-value">{{ $metrics['engagement']['profile_views_today'] }}</div>
            </div>
            <div class="mini-card">
                <div class="mc-label">Form Oturumu</div>
                <div class="mc-value">{{ $metrics['funnel']['unique_sessions'] }}</div>
            </div>
            <div class="mini-card">
                <div class="mc-label">Başvuru Gönderen</div>
                <div class="mc-value">{{ $metrics['funnel']['submits'] }}</div>
            </div>
            <div class="mini-card">
                <div class="mc-label">Dönüşüm Oranı</div>
                <div class="mc-value">%{{ $metrics['funnel']['conversion_rate'] }}</div>
            </div>
        </div>

        @if(!empty($metrics['funnel']['by_country']))
        <table class="data">
            <tr><th>Ülke (Form Olayları)</th><th style="text-align:right">Oturum</th></tr>
            @foreach(array_slice($metrics['funnel']['by_country'], 0, 5) as $cc => $cnt)
            <tr><td>{{ $cc }}</td><td class="num">{{ $cnt }}</td></tr>
            @endforeach
        </table>
        @endif
    </div>

    <hr class="divider">

    {{-- ===== TREND GRAFİĞİ ===== --}}
    <div class="section">
        <h2 class="section-title">
            Aylık Başvuru Trendi
            @php $mom = $metrics['monthly_trend']['mom_pct']; @endphp
            <span class="trend-badge {{ $mom > 0 ? 'trend-up' : ($mom < 0 ? 'trend-down' : 'trend-flat') }}">
                {{ $mom > 0 ? '↑' : ($mom < 0 ? '↓' : '→') }} {{ abs($mom) }}% MoM
            </span>
            @php $wt = $metrics['monthly_trend']['week_trend_pct']; @endphp
            <span class="trend-badge {{ $wt > 0 ? 'trend-up' : ($wt < 0 ? 'trend-down' : 'trend-flat') }}">
                {{ $wt > 0 ? '↑' : ($wt < 0 ? '↓' : '→') }} {{ abs($wt) }}% 7 gün
            </span>
        </h2>

        <div class="chart-box">
            <img src="{{ $trendChart }}" alt="Günlük Başvuru Trendi" />
        </div>

        <div class="mini-table">
            <div class="mini-card">
                <div class="mc-label">Bu Ay Toplam</div>
                <div class="mc-value">{{ $metrics['monthly_trend']['current_total'] }}</div>
            </div>
            <div class="mini-card">
                <div class="mc-label">Geçen Ay Toplam</div>
                <div class="mc-value">{{ $metrics['monthly_trend']['previous_total'] }}</div>
            </div>
        </div>
    </div>

    @if(!empty($positionChart))
    <div class="section">
        <div class="chart-box">
            <img src="{{ $positionChart }}" alt="Pozisyon Dağılımı" />
        </div>
    </div>
    @endif

    @if(!empty($sourceChart))
    <div class="section">
        <div class="chart-box">
            <img src="{{ $sourceChart }}" alt="Kaynak Dağılımı" />
        </div>
    </div>
    @endif

    <hr class="divider">

    {{-- ===== EMAIL İSTATİSTİKLERİ ===== --}}
    <div class="section">
        <h2 class="section-title">Email İstatistikleri</h2>
        <div class="mini-table">
            <div class="mini-card">
                <div class="mc-label">Gönderilen</div>
                <div class="mc-value">{{ $metrics['emails']['sent'] }}</div>
            </div>
            <div class="mini-card">
                <div class="mc-label">Başarısız</div>
                <div class="mc-value" style="color:{{ $metrics['emails']['failed'] > 0 ? '#dc2626' : '#0f4c81' }}">{{ $metrics['emails']['failed'] }}</div>
            </div>
        </div>
    </div>

    <hr class="divider">

    {{-- ===== GENEL HAVUZ DURUMU ===== --}}
    <div class="section">
        <h2 class="section-title">Genel Havuz Durumu</h2>
        <table class="data">
            <tr><th>Durum</th><th style="text-align:right">Adet</th></tr>
            @foreach($metrics['candidates']['total_by_status'] as $status => $cnt)
            <tr><td>{{ ucwords(str_replace('_', ' ', $status)) }}</td><td class="num">{{ number_format($cnt) }}</td></tr>
            @endforeach
            <tr style="background:#f1f5f9; font-weight:700;">
                <td>TOPLAM</td>
                <td class="num">{{ number_format($metrics['candidates']['total_all']) }}</td>
            </tr>
        </table>
    </div>

    {{-- ===== FOOTER ===== --}}
    <div class="footer">
        <p><a href="https://octopus-ai.net">octopus-ai.net</a> &bull; <a href="https://octopus-ai.net/octo-admin/analytics">Dashboard</a></p>
        <p>&copy; {{ date('Y') }} Octopus AI — Maritime Talent Intelligence Platform</p>
        <p>Bu rapor otomatik olarak {{ now()->format('H:i') }} saatinde oluşturulmuştur.</p>
    </div>
</div>
</body>
</html>
