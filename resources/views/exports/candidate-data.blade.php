<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Aday Veri Raporu - {{ $candidate->full_name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1a56db;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #1a56db;
            margin: 0;
            font-size: 18px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0;
            font-size: 10px;
        }
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section-title {
            background: #f3f4f6;
            padding: 8px 12px;
            font-weight: bold;
            color: #1a56db;
            border-left: 4px solid #1a56db;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            text-align: left;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
        }
        .label {
            color: #6b7280;
            font-weight: 500;
            width: 30%;
        }
        .value {
            color: #111827;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
        }
        .kvkk-notice {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 10px;
        }
        .kvkk-notice strong {
            color: #d97706;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>KVKK Veri Raporu</h1>
        <p>Aday Kisisel Verileri Dokumanı</p>
        <p>Olusturulma Tarihi: {{ $exportDate }}</p>
    </div>

    <div class="kvkk-notice">
        <strong>KVKK Madde 11 - Ilgili Kisinin Haklari</strong><br>
        Bu dokuman, 6698 sayili Kisisel Verilerin Korunmasi Kanunu kapsaminda,
        veri sahibinin talep ettigi kisisel verileri icermektedir.
    </div>

    <div class="section">
        <div class="section-title">Kisisel Bilgiler</div>
        <table>
            <tr>
                <td class="label">Ad Soyad</td>
                <td class="value">{{ $data['personal_info']['first_name'] }} {{ $data['personal_info']['last_name'] }}</td>
            </tr>
            <tr>
                <td class="label">E-posta</td>
                <td class="value">{{ $data['personal_info']['email'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Telefon</td>
                <td class="value">{{ $data['personal_info']['phone'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Basvuru Tarihi</td>
                <td class="value">{{ \Carbon\Carbon::parse($data['personal_info']['application_date'])->format('d.m.Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Durum</td>
                <td class="value">{{ $data['personal_info']['status'] }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Basvuru Bilgileri</div>
        <table>
            <tr>
                <td class="label">Pozisyon</td>
                <td class="value">{{ $data['application_info']['job_title'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Lokasyon</td>
                <td class="value">{{ $data['application_info']['job_location'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Kaynak</td>
                <td class="value">{{ $data['application_info']['source'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">KVKK Onayi</td>
                <td class="value">{{ $data['application_info']['consent_given'] ? 'Evet' : 'Hayir' }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($data['interviews']))
    <div class="section">
        <div class="section-title">Mulakat Bilgileri</div>
        @foreach($data['interviews'] as $interview)
        <table>
            <tr>
                <td class="label">Mulakat ID</td>
                <td class="value">{{ $interview['id'] }}</td>
            </tr>
            <tr>
                <td class="label">Durum</td>
                <td class="value">{{ $interview['status'] }}</td>
            </tr>
            <tr>
                <td class="label">Baslangic</td>
                <td class="value">{{ $interview['started_at'] ? \Carbon\Carbon::parse($interview['started_at'])->format('d.m.Y H:i') : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Bitis</td>
                <td class="value">{{ $interview['completed_at'] ? \Carbon\Carbon::parse($interview['completed_at'])->format('d.m.Y H:i') : '-' }}</td>
            </tr>
            @if($interview['analysis'])
            <tr>
                <td class="label">Genel Puan</td>
                <td class="value">{{ $interview['analysis']['overall_score'] }}</td>
            </tr>
            <tr>
                <td class="label">Oneri</td>
                <td class="value">{{ $interview['analysis']['recommendation'] ?? '-' }}</td>
            </tr>
            @endif
        </table>

        @if(!empty($interview['responses']))
        <p><strong>Mulakat Yanitlari:</strong></p>
        <table>
            <thead>
                <tr>
                    <th>Soru</th>
                    <th>Transkript</th>
                    <th>Sure (sn)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($interview['responses'] as $response)
                <tr>
                    <td>{{ \Illuminate\Support\Str::limit($response['question'] ?? '-', 50) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($response['transcript'] ?? '-', 100) }}</td>
                    <td>{{ $response['duration_seconds'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        <hr>
        @endforeach
    </div>
    @endif

    <div class="section">
        <div class="section-title">Veri Saklama Bilgileri</div>
        <table>
            <tr>
                <td class="label">Saklama Suresi</td>
                <td class="value">{{ $data['data_retention']['retention_days'] }} gun</td>
            </tr>
            <tr>
                <td class="label">Planlanan Silme Tarihi</td>
                <td class="value">{{ \Carbon\Carbon::parse($data['data_retention']['scheduled_deletion'])->format('d.m.Y') }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($data['audit_trail']))
    <div class="section">
        <div class="section-title">Islem Gecmisi</div>
        <table>
            <thead>
                <tr>
                    <th>Islem</th>
                    <th>Tarih</th>
                    <th>IP Adresi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['audit_trail'] as $log)
                <tr>
                    <td>{{ $log['action'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($log['timestamp'])->format('d.m.Y H:i') }}</td>
                    <td>{{ $log['ip_address'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        Octopus AI - KVKK Uyumlu Veri Raporu | {{ $exportDate }}
    </div>
</body>
</html>
